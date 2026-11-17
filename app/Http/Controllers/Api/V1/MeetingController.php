<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\T4\Course;
use App\Models\T4\LecturerAttendance;
use App\Models\T4\Meeting;
use App\Services\Presensi\Actor;
use App\Services\Presensi\QrToken;
use App\Support\Api\ApiException;
use App\Support\Api\ApiResponse;
use App\Support\Api\Ids;
use App\Support\Api\Input;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MeetingController extends Controller
{
    /** Muat Meeting: 400 format ID salah, 404 tidak ada. */
    public static function find(string $meetingId): Meeting
    {
        Ids::assert('mtg', $meetingId, 'meetingId');

        return Meeting::with('lecturerAttendance')->find($meetingId)
            ?? throw ApiException::notFound('MEETING_NOT_FOUND', 'Meeting tidak ditemukan.');
    }

    /** 403 bila dosen pemanggil bukan pengampu Meeting. */
    private function ensureOwner(Meeting $meeting, Actor $actor): void
    {
        if ($meeting->lecturer_id !== $actor->lecturer()->lecturer_id) {
            throw ApiException::forbidden('NOT_MEETING_LECTURER', 'Anda bukan dosen pengampu Meeting ini.');
        }
    }

    // 1. POST /meetings  (Dosen) -> 201
    public function store(Request $request): JsonResponse
    {
        $actor = Actor::from($request);
        $data = $request->attributes->get('jsonBody', []);

        $courseId = Ids::assert('crs', Input::requireString($data, 'courseId'), 'courseId');
        $start = Input::datetime(Input::requireString($data, 'startTime'), 'startTime');
        $end = Input::datetime(Input::requireString($data, 'endTime'), 'endTime');
        $scheduleId = Input::optionalString($data, 'scheduleId');
        $topic = Input::optionalString($data, 'topic');
        $methodRaw = Input::optionalString($data, 'attendanceMethod');

        $course = Course::find($courseId)
            ?? throw ApiException::notFound('COURSE_NOT_FOUND', 'Mata kuliah tidak ditemukan.');
        $lecturer = $actor->lecturer();
        if ($course->lecturer_id !== $lecturer->lecturer_id) {
            throw ApiException::forbidden('NOT_COURSE_LECTURER', 'Anda bukan dosen pengampu mata kuliah ini.');
        }

        $method = $methodRaw === null ? 'BOTH' : Input::enum($methodRaw, 'attendanceMethod', Meeting::METHODS);
        if ($end->lte($start)) {
            throw ApiException::validation('Validasi permintaan gagal.', [['field' => 'endTime', 'message' => 'endTime harus setelah startTime.']]);
        }

        $meeting = DB::transaction(function () use ($course, $lecturer, $scheduleId, $topic, $start, $end, $method) {
            $meeting = Meeting::create([
                'meeting_id' => Ids::make('mtg'),
                'course_id' => $course->course_id,
                'schedule_id' => $scheduleId,
                'meeting_number' => (int) Meeting::where('course_id', $course->course_id)->max('meeting_number') + 1,
                'topic' => $topic,
                'lecturer_id' => $lecturer->lecturer_id,
                'start_time' => $start,
                'end_time' => $end,
                'status' => Meeting::ACTIVE,
                'attendance_open' => false,
                'attendance_method' => $method,
            ]);
            LecturerAttendance::create([
                'lecturer_attendance_id' => Ids::make('lat'),
                'meeting_id' => $meeting->meeting_id,
                'lecturer_id' => $lecturer->lecturer_id,
            ]);

            return $meeting;
        });

        return ApiResponse::created('Meeting berhasil dibuat.', $meeting->toApi());
    }

    // (tambahan) GET /meetings/{meetingId}  (Dosen pengampu, Prodi)
    public function show(Request $request, string $meetingId): JsonResponse
    {
        $actor = Actor::from($request);
        $meeting = self::find($meetingId);
        if (! $actor->isProdi()) {
            $this->ensureOwner($meeting, $actor);
        }

        return ApiResponse::ok('Meeting berhasil diambil.', $meeting->toApi());
    }

    // 2. PATCH /meetings/{meetingId}/attendance  (Dosen) -> pintu absensi
    public function updateAttendance(Request $request, string $meetingId): JsonResponse
    {
        $actor = Actor::from($request);
        $meeting = self::find($meetingId);
        $this->ensureOwner($meeting, $actor);

        $data = $request->attributes->get('jsonBody', []);
        $open = Input::optionalBool($data, 'attendanceOpen');
        $methodRaw = Input::optionalString($data, 'attendanceMethod');
        if ($open === null && $methodRaw === null) {
            throw ApiException::badRequest('MISSING_FIELD', 'Isi minimal salah satu: attendanceOpen atau attendanceMethod.', [
                ['field' => 'attendanceOpen', 'message' => 'Wajib diisi bila attendanceMethod kosong.'],
            ]);
        }
        $method = $methodRaw === null ? null : Input::enum($methodRaw, 'attendanceMethod', Meeting::METHODS);

        if ($meeting->isEnded()) {
            throw ApiException::rule('MEETING_ENDED', 'Meeting sudah berakhir, pengaturan pintu absensi tidak dapat diubah.');
        }

        if ($open !== null) {
            $meeting->attendance_open = $open;
        }
        if ($method !== null) {
            $meeting->attendance_method = $method;
        }
        $meeting->save();

        return ApiResponse::ok('Pengaturan pintu absensi berhasil diperbarui.', $meeting->toApi());
    }

    // 3. POST /meetings/{meetingId}/start  (Dosen) -> checkpoint 1
    public function start(Request $request, string $meetingId): JsonResponse
    {
        $actor = Actor::from($request);
        $meeting = self::find($meetingId);
        $this->ensureOwner($meeting, $actor);

        if ($meeting->isEnded()) {
            throw ApiException::rule('MEETING_ENDED', 'Meeting sudah berakhir.');
        }

        $record = $meeting->lecturerAttendance;
        if (! $record->is_present_at_start) { // idempotent: panggilan ulang tidak mengubah waktu checkpoint
            $record->update(['is_present_at_start' => true, 'started_at' => now()]);
        }

        return ApiResponse::ok('Checkpoint 1 (mulai kelas) berhasil dicatat.', $this->checkpointPayload($meeting, $record));
    }

    // 4. POST /meetings/{meetingId}/end  (Dosen) -> checkpoint 2
    public function end(Request $request, string $meetingId): JsonResponse
    {
        $actor = Actor::from($request);
        $meeting = self::find($meetingId);
        $this->ensureOwner($meeting, $actor);

        if ($meeting->status === Meeting::ENDED) {
            throw ApiException::rule('MEETING_ENDED', 'Meeting sudah berakhir.');
        }
        $record = $meeting->lecturerAttendance;
        if (! $record->is_present_at_start) {
            throw ApiException::rule('CHECKPOINT_1_REQUIRED', 'Kelas tidak dapat diakhiri sebelum checkpoint 1 (mulai kelas) dicatat.');
        }

        DB::transaction(function () use ($meeting, $record) {
            $record->update(['is_present_at_end' => true, 'ended_at' => now()]);
            $meeting->update(['status' => Meeting::ENDED, 'attendance_open' => false]);
        });

        return ApiResponse::ok('Checkpoint 2 (akhir kelas) berhasil dicatat. Meeting berakhir.', $this->checkpointPayload($meeting->refresh(), $record->refresh()));
    }

    /** @return array<string, mixed> */
    private function checkpointPayload(Meeting $meeting, LecturerAttendance $record): array
    {
        return $record->toApi() + ['meetingStatus' => $meeting->toApi()['status'], 'attendanceOpen' => $meeting->toApi()['attendanceOpen']];
    }

    // 5. GET /meetings/{meetingId}/lecturer-attendance  (Dosen pengampu, Prodi)
    public function lecturerAttendance(Request $request, string $meetingId): JsonResponse
    {
        $actor = Actor::from($request);
        $meeting = self::find($meetingId);
        if (! $actor->isProdi()) {
            $this->ensureOwner($meeting, $actor);
        }

        return ApiResponse::ok('Presensi dosen berhasil diambil.', $meeting->lecturerAttendance->toApi());
    }

    // 6. GET /meetings/{meetingId}/qr  (Dosen pengampu)
    public function qr(Request $request, string $meetingId): JsonResponse
    {
        $actor = Actor::from($request);
        $meeting = self::find($meetingId);
        $this->ensureOwner($meeting, $actor);

        if ($meeting->isEnded()) {
            throw ApiException::rule('MEETING_ENDED', 'Meeting sudah berakhir.');
        }
        if (! $meeting->attendance_open) {
            throw ApiException::rule('ATTENDANCE_CLOSED', 'Pintu absensi sedang tertutup.');
        }
        if ($meeting->attendance_method === 'BUTTON') {
            throw ApiException::rule('QR_NOT_ENABLED', 'Meeting ini memakai metode BUTTON, QR tidak tersedia.');
        }

        $qr = QrToken::current($meeting->meeting_id);

        return ApiResponse::ok('QR Code berhasil diambil.', [
            'meetingId' => $meeting->meeting_id,
            'qrToken' => $qr['qrToken'],
            'refreshIntervalSeconds' => $qr['refreshIntervalSeconds'],
            'expiresAt' => ApiResponse::iso(CarbonImmutable::createFromTimestampUTC($qr['expiresAt'])),
        ]);
    }
}
