<?php

namespace Tests\Feature\Presensi;

use Tests\TestCase;

class PresensiRouteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_presensi_root_redirects_to_mahasiswa_page(): void
    {
        $this->get('/presensi')->assertRedirect('/presensi/mahasiswa');
    }

    public function test_all_presensi_pages_render(): void
    {
        foreach ([
            route('presensi.mahasiswa.index') => 'Presensi Saya',
            route('presensi.mahasiswa.absen') => 'Absen Kuliah',
            route('presensi.dosen.index') => 'Sesi Kuliah Hari Ini',
            route('presensi.dosen.sesi', 1) => 'Sistem Operasi',
            route('presensi.dosen.rekap') => 'Rekap Presensi',
            route('presensi.admin.index') => 'Monitoring Presensi',
        ] as $url => $text) {
            $this->get($url)->assertOk()->assertSee($text);
        }
    }

    public function test_unknown_session_returns_404(): void
    {
        $this->get('/presensi/dosen/sesi/999')->assertNotFound();
        $this->postJson('/presensi/dosen/sesi/999/mulai')->assertNotFound();
    }
}
