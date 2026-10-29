<?php

namespace Tests\Feature\Presensi;

use App\Services\Presensi\AttendanceStatus;
use App\Services\Presensi\AttendanceStore;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DosenSesiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function store(): AttendanceStore
    {
        return app(AttendanceStore::class);
    }

    public function test_start_class_opens_session_and_generates_token(): void
    {
        $this->post(route('presensi.dosen.sesi.mulai', 1))->assertRedirect(route('presensi.dosen.sesi', 1));

        $state = $this->store()->state(1);
        $this->assertSame('berlangsung', $state['status']);
        $this->assertSame(6, strlen($state['token']));
    }

    public function test_finish_class_closes_session_and_marks_missing_students_alpa(): void
    {
        $this->post(route('presensi.dosen.sesi.mulai', 1));
        $this->post(route('presensi.mahasiswa.absen.store', 1), ['method' => 'button']); // Aditya hadir
        $this->post(route('presensi.dosen.sesi.selesai', 1));

        $this->assertSame('selesai', $this->store()->state(1)['status']);
        $this->assertSame(AttendanceStatus::Hadir, $this->store()->record(1, '115240001'));
        $this->assertSame(AttendanceStatus::Alpa, $this->store()->record(1, '115240002'));
    }

    public function test_lecturer_can_change_student_status_even_after_class_finished(): void
    {
        $this->post(route('presensi.dosen.sesi.mulai', 1));
        $this->post(route('presensi.dosen.sesi.selesai', 1));

        $this->post(route('presensi.dosen.sesi.mark', [1, '115240002']), ['status' => 'sakit'])
            ->assertSessionHas('status');

        $this->assertSame(AttendanceStatus::Sakit, $this->store()->record(1, '115240002'));
    }

    public function test_mark_rejects_invalid_status_and_unknown_student(): void
    {
        $this->post(route('presensi.dosen.sesi.mark', [1, '115240002']), ['status' => 'terbang'])
            ->assertSessionHasErrors('status');

        $this->post(route('presensi.dosen.sesi.mark', [1, '000000000']), ['status' => 'hadir'])
            ->assertNotFound();
    }

    public function test_reminder_appears_ten_minutes_after_end_snooze_hides_and_finish_stops_it(): void
    {
        // Sesi 1 selesai 11:20 -> reminder mulai 11:30
        Carbon::setTestNow(Carbon::today()->setTime(9, 40));
        $this->post(route('presensi.dosen.sesi.mulai', 1));

        Carbon::setTestNow(Carbon::today()->setTime(11, 29));
        $this->getJson(route('presensi.dosen.sesi.reminder', 1))->assertJson(['remind' => false]);

        Carbon::setTestNow(Carbon::today()->setTime(11, 30));
        $this->getJson(route('presensi.dosen.sesi.reminder', 1))->assertJson(['remind' => true]);

        // "Ingatkan nanti" -> tertunda 5 menit
        $this->post(route('presensi.dosen.sesi.tunda', 1));
        $this->getJson(route('presensi.dosen.sesi.reminder', 1))->assertJson(['remind' => false]);

        Carbon::setTestNow(Carbon::today()->setTime(11, 35));
        $this->getJson(route('presensi.dosen.sesi.reminder', 1))->assertJson(['remind' => true]);

        // Selesaikan kuliah -> reminder mati
        $this->post(route('presensi.dosen.sesi.selesai', 1));
        $this->getJson(route('presensi.dosen.sesi.reminder', 1))->assertJson(['remind' => false]);
    }

    public function test_no_reminder_if_class_never_started(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(12, 0));

        $this->getJson(route('presensi.dosen.sesi.reminder', 1))->assertJson(['remind' => false]);
    }
}
