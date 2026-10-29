<?php

namespace App\Services\Presensi;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Penyimpanan SEMENTARA untuk modul presensi (T4).
 * Data master (mata kuliah, jadwal, mahasiswa) masih dummy karena modul T1/T2/T3 belum ada,
 * dan state presensi disimpan di session. Saat API T1-T3 siap, ganti isi class ini
 * (method publiknya sengaja dibuat sebagai "kontrak") tanpa mengubah controller/view.
 */
class AttendanceStore
{
    public const DEMO_NIM = '115240001'; // TODO: ganti dengan mahasiswa yang login (T1)

    public const LOW_ATTENDANCE_THRESHOLD = 75; // asumsi, belum diputuskan PO

    public const RESULT_OK = 'ok';

    public const RESULT_NOT_OPEN = 'not_open';

    public const RESULT_INVALID_TOKEN = 'invalid_token';

    public const RESULT_ALREADY = 'already';

    public const RESULT_UNKNOWN = 'unknown';

    private const HISTORY = [
        '115240001' => ['hadir', 'hadir', 'hadir', 'sakit', 'hadir', 'hadir', 'izin'],
        '115240002' => ['hadir', 'hadir', 'hadir', 'hadir', 'hadir', 'hadir', 'hadir'],
        '115240003' => ['hadir', 'alpa', 'hadir', 'hadir', 'sakit', 'hadir', 'hadir'],
        '112240001' => ['hadir', 'hadir', 'alpa', 'alpa', 'hadir', 'izin', 'hadir'],
        '113230001' => ['alpa', 'alpa', 'hadir', 'alpa', 'hadir', 'alpa', 'sakit'],
        '121250001' => ['hadir', 'hadir', 'hadir', 'hadir', 'izin', 'hadir', 'hadir'],
    ];

    /** @return array<string, string> nim => nama */
    public function students(): array
    {
        return [
            '115240001' => 'Aditya Pratama',
            '115240002' => 'Nadia Putri',
            '115240003' => 'Bima Saputra',
            '112240001' => 'Citra Lestari',
            '113230001' => 'Dewi Anggraini',
            '121250001' => 'Fajar Ramadhan',
        ];
    }

    /** @return array<int, array<string, mixed>> sesi kuliah hari ini (dummy) */
    public function sessions(): array
    {
        return [
            1 => ['id' => 1, 'course' => 'IF-215 A', 'name' => 'Sistem Operasi', 'lecturer' => 'Ir. Jaenudin', 'room' => 'D7', 'meeting' => 8, 'start' => '09:40', 'end' => '11:20'],
            2 => ['id' => 2, 'course' => 'IF-201 A', 'name' => 'Basis Data', 'lecturer' => 'Dr. Asep Pangestu, M.Kom', 'room' => 'Aula G', 'meeting' => 8, 'start' => '13:30', 'end' => '15:10'],
            3 => ['id' => 3, 'course' => 'HK-110 A', 'name' => 'Hukum Perdata', 'lecturer' => 'Prof. Siti Saodah, M.Kom, S.Pd', 'room' => 'LIK B', 'meeting' => 8, 'start' => '15:30', 'end' => '17:10'],
        ];
    }

    /** @return array<string, mixed>|null */
    public function session(int $id): ?array
    {
        return $this->sessions()[$id] ?? null;
    }

    /** @return array<string, array<string, mixed>> kode => info mata kuliah */
    public function courses(): array
    {
        $courses = [];
        foreach ($this->sessions() as $s) {
            $courses[$s['course']] = ['code' => $s['course'], 'name' => $s['name'], 'lecturer' => $s['lecturer']];
        }

        return $courses;
    }

    public function startsAt(array $session): Carbon
    {
        return now()->setTimeFromTimeString($session['start']);
    }

    public function endsAt(array $session): Carbon
    {
        return now()->setTimeFromTimeString($session['end']);
    }

    // ---- State sesi -------------------------------------------------------

    /** @return array{status:string, started_at:?string, ended_at:?string, token:?string, snoozed_until:?string} */
    public function state(int $id): array
    {
        return array_merge(
            ['status' => 'belum', 'started_at' => null, 'ended_at' => null, 'token' => null, 'snoozed_until' => null],
            $this->all()['sessions'][$id] ?? []
        );
    }

