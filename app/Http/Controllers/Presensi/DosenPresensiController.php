<?php

namespace App\Http\Controllers\Presensi;

use App\Http\Controllers\Controller;
use App\Services\Presensi\AttendanceStatus;
use App\Services\Presensi\AttendanceStore;
use App\Services\Presensi\RecapCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DosenPresensiController extends Controller
{
    public function __construct(private AttendanceStore $store) {}

    /** Daftar sesi kuliah hari ini. */
    public function index(): View
    {
        $sessions = array_map(fn ($s) => $s + ['state' => $this->store->state($s['id'])], array_values($this->store->sessions()));

        return view('presensi.dosen.index', ['sessions' => $sessions]);
    }

    /** Kelola satu sesi: mulai/selesai, QR, ubah status mahasiswa. */
    public function show(int $session): View
    {
        $data = $this->findOrFail($session);

        $students = [];
        foreach ($this->store->students() as $nim => $name) {
            $students[] = ['nim' => $nim, 'name' => $name, 'status' => $this->store->record($session, $nim)];
        }

        return view('presensi.dosen.sesi', [
            'session' => $data,
            'state' => $this->store->state($session),
            'students' => $students,
            'statuses' => AttendanceStatus::cases(),
        ]);
    }

    /** Cek 1: mulai kuliah (membuka absen). */
    public function start(int $session): RedirectResponse
    {
        $this->findOrFail($session);

        if ($this->store->state($session)['status'] === 'belum') {
            $this->store->startSession($session);
        }

        return redirect()->route('presensi.dosen.sesi', $session)->with('status', 'Kuliah dimulai, absen mahasiswa dibuka.');
    }

    /** Cek 2: selesai kuliah (menutup absen & mematikan reminder). */
    public function finish(int $session): RedirectResponse
    {
        $this->findOrFail($session);

        if ($this->store->state($session)['status'] === 'berlangsung') {
            $this->store->finishSession($session);
        }

        return redirect()->route('presensi.dosen.sesi', $session)->with('status', 'Kuliah diselesaikan, absen ditutup.');
    }

    /** "Ingatkan nanti". */
    public function snooze(int $session): RedirectResponse
    {
        $this->findOrFail($session);
        $this->store->snooze($session);

        return redirect()->route('presensi.dosen.sesi', $session)->with('status', 'Pengingat ditunda.');
    }

    /** Dipolling oleh halaman sesi untuk memunculkan reminder. */
    public function reminder(int $session): JsonResponse
    {
        $this->findOrFail($session);

        return response()->json(['remind' => $this->store->shouldRemind($session)]);
    }

    /** Dosen mengubah status kehadiran mahasiswa (juga setelah kuliah selesai). */
    public function mark(Request $request, int $session, string $nim): RedirectResponse
    {
        $this->findOrFail($session);

        if (! isset($this->store->students()[$nim])) {
            abort(404);
        }

        $data = $request->validate(['status' => ['required', Rule::enum(AttendanceStatus::class)]]);

        $this->store->mark($session, $nim, AttendanceStatus::from($data['status']));

        return redirect()->route('presensi.dosen.sesi', $session)->with('status', 'Status kehadiran diperbarui.');
    }

    /** Rekap per mata kuliah. */
    public function rekap(): View
    {
        $courses = [];
        foreach ($this->store->courses() as $code => $course) {
            $courses[] = $course + ['recap' => $this->store->courseRecap($code)];
        }

        return view('presensi.dosen.rekap', ['courses' => $courses, 'threshold' => AttendanceStore::LOW_ATTENDANCE_THRESHOLD]);
    }

    /** @return array<string, mixed> */
    private function findOrFail(int $session): array
    {
        return $this->store->session($session) ?? abort(404);
    }
}
