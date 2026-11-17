<?php

namespace App\Services\Presensi;

use App\Models\T4\Attendance;
use App\Models\T4\Course;
use App\Models\T4\Meeting;
use Illuminate\Support\Collection;

/**
 * Rekap kehadiran.
 *  - totalSessions = Meeting mata kuliah yang sudah berlangsung: sudah checkpoint 1, atau diakhiri, atau endTime terlewat.
 *  - absentSessions = totalSessions - attended - sick - permission (Meeting tanpa catatan dihitung alpa).
 *  - totalPercentage = attendedSessions / totalSessions * 100 (hanya status ATTENDED; sakit/izin belum diputuskan, SRS 7.3 no. 3).
 */
class RecapService
{
    /** @return Collection<int, string> meetingId yang sudah berlangsung */
    public function heldMeetingIds(Course $course): Collection
    {
        return Meeting::query()
            ->where('course_id', $course->course_id)
            ->where(function ($q) {
                $q->where('status', Meeting::ENDED)
                    ->orWhere('end_time', '<=', now())
                    ->orWhereHas('lecturerAttendance', fn ($l) => $l->where('is_present_at_start', true));
            })
            ->pluck('meeting_id');
    }

    /**
     * @param  Collection<string, Collection<int, Attendance>>  $byStudent  hasil groupBy student_id
     * @return array<string, mixed>
     */
    public function row(Collection $heldIds, ?Collection $records): array
    {
        $records = ($records ?? collect())->whereIn('meeting_id', $heldIds->all());
        $count = fn (string $status) => $records->where('status', $status)->count();

        $total = $heldIds->count();
        $attended = $count('ATTENDED');
        $sick = $count('SICK');
        $permission = $count('PERMISSION');
        $absent = max(0, $total - $attended - $sick - $permission);
        $percentage = $total > 0 ? round($attended / $total * 100, 2) : 0.0;

        return [
            'totalSessions' => $total,
            'attendedDetail' => [
                'attendedSessions' => $attended,
                'sickSessions' => $sick,
                'absentSessions' => $absent,
                'permissionSessions' => $permission,
            ],
            'totalPercentage' => $percentage,
            'isEligibleForExam' => $percentage >= (float) config('t4.exam_threshold'),
        ];
    }

    /** Rekap satu mahasiswa untuk satu mata kuliah. @return array<string, mixed> */
    public function forStudent(Course $course, string $studentId): array
    {
        $held = $this->heldMeetingIds($course);
        $records = Attendance::where('student_id', $studentId)->whereIn('meeting_id', $held)->get();

        return $this->row($held, $records);
    }

    /**
     * Rekap seluruh mahasiswa terdaftar pada mata kuliah.
     *
     * @return array{rows: Collection<int, array<string, mixed>>, average: float, totalMeetings: int}
     */
    public function forCourse(Course $course): array
    {
        $held = $this->heldMeetingIds($course);
        $byStudent = Attendance::whereIn('meeting_id', $held)->get()->groupBy('student_id');

        $rows = $course->students()->orderBy('nim')->get()->map(fn ($student) => [
            'studentId' => $student->student_id,
            'nim' => $student->nim,
            'fullName' => $student->full_name,
        ] + $this->row($held, $byStudent->get($student->student_id)))->values();

        $average = $rows->isEmpty() ? 0.0 : round($rows->avg('totalPercentage'), 2);

        return ['rows' => $rows, 'average' => $average, 'totalMeetings' => $held->count()];
    }
}
