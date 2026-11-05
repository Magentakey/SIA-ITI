<?php

namespace Tests\Feature\Presensi;

use Tests\TestCase;

class HalamanPresensiTest extends TestCase
{
    public function test_all_presensi_pages_render(): void
    {
        foreach ([
            '/presensi' => 'Kelas hari ini',
            '/presensi/riwayat' => 'Riwayat kehadiran Pemrograman Web',
            '/presensi/dosen' => 'Kelola pertemuan',
            '/presensi/dosen/meeting' => 'Presensi dosen',
            '/presensi/dosen/rekap' => 'Rekap mata kuliah',
            '/presensi/admin' => 'Laporan Kehadiran',
        ] as $url => $text) {
            $this->get($url)->assertOk()->assertSee($text);
        }
    }

    public function test_student_default_state_matches_design(): void
    {
        $this->get('/presensi')
            ->assertOk()
            ->assertSee('Sudah dipresensi dosen')
            ->assertSee('Belum dimulai')
            ->assertSee('90,91%', false)
            ->assertSee('Memenuhi')
            ->assertSee('Belum memenuhi')
            ->assertSee('Lihat riwayat Pemrograman Web');
    }

    public function test_student_web_has_no_self_attendance_button(): void
    {
        $this->get('/presensi')
            ->assertDontSee('Absen Hadir')
            ->assertDontSee('Pindai QR');
    }

    public function test_student_state_class_in_progress_not_yet_recorded(): void
    {
        $this->get('/presensi?state=berlangsung')
            ->assertOk()
            ->assertSee('Belum dipresensi')
            ->assertSee('Dosen belum mencatat kehadiran pertemuan 11')
            ->assertSee('90%', false);
    }

    public function test_student_state_recorded_absent(): void
    {
        $this->get('/presensi?state=alpa')
            ->assertOk()
            ->assertSee('Dicatat tidak hadir')
            ->assertSee('81,82%', false);
    }

    public function test_student_state_no_class_today(): void
    {
        $this->get('/presensi?state=kosong')
            ->assertOk()
            ->assertSee('Tidak ada kelas hari ini')
            ->assertSee('Kelas berikutnya: Sabtu, 7 Nov 2026');
    }

    public function test_student_state_loading_and_error(): void
    {
        $this->get('/presensi?state=loading')->assertOk()->assertSee('Memuat presensi');
        $this->get('/presensi?state=error')->assertOk()->assertSee('Gagal memuat presensi')->assertSee('Coba lagi');
    }

    public function test_unknown_state_falls_back_to_default(): void
    {
        $this->get('/presensi?state=ngawur')->assertOk()->assertSee('Sudah dipresensi dosen');
    }

    public function test_student_history_page_shows_summary(): void
    {
        $this->get('/presensi/riwayat')
            ->assertOk()
            ->assertSee('10 dari 11 pertemuan')
            ->assertSee('Memenuhi syarat ujian')
            ->assertSee('Presensi dicatat oleh dosen');
    }

    public function test_lecturer_meeting_in_progress(): void
    {
        $this->get('/presensi/dosen/meeting')
            ->assertOk()
            ->assertSee('Checkpoint 1')
            ->assertSee('Checkpoint 2')
            ->assertSee('Belum lengkap')
            ->assertSee('Area QR Code')
            ->assertSee('Kelas berakhir 10 menit lagi');
    }

    public function test_lecturer_meeting_ended_only_allows_manual_attendance(): void
    {
        $this->get('/presensi/dosen/meeting?state=berakhir')
            ->assertOk()
            ->assertSee('Kelas telah berakhir')
            ->assertSee('hanya dapat dicatat manual oleh dosen')
            ->assertDontSee('Area QR Code');
    }

    public function test_lecturer_recap_shows_course_average(): void
    {
        $this->get('/presensi/dosen/rekap')->assertOk()->assertSee('80%', false)->assertSee('Rata-rata kehadiran');
    }
}
