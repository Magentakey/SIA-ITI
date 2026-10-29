<?php

namespace App\Http\Controllers\Presensi;

use App\Http\Controllers\Controller;
use App\Services\Presensi\AttendanceStore;
use App\Services\Presensi\RecapCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MahasiswaPresensiController extends Controller
{
    public function __construct(private AttendanceStore $store) {}

    /** Rekap presensi mahasiswa per mata kuliah. */
    public function index(): View
    {
        $nim = AttendanceStore::DEMO_NIM;

        $rows = [];
        foreach ($this->store->courses() as $code => $course) {
            $rows[] = [
                'course' => $course,
                'recap' => RecapCalculator::summarize($this->store->statusesFor($nim, $code)),
            ];
        }

        return view('presensi.mahasiswa.index', [
            'student' => $this->store->students()[$nim],
            'nim' => $nim,
            'rows' => $rows,
            'threshold' => AttendanceStore::LOW_ATTENDANCE_THRESHOLD,
        ]);
    }

    /** Halaman absen: daftar sesi hari ini. */
    public function absen(): View
    {
        $nim = AttendanceStore::DEMO_NIM;

        $sessions = array_map(fn ($s) => $s + [
            'state' => $this->store->state($s['id']),
            'record' => $this->store->record($s['id'], $nim),
        ], array_values($this->store->sessions()));

        return view('presensi.mahasiswa.absen', ['sessions' => $sessions]);
    }

    /** Submit absen (tombol biasa atau QR/token). */
    public function store(Request $request, int $session): RedirectResponse
    {
        $data = $request->validate([
            'method' => ['required', 'in:button,qr'],
            'token' => ['nullable', 'string', 'max:20'],
        ]);

        $result = $this->store->checkIn($session, AttendanceStore::DEMO_NIM, $data['method'], $data['token'] ?? null);

        if ($result === AttendanceStore::RESULT_UNKNOWN) {
            abort(404);
        }

        $messages = [
            AttendanceStore::RESULT_OK => ['status', 'Absen berhasil dicatat.'],
            AttendanceStore::RESULT_NOT_OPEN => ['error', 'Absen belum dibuka atau sudah ditutup oleh dosen.'],
            AttendanceStore::RESULT_INVALID_TOKEN => ['error', 'Kode QR/token tidak valid.'],
            AttendanceStore::RESULT_ALREADY => ['error', 'Kamu sudah absen pada sesi ini.'],
        ];

        [$key, $text] = $messages[$result];

        return redirect()->route('presensi.mahasiswa.absen')->with($key, $text);
    }
}
