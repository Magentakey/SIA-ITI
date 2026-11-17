<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\T4\Attendance;
use App\Models\T4\AttendanceAudit;
use App\Models\T4\Student;
use App\Services\Presensi\Actor;
use App\Services\Presensi\QrToken;
use App\Support\Api\ApiException;
use App\Support\Api\ApiResponse;
use App\Support\Api\Ids;
use App\Support\Api\Input;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    private function enrolled(string $courseId, string $studentId): bool
    {
        return DB::table('course_enrollments')->where('course_id', $courseId)->where('student_id', $studentId)->exists();
    }

    // 7. POST /attendance/submit  (Mahasiswa) -> 201. studentId diambil dari token.
    public function submit(Request $request): JsonResponse
    {
        $actor = Actor::from($request);
        $data = $request->attributes->get('jsonBody', []);

        $meetingId = Input::requireString($data, 'meetingId');
        $method = Input::enum(Input::requireString($data, 'method', 20), 'method', ['QR', 'BUTTON']);
        $qrToken = $method === 'QR' ? Input::requireString($data, 'qrToken') : null;
        Ids::assert('mtg', $meetingId, 'meetingId');

        $meeting = MeetingController::find($meetingId);
        $student = $actor->student();

        if (! $this->enrolled($meeting->course_id, $student->student_id)) {
            throw ApiException::rule('STUDENT_NOT_ENROLLED', 'Mahasiswa tidak terdaftar pada kelas ini.');
        }
        if ($meeting->isEnded()) {
            throw ApiException::rule('MEETING_ENDED', 'Kelas sudah berakhir. Presensi mandiri ditolak, hubungi dosen untuk presensi manual.');
        }
        if (! $meeting->attendance_open) {
            throw ApiException::rule('ATTENDANCE_CLOSED', 'Pintu absensi sedang tertutup.');
        }
        if ($meeting->attendance_method !== 'BOTH' && $meeting->attendance_method !== $method) {
            throw ApiException::rule('ATTENDANCE_METHOD_NOT_ALLOWED', "Meeting ini hanya menerima presensi metode {$meeting->attendance_method}.");
        }
        if ($method === 'QR' && ! QrToken::isValid($meeting->meeting_id, (string) $qrToken)) {
            throw ApiException::rule('QR_EXPIRED', 'QR Code kedaluwarsa atau tidak valid.');
        }

        try {
            $attendance = Attendance::create([
                'attendance_id' => Ids::make('att'),
                'meeting_id' => $meeting->meeting_id,
                'student_id' => $student->student_id,
                'status' => 'ATTENDED',
                'method' => $method,
                'recorded_at' => now(),
            ]);
        } catch (QueryException $e) { // unique(meeting_id, student_id): balapan dua request bersamaan
            throw ApiException::rule('ATTENDANCE_DUPLICATE', 'Presensi untuk Meeting ini sudah tercatat.');
        }

        return ApiResponse::created('Presensi berhasil dicatat.', $attendance->toApi());
    }

    // 8. POST /meetings/{meetingId}/attendance/manual  (Dosen). 201 bila baru, 200 bila memperbarui catatan.
    public function manual(Request $request, string $meetingId): JsonResponse
    {
        $actor = Actor::from($request);
        $meeting = MeetingController::find($meetingId);
        if ($meeting->lecturer_id !== $actor->lecturer()->lecturer_id) {
            throw ApiException::forbidden('NOT_MEETING_LECTURER', 'Anda bukan dosen pengampu Meeting ini.');
        }

        $data = $request->attributes->get('jsonBody', []);
        $studentId = Ids::assert('std', Input::requireString($data, 'studentId'), 'studentId');
        $status = Input::enum(Input::requireString($data, 'status', 20), 'status', Attendance::STATUSES);

        Student::find($studentId) ?? throw ApiException::notFound('STUDENT_NOT_FOUND', 'Mahasiswa tidak ditemukan.');
        if (! $this->enrolled($meeting->course_id, $studentId)) {
            throw ApiException::rule('STUDENT_NOT_ENROLLED', 'Mahasiswa tidak terdaftar pada kelas ini.');
        }

        [$attendance, $created] = DB::transaction(function () use ($meeting, $studentId, $status, $actor) {
            $existing = Attendance::where('meeting_id', $meeting->meeting_id)->where('student_id', $studentId)->first();
            $old = $existing?->status;
            if ($existing) {
                $existing->update(['status' => $status, 'method' => 'MANUAL', 'recorded_at' => now(), 'confirmed_by' => $actor->userId]);
                $attendance = $existing;
            } else {
                $attendance = Attendance::create([
                    'attendance_id' => Ids::make('att'),
                    'meeting_id' => $meeting->meeting_id,
                    'student_id' => $studentId,
                    'status' => $status,
                    'method' => 'MANUAL',
                    'recorded_at' => now(),
                    'confirmed_by' => $actor->userId, // dari token, bukan dari body
                ]);
            }
            $this->audit($attendance, $actor, $old, $status, AttendanceAudit::MANUAL_ENTRY);

            return [$attendance, $existing === null];
        });

        return $created
            ? ApiResponse::created('Presensi manual berhasil dicatat.', $attendance->toApi())
            : ApiResponse::ok('Catatan presensi berhasil diperbarui secara manual.', $attendance->toApi());
    }

    // 9. PUT /attendance/{attendanceId}/correction  (Dosen)
    public function correction(Request $request, string $attendanceId): JsonResponse
    {
        $actor = Actor::from($request);
        Ids::assert('att', $attendanceId, 'attendanceId');
        $attendance = Attendance::find($attendanceId)
            ?? throw ApiException::notFound('ATTENDANCE_NOT_FOUND', 'Catatan presensi tidak ditemukan.');
        $meeting = MeetingController::find($attendance->meeting_id);
        if ($meeting->lecturer_id !== $actor->lecturer()->lecturer_id) {
            throw ApiException::forbidden('NOT_MEETING_LECTURER', 'Anda bukan dosen pengampu Meeting ini.');
        }

        $data = $request->attributes->get('jsonBody', []);
        $status = Input::enum(Input::requireString($data, 'status', 20), 'status', Attendance::STATUSES);
        if ($status === $attendance->status) {
            throw ApiException::rule('STATUS_UNCHANGED', 'Status baru sama dengan status saat ini.');
        }

        DB::transaction(function () use ($attendance, $actor, $status) {
            $old = $attendance->status;
            $attendance->update(['status' => $status, 'recorded_at' => now(), 'confirmed_by' => $actor->userId]);
            $this->audit($attendance, $actor, $old, $status, AttendanceAudit::CORRECTION);
        });

        return ApiResponse::ok('Presensi berhasil dikoreksi.', $attendance->refresh()->toApi());
    }

    private function audit(Attendance $attendance, Actor $actor, ?string $old, string $new, string $type): void
    {
        AttendanceAudit::create([
            'audit_id' => Ids::make('aud'),
            'attendance_id' => $attendance->attendance_id,
            'changed_by' => $actor->userId,
            'old_status' => $old,
            'new_status' => $new,
            'action_type' => $type,
        ]);
    }
}
