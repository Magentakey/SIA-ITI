<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\T4\Course;
use App\Models\T4\Student;
use App\Services\Presensi\Actor;
use App\Services\Presensi\RecapService;
use App\Support\Api\ApiException;
use App\Support\Api\ApiResponse;
use App\Support\Api\Ids;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecapController extends Controller
{
    public function __construct(private readonly RecapService $recap) {}

    private function findCourse(string $courseId): Course
    {
        Ids::assert('crs', $courseId, 'courseId');

        return Course::find($courseId) ?? throw ApiException::notFound('COURSE_NOT_FOUND', 'Mata kuliah tidak ditemukan.');
    }

    private function findStudent(string $studentId): Student
    {
        Ids::assert('std', $studentId, 'studentId');

        return Student::find($studentId) ?? throw ApiException::notFound('STUDENT_NOT_FOUND', 'Mahasiswa tidak ditemukan.');
    }

    /** Mahasiswa hanya boleh melihat rekap miliknya sendiri (SEC-002). */
    private function ensureStudentAccess(Actor $actor, Student $student): void
    {
        if ($actor->hasRole('STUDENT') && $actor->student()->student_id !== $student->student_id) {
            throw ApiException::forbidden('NOT_OWN_DATA', 'Mahasiswa hanya dapat melihat rekap miliknya sendiri.');
        }
    }

    // 10. GET /attendance/recap/student/{id}?courseId=  (Mahasiswa, Dosen)
    public function student(Request $request, string $id): JsonResponse
    {
        $actor = Actor::from($request);
        $student = $this->findStudent($id);
        $this->ensureStudentAccess($actor, $student);

        $courses = Course::query()
            ->whereIn('course_id', fn ($q) => $q->select('course_id')->from('course_enrollments')->where('student_id', $student->student_id))
            ->when($request->query('courseId'), fn ($q, $courseId) => $q->where('course_id', Ids::assert('crs', $courseId, 'courseId')))
            ->orderBy('course_code')
            ->get();

        return ApiResponse::ok('Rekap presensi mahasiswa berhasil diambil.', [
            'studentId' => $student->student_id,
            'nim' => $student->nim,
            'fullName' => $student->full_name,
            'courses' => $courses->map(fn (Course $c) => [
                'courseId' => $c->course_id,
                'courseCode' => $c->course_code,
                'courseName' => $c->course_name,
            ] + $this->recap->forStudent($c, $student->student_id))->values(),
        ]);
    }

    // 11. GET /attendance/recap/course/{id}?page=&limit=  (Dosen pengampu, Prodi)
    public function course(Request $request, string $id): JsonResponse
    {
        $actor = Actor::from($request);
        $course = $this->findCourse($id);
        if (! $actor->isProdi() && $course->lecturer_id !== $actor->lecturer()->lecturer_id) {
            throw ApiException::forbidden('NOT_COURSE_LECTURER', 'Anda bukan dosen pengampu mata kuliah ini.');
        }

        [$page, $limit] = $this->paging($request);
        $result = $this->recap->forCourse($course);
        $total = $result['rows']->count();

        return ApiResponse::ok('Rekap presensi mata kuliah berhasil diambil.', [
            'courseId' => $course->course_id,
            'courseCode' => $course->course_code,
            'courseName' => $course->course_name,
            'totalMeetings' => $result['totalMeetings'],
            'averagePercentage' => $result['average'],
            'students' => $result['rows']->slice(($page - 1) * $limit, $limit)->values(),
        ], [
            'page' => $page,
            'limit' => $limit,
            'totalItems' => $total,
            'totalPages' => (int) max(1, ceil($total / $limit)),
        ]);
    }

    // (tambahan, kompatibel kontrak SIA-API-INT v1.1 bagian 6.4) GET /attendances/summary?studentId=&classOfferingId=
    // classOfferingId diperlakukan sebagai courseId. Dikonsumsi Tim 5 untuk kelayakan ujian.
    public function summary(Request $request): JsonResponse
    {
        $actor = Actor::from($request);
        foreach (['studentId', 'classOfferingId'] as $param) {
            if (! is_string($request->query($param)) || $request->query($param) === '') {
                throw ApiException::badRequest('MISSING_FIELD', "Query {$param} wajib diisi.", [['field' => $param, 'message' => 'Wajib diisi.']]);
            }
        }
        $student = $this->findStudent($request->query('studentId'));
        $this->ensureStudentAccess($actor, $student);
        $course = $this->findCourse($request->query('classOfferingId'));
        $row = $this->recap->forStudent($course, $student->student_id);

        return ApiResponse::ok('Rekap presensi mahasiswa berhasil diambil.', [
            'studentId' => $student->student_id,
            'classOfferingId' => $course->course_id,
            'totalSessionsHeld' => $row['totalSessions'],
            'attendedSessions' => $row['attendedDetail']['attendedSessions'],
            'sickSessions' => $row['attendedDetail']['sickSessions'],
            'absentSessions' => $row['attendedDetail']['absentSessions'],
            'permissionSessions' => $row['attendedDetail']['permissionSessions'],
            'attendancePercentage' => $row['totalPercentage'],
            'isEligibleForExam' => $row['isEligibleForExam'],
        ]);
    }

    /** @return array{0:int,1:int} */
    private function paging(Request $request): array
    {
        $out = [];
        foreach (['page' => [1, null], 'limit' => [10, 100]] as $key => [$default, $max]) {
            $raw = $request->query($key);
            if ($raw === null) {
                $out[] = $default;

                continue;
            }
            if (! is_string($raw) || ! ctype_digit($raw) || (int) $raw < 1) {
                throw ApiException::badRequest('INVALID_QUERY', "Query {$key} harus bilangan bulat >= 1.", [['field' => $key, 'message' => 'Harus bilangan bulat >= 1.']]);
            }
            $out[] = $max ? min((int) $raw, $max) : (int) $raw;
        }

        return $out;
    }
}