    public function startSession(int $id): void
    {
        $this->patch($id, [
            'status' => 'berlangsung',
            'started_at' => now()->toIso8601String(),
            'token' => strtoupper(Str::random(6)),
            'snoozed_until' => null,
        ]);
    }

    /** Menyelesaikan kuliah; mahasiswa yang belum tercatat otomatis Alpa (asumsi). */
    public function finishSession(int $id): void
    {
        foreach (array_keys($this->students()) as $nim) {
            if ($this->record($id, $nim) === null) {
                $this->mark($id, $nim, AttendanceStatus::Alpa);
            }
        }

        $this->patch($id, ['status' => 'selesai', 'ended_at' => now()->toIso8601String(), 'snoozed_until' => null]);
    }

    public function snooze(int $id): void
    {
        $this->patch($id, ['snoozed_until' => now()->addMinutes(ReminderPolicy::SNOOZE_MINUTES)->toIso8601String()]);
    }

    public function shouldRemind(int $id): bool
    {
        $session = $this->session($id);
        $state = $this->state($id);

        if ($session === null || $state['status'] !== 'berlangsung') {
            return false;
        }

        return ReminderPolicy::shouldRemind(
            $this->endsAt($session),
            false,
            $state['snoozed_until'] ? Carbon::parse($state['snoozed_until']) : null,
            now()
        );
    }

    // ---- Record presensi mahasiswa ---------------------------------------

    public function record(int $id, string $nim): ?AttendanceStatus
    {
        $value = $this->all()['records'][$id][$nim] ?? null;

        return $value === null ? null : AttendanceStatus::from($value);
    }

    public function mark(int $id, string $nim, AttendanceStatus $status): void
    {
        $all = $this->all();
        $all['records'][$id][$nim] = $status->value;
        session(['presensi' => $all]);
    }

    /** Absen mahasiswa. $method: "button" atau "qr" (qr wajib token yang cocok). */
    public function checkIn(int $id, string $nim, string $method, ?string $token = null): string
    {
        if ($this->session($id) === null || ! isset($this->students()[$nim])) {
            return self::RESULT_UNKNOWN;
        }

        $state = $this->state($id);

        if ($state['status'] !== 'berlangsung') {
            return self::RESULT_NOT_OPEN;
        }

        if ($method === 'qr' && ($token === null || strtoupper(trim($token)) !== $state['token'])) {
            return self::RESULT_INVALID_TOKEN;
        }

        if ($this->record($id, $nim) !== null) {
            return self::RESULT_ALREADY;
        }

        $this->mark($id, $nim, AttendanceStatus::Hadir);

        return self::RESULT_OK;
    }

    // ---- Rekap ------------------------------------------------------------

    /** @return array<int, string> */
    public function statusesFor(string $nim, string $courseCode): array
    {
        $statuses = self::HISTORY[$nim] ?? [];

        foreach ($this->sessions() as $s) {
            if ($s['course'] === $courseCode && ($r = $this->record($s['id'], $nim)) !== null) {
                $statuses[] = $r->value;
            }
        }

        return $statuses;
    }

    /** @return array<string, mixed> */
    public function courseRecap(string $courseCode): array
    {
        $rows = [];
        foreach ($this->students() as $nim => $name) {
            $rows[] = ['nim' => $nim, 'name' => $name, 'recap' => RecapCalculator::summarize($this->statusesFor($nim, $courseCode))];
        }

        $percentages = array_map(fn ($r) => $r['recap']['totalPercentage'], $rows);

        return [
            'rows' => $rows,
            'average' => RecapCalculator::average($percentages),
            'below' => count(array_filter($percentages, fn ($p) => $p < self::LOW_ATTENDANCE_THRESHOLD)),
        ];
    }

    // ---- internal ---------------------------------------------------------

    /** @return array{sessions: array<int, array<string, mixed>>, records: array<int, array<string, string>>} */
    private function all(): array
    {
        return session('presensi', ['sessions' => [], 'records' => []]);
    }

    private function patch(int $id, array $values): void
    {
        $all = $this->all();
        $all['sessions'][$id] = array_merge($this->state($id), $values);
        session(['presensi' => $all]);
    }
}
