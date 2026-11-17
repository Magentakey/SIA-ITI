<?php

namespace Database\Seeders;

use App\Models\T4\ApiUser;
use App\Models\T4\Attendance;
use App\Models\T4\Course;
use App\Models\T4\Lecturer;
use App\Models\T4\LecturerAttendance;
use App\Models\T4\Meeting;
use App\Models\T4\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Data uji API T4. ID bertipe <prefix>_<uuid> dengan uuid tetap supaya mudah dirujuk di Postman/dokumentasi.
 * Password akun dibaca dari T4_DEV_PASSWORD; bila kosong dibuat acak dan ditampilkan di terminal (SEC-INT-001).
 */
class T4Seeder extends Seeder
{
    /** uuid tetap: 00000000-0000-4000-8000-0000000000NN */
    public static function id(string $prefix, int $n): string
    {
        return sprintf('%s_00000000-0000-4000-8000-%012d', $prefix, $n);
    }

    public function run(): void
    {
        $password = config('t4.dev_password') ?: Str::password(16, symbols: false);
        $hash = Hash::make($password);

        $accounts = [
            // [identifier, nama, role]
            ['11520001', 'Budi Santoso', 'STUDENT'],
            ['11520002', 'Siti Aminah', 'STUDENT'],
            ['11520003', 'Andi Wijaya', 'STUDENT'],
            ['DSN001', 'Dr. Rina Kartika, M.Kom.', 'LECTURER'],
            ['DSN002', 'Agus Prasetyo, S.T., M.T.', 'LECTURER'],
            ['KPR001', 'Kaprodi Teknik Informatika', 'KAPRODI'],
            ['ADM001', 'Admin Akademik', 'ADMIN_ACADEMIC'],
        ];
        foreach ($accounts as $i => [$identifier, $name, $role]) {
            ApiUser::create(['user_id' => self::id('usr', $i + 1), 'identifier' => $identifier, 'full_name' => $name, 'role' => $role, 'password' => $hash]);
        }

        foreach ([1 => ['11520001', 'Budi Santoso'], 2 => ['11520002', 'Siti Aminah'], 3 => ['11520003', 'Andi Wijaya']] as $n => [$nim, $name]) {
            Student::create(['student_id' => self::id('std', $n), 'nim' => $nim, 'full_name' => $name]);
        }
        foreach ([1 => ['DSN001', 'Dr. Rina Kartika, M.Kom.'], 2 => ['DSN002', 'Agus Prasetyo, S.T., M.T.']] as $n => [$nip, $name]) {
            Lecturer::create(['lecturer_id' => self::id('lec', $n), 'nip' => $nip, 'full_name' => $name]);
        }

        Course::create(['course_id' => self::id('crs', 1), 'course_code' => 'IF201', 'course_name' => 'Pemrograman Web', 'lecturer_id' => self::id('lec', 1)]);
        Course::create(['course_id' => self::id('crs', 2), 'course_code' => 'IF203', 'course_name' => 'Basis Data', 'lecturer_id' => self::id('lec', 2)]);

        // Peserta kelas: IF201 = Budi, Siti. IF203 = Budi, Andi. (Andi TIDAK terdaftar di IF201.)
        foreach ([[1, 1], [1, 2], [2, 1], [2, 3]] as [$c, $s]) {
            DB::table('course_enrollments')->insert(['course_id' => self::id('crs', $c), 'student_id' => self::id('std', $s), 'created_at' => now(), 'updated_at' => now()]);
        }

        // Meeting. Skenario tiap Meeting dijelaskan di docs/T4-api.md.
        $meetings = [
            // n, course, no, status, open, method, start, end, checkpoint1, checkpoint2
            [1, 1, 5, 'ACTIVE', true, 'BOTH', now()->subHour(), now()->addYear(), false, false],   // siap presensi (QR/Button)
            [2, 1, 4, 'ACTIVE', true, 'BOTH', now()->subDays(2), now()->subDay(), false, false],  // endTime terlewat -> MEETING_ENDED
            [3, 1, 6, 'ACTIVE', false, 'BOTH', now()->subHour(), now()->addYear(), false, false], // pintu tertutup -> ATTENDANCE_CLOSED
            [4, 1, 1, 'ENDED', false, 'BOTH', now()->subDays(21), now()->subDays(21)->addHours(2), true, true],
            [5, 1, 2, 'ENDED', false, 'BOTH', now()->subDays(14), now()->subDays(14)->addHours(2), true, true],
            [6, 1, 3, 'ENDED', false, 'BOTH', now()->subDays(7), now()->subDays(7)->addHours(2), true, true],
            [7, 1, 7, 'ACTIVE', true, 'QR', now()->subHour(), now()->addYear(), true, false],     // sudah checkpoint 1, hanya QR
            [8, 2, 1, 'ACTIVE', true, 'BOTH', now()->subHour(), now()->addYear(), false, false],  // milik Dosen 2 (uji 403 non-pengampu)
        ];
        foreach ($meetings as [$n, $course, $no, $status, $open, $method, $start, $end, $cp1, $cp2]) {
            $lecturerId = self::id('lec', $course);
            Meeting::create([
                'meeting_id' => self::id('mtg', $n), 'course_id' => self::id('crs', $course), 'schedule_id' => 'sch_demo', 'meeting_number' => $no,
                'topic' => "Pertemuan {$no}", 'lecturer_id' => $lecturerId, 'start_time' => $start, 'end_time' => $end,
                'status' => $status, 'attendance_open' => $open, 'attendance_method' => $method,
            ]);
            LecturerAttendance::create([
                'lecturer_attendance_id' => self::id('lat', $n), 'meeting_id' => self::id('mtg', $n), 'lecturer_id' => $lecturerId,
                'is_present_at_start' => $cp1, 'is_present_at_end' => $cp2,
                'started_at' => $cp1 ? $start : null, 'ended_at' => $cp2 ? $end : null,
            ]);
        }

        // Catatan presensi awal (att_..0001 dst dipakai untuk uji koreksi)
        $records = [
            // n, meeting, student, status, method
            [1, 4, 1, 'ATTENDED', 'QR'], [2, 4, 2, 'SICK', 'MANUAL'],
            [3, 5, 1, 'ATTENDED', 'QR'],
            [4, 6, 1, 'ATTENDED', 'BUTTON'], [5, 6, 2, 'ATTENDED', 'QR'],
            [6, 2, 1, 'ATTENDED', 'QR'],
        ];
        foreach ($records as [$n, $m, $s, $status, $method]) {
            Attendance::create([
                'attendance_id' => self::id('att', $n), 'meeting_id' => self::id('mtg', $m), 'student_id' => self::id('std', $s),
                'status' => $status, 'method' => $method, 'recorded_at' => now()->subDays(3),
                'confirmed_by' => $method === 'MANUAL' ? self::id('usr', 4) : null,
            ]);
        }

        $this->command?->newLine();
        $this->command?->info('Akun uji T4 (username = NIM/NIP, password sama untuk semua):');
        $this->command?->line('  password : '.$password);
        foreach ($accounts as [$identifier, $name, $role]) {
            $this->command?->line(sprintf('  %-8s %-15s %s', $identifier, $role, $name));
        }
    }
}
