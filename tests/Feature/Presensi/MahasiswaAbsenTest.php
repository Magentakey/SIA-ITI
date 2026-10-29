<?php

namespace Tests\Feature\Presensi;

use App\Services\Presensi\AttendanceStatus;
use App\Services\Presensi\AttendanceStore;
use Tests\TestCase;

class MahasiswaAbsenTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function store(): AttendanceStore
    {
        return app(AttendanceStore::class);
    }

    public function test_student_cannot_check_in_before_lecturer_starts_class(): void
    {
        $this->post(route('presensi.mahasiswa.absen.store', 1), ['method' => 'button'])
            ->assertRedirect(route('presensi.mahasiswa.absen'))
            ->assertSessionHas('error');

        $this->assertNull($this->store()->record(1, AttendanceStore::DEMO_NIM));
    }

    public function test_student_can_check_in_with_button_once(): void
    {
        $this->post(route('presensi.dosen.sesi.mulai', 1));

        $this->post(route('presensi.mahasiswa.absen.store', 1), ['method' => 'button'])
            ->assertSessionHas('status');
        $this->assertSame(AttendanceStatus::Hadir, $this->store()->record(1, AttendanceStore::DEMO_NIM));

        // absen kedua ditolak
        $this->post(route('presensi.mahasiswa.absen.store', 1), ['method' => 'button'])
            ->assertSessionHas('error');
    }

    public function test_qr_check_in_requires_valid_token(): void
    {
        $this->post(route('presensi.dosen.sesi.mulai', 1));
        $token = $this->store()->state(1)['token'];

        $this->post(route('presensi.mahasiswa.absen.store', 1), ['method' => 'qr', 'token' => 'SALAH1'])
            ->assertSessionHas('error');
        $this->assertNull($this->store()->record(1, AttendanceStore::DEMO_NIM));

        $this->post(route('presensi.mahasiswa.absen.store', 1), ['method' => 'qr', 'token' => strtolower($token)])
            ->assertSessionHas('status');
        $this->assertSame(AttendanceStatus::Hadir, $this->store()->record(1, AttendanceStore::DEMO_NIM));
    }

    public function test_invalid_method_is_rejected(): void
    {
        $this->post(route('presensi.mahasiswa.absen.store', 1), ['method' => 'hack'])
            ->assertSessionHasErrors('method');
    }

    public function test_check_in_closed_after_class_finished(): void
    {
        $this->post(route('presensi.dosen.sesi.mulai', 1));
        $this->post(route('presensi.dosen.sesi.selesai', 1));

        $this->post(route('presensi.mahasiswa.absen.store', 1), ['method' => 'button'])
            ->assertSessionHas('error');
    }
}
