<?php

namespace Tests\Feature\Api;

use App\Models\T4\AttendanceAudit;
use App\Services\Presensi\QrToken;
use App\Services\Presensi\RecapService;
use Database\Seeders\T4Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\TestCase;

/**
 * Uji kontrak API T4 (Tugas-09): status code, envelope, role, dan aturan bisnis.
 * Data dari T4Seeder; ID Meeting mtg_..0001 s.d. 0008 dijelaskan di docs/T4-api.md.
 */
class PresensiApiTest extends TestCase
{
    use RefreshDatabase;

    private const API = '/api/v1';

    private string $password;

    protected function setUp(): void
    {
        parent::setUp();
        $this->password = Str::password(14, symbols: false);
        config(['t4.dev_password' => $this->password]);
        $this->seed(T4Seeder::class);
    }

    private function mtg(int $n): string
    {
        return T4Seeder::id('mtg', $n);
    }

    private function token(string $identifier): string
    {
        return $this->postJson(self::API.'/auth/login', ['username' => $identifier, 'password' => $this->password])
            ->assertOk()->json('data.accessToken');
    }

    private function call_(string $method, string $uri, ?string $as = null, ?array $data = null, array $headers = []): TestResponse
    {
        if ($as !== null) {
            $headers['Authorization'] = 'Bearer '.$this->token($as);
        }

        return $this->json($method, self::API.$uri, $data ?? [], $headers);
    }

    /** Pastikan envelope error standar dan kode machine-readable. */
    private function assertError(TestResponse $r, int $status, string $code): void
    {
        $r->assertStatus($status)->assertJson([
            'success' => false, 'code' => $status, 'error' => ['code' => $code],
        ])->assertJsonStructure(['success', 'code', 'message', 'error' => ['type', 'code', 'details']]);
        $this->assertStringNotContainsString('trace', strtolower($r->getContent()));
    }

    private function assertSuccess(TestResponse $r, int $status): void
    {
        $r->assertStatus($status)->assertJson(['success' => true, 'code' => $status])
            ->assertJsonStructure(['success', 'code', 'message', 'data', 'meta']);
    }

    // ------------------------------------------------------------------ Auth (401 / 403 umum)

    public function test_semua_endpoint_tanpa_token_ditolak_401(): void
    {
        $m = $this->mtg(1);
        $endpoints = [
            ['post', '/meetings'], ['patch', "/meetings/{$m}/attendance"], ['post', "/meetings/{$m}/start"],
            ['post', "/meetings/{$m}/end"], ['get', "/meetings/{$m}/lecturer-attendance"], ['get', "/meetings/{$m}/qr"],
            ['post', '/attendance/submit'], ['post', "/meetings/{$m}/attendance/manual"],
            ['put', '/attendance/'.T4Seeder::id('att', 1).'/correction'],
            ['get', '/attendance/recap/student/'.T4Seeder::id('std', 1)], ['get', '/attendance/recap/course/'.T4Seeder::id('crs', 1)],
        ];
        foreach ($endpoints as [$method, $uri]) {
            $this->assertError($this->call_($method, $uri), 401, 'TOKEN_MISSING');
        }
    }

    public function test_token_rusak_atau_kedaluwarsa_ditolak_401(): void
    {
        $this->assertError($this->call_('get', '/meetings/'.$this->mtg(1).'/qr', null, null, ['Authorization' => 'Bearer abc.def.ghi']), 401, 'TOKEN_INVALID');

        config(['t4.jwt.ttl' => -10]);
        $expired = $this->token('DSN001');
        $this->assertError($this->call_('get', '/meetings/'.$this->mtg(1).'/qr', null, null, ['Authorization' => "Bearer {$expired}"]), 401, 'TOKEN_EXPIRED');
    }

    public function test_login_bentuk_respons_dan_kredensial_salah(): void
    {
        $r = $this->postJson(self::API.'/auth/login', ['username' => '11520001', 'password' => $this->password]);
        $this->assertSuccess($r, 200);
        $r->assertJsonPath('data.tokenType', 'Bearer')->assertJsonPath('data.user.roles', ['STUDENT']);
        $this->assertError($this->postJson(self::API.'/auth/login', ['username' => '11520001', 'password' => 'salah']), 401, 'INVALID_CREDENTIALS');
        $this->assertError($this->postJson(self::API.'/auth/login', ['username' => '11520001']), 400, 'MISSING_FIELD');
    }

    // ------------------------------------------------------------------ 1. POST /meetings

    public function test_dosen_membuat_meeting_201(): void
    {
        $r = $this->call_('post', '/meetings', 'DSN001', [
            'courseId' => T4Seeder::id('crs', 1), 'startTime' => '2026-12-01T01:00:00Z', 'endTime' => '2026-12-01T03:30:00Z',
            'scheduleId' => 'sch_x', 'topic' => 'Pengenalan', 'attendanceMethod' => 'qr',
        ]);
        $this->assertSuccess($r, 201);
        $r->assertJsonPath('data.status', 'ACTIVE')->assertJsonPath('data.attendanceOpen', false)
            ->assertJsonPath('data.attendanceMethod', 'QR')->assertJsonPath('data.meetingNumber', 8)
            ->assertJsonPath('data.startTime', '2026-12-01T01:00:00Z');

        // LecturerAttendance ikut dibuat (semua false)
        $this->call_('get', '/meetings/'.$r->json('data.meetingId').'/lecturer-attendance', 'DSN001')
            ->assertOk()->assertJsonPath('data.isPresent', false);
    }

    public function test_buat_meeting_validasi(): void
    {
        $base = ['courseId' => T4Seeder::id('crs', 1), 'startTime' => '2026-12-01T01:00:00Z', 'endTime' => '2026-12-01T03:00:00Z'];
        $this->assertError($this->call_('post', '/meetings', 'DSN001', array_diff_key($base, ['endTime' => 1])), 400, 'MISSING_FIELD');
        $this->assertError($this->call_('post', '/meetings', 'DSN001', ['startTime' => '01-12-2026'] + $base), 400, 'INVALID_DATETIME');
        $this->assertError($this->call_('post', '/meetings', 'DSN001', ['courseId' => 'abc'] + $base), 400, 'INVALID_ID_FORMAT');
        $this->assertError($this->call_('post', '/meetings', 'DSN001', ['courseId' => T4Seeder::id('crs', 9)] + $base), 404, 'COURSE_NOT_FOUND');
        $this->assertError($this->call_('post', '/meetings', 'DSN001', ['endTime' => '2026-12-01T00:00:00Z'] + $base), 422, 'VALIDATION_FAILED');
        $this->assertError($this->call_('post', '/meetings', 'DSN001', ['attendanceMethod' => 'FACE'] + $base), 422, 'VALIDATION_FAILED');
        $this->assertError($this->call_('post', '/meetings', 'DSN002', $base), 403, 'NOT_COURSE_LECTURER');
        $this->assertError($this->call_('post', '/meetings', '11520001', $base), 403, 'ROLE_NOT_ALLOWED');
        $this->assertError($this->call('POST', self::API.'/meetings', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$this->token('DSN001'), 'CONTENT_TYPE' => 'application/json'], '{rusak'), 400, 'INVALID_JSON');
    }

    // ------------------------------------------------------------------ 2. PATCH pintu absensi

    public function test_atur_pintu_absensi(): void
    {
        $r = $this->call_('patch', '/meetings/'.$this->mtg(3).'/attendance', 'DSN001', ['attendanceOpen' => true, 'attendanceMethod' => 'button']);
        $this->assertSuccess($r, 200);
        $r->assertJsonPath('data.attendanceOpen', true)->assertJsonPath('data.attendanceMethod', 'BUTTON');
    }

    public function test_atur_pintu_absensi_error(): void
    {
        $url = '/meetings/'.$this->mtg(3).'/attendance';
        $this->assertError($this->call_('patch', $url, 'DSN001', []), 400, 'MISSING_FIELD');
        $this->assertError($this->call_('patch', $url, 'DSN001', ['attendanceOpen' => 'ya']), 400, 'INVALID_FIELD_TYPE');
        $this->assertError($this->call_('patch', $url, 'DSN001', ['attendanceMethod' => 'FACE']), 422, 'VALIDATION_FAILED');
        $this->assertError($this->call_('patch', $url, 'DSN002', ['attendanceOpen' => true]), 403, 'NOT_MEETING_LECTURER');
        $this->assertError($this->call_('patch', '/meetings/'.$this->mtg(4).'/attendance', 'DSN001', ['attendanceOpen' => true]), 422, 'MEETING_ENDED');
        $this->assertError($this->call_('patch', '/meetings/'.T4Seeder::id('mtg', 99).'/attendance', 'DSN001', ['attendanceOpen' => true]), 404, 'MEETING_NOT_FOUND');
        $this->assertError($this->call_('patch', '/meetings/xyz/attendance', 'DSN001', ['attendanceOpen' => true]), 400, 'INVALID_ID_FORMAT');
    }

    // ------------------------------------------------------------------ 3-5. start, end, lecturer-attendance

    public function test_alur_dua_checkpoint_dosen(): void
    {
        $m = $this->mtg(1);
        $this->assertError($this->call_('post', "/meetings/{$m}/end", 'DSN001'), 422, 'CHECKPOINT_1_REQUIRED');

        $start = $this->call_('post', "/meetings/{$m}/start", 'DSN001');
        $this->assertSuccess($start, 200);
        $start->assertJsonPath('data.isPresentAtStart', true)->assertJsonPath('data.isPresent', false);
        $this->call_('post', "/meetings/{$m}/start", 'DSN001')->assertOk(); // idempotent

        $end = $this->call_('post', "/meetings/{$m}/end", 'DSN001');
        $this->assertSuccess($end, 200);
        $end->assertJsonPath('data.isPresent', true)->assertJsonPath('data.meetingStatus', 'ENDED')->assertJsonPath('data.attendanceOpen', false);

        $this->assertError($this->call_('post', "/meetings/{$m}/end", 'DSN001'), 422, 'MEETING_ENDED');
        $this->assertError($this->call_('post', "/meetings/{$m}/start", 'DSN001'), 422, 'MEETING_ENDED');
    }

    public function test_start_end_hanya_dosen_pengampu(): void
    {
        $this->assertError($this->call_('post', '/meetings/'.$this->mtg(1).'/start', 'DSN002'), 403, 'NOT_MEETING_LECTURER');
        $this->assertError($this->call_('post', '/meetings/'.$this->mtg(1).'/end', '11520001'), 403, 'ROLE_NOT_ALLOWED');
        $this->assertError($this->call_('post', '/meetings/'.T4Seeder::id('mtg', 99).'/start', 'DSN001'), 404, 'MEETING_NOT_FOUND');
    }

    public function test_lecturer_attendance_akses(): void
    {
        $url = '/meetings/'.$this->mtg(4).'/lecturer-attendance';
        $r = $this->call_('get', $url, 'DSN001');
        $this->assertSuccess($r, 200);
        $r->assertJsonPath('data.isPresentAtStart', true)->assertJsonPath('data.isPresentAtEnd', true)->assertJsonPath('data.isPresent', true)
            ->assertJsonStructure(['data' => ['lecturerAttendanceId', 'meetingId', 'lecturerId', 'isPresent', 'reminderSent']]);
        $this->call_('get', $url, 'KPR001')->assertOk();
        $this->call_('get', $url, 'ADM001')->assertOk();
        $this->assertError($this->call_('get', $url, 'DSN002'), 403, 'NOT_MEETING_LECTURER');
        $this->assertError($this->call_('get', $url, '11520001'), 403, 'ROLE_NOT_ALLOWED');
    }

    // ------------------------------------------------------------------ 6. QR

    public function test_qr_dosen(): void
    {
        $r = $this->call_('get', '/meetings/'.$this->mtg(1).'/qr', 'DSN001');
        $this->assertSuccess($r, 200);
        $r->assertJsonPath('data.refreshIntervalSeconds', 30)->assertJsonStructure(['data' => ['meetingId', 'qrToken', 'expiresAt']]);
        $this->assertTrue(QrToken::isValid($this->mtg(1), $r->json('data.qrToken')));
    }

    public function test_qr_error(): void
    {
        $this->assertError($this->call_('get', '/meetings/'.$this->mtg(3).'/qr', 'DSN001'), 422, 'ATTENDANCE_CLOSED');
        $this->assertError($this->call_('get', '/meetings/'.$this->mtg(2).'/qr', 'DSN001'), 422, 'MEETING_ENDED');
        $this->assertError($this->call_('get', '/meetings/'.$this->mtg(1).'/qr', 'DSN002'), 403, 'NOT_MEETING_LECTURER');
        $this->assertError($this->call_('get', '/meetings/'.$this->mtg(1).'/qr', '11520001'), 403, 'ROLE_NOT_ALLOWED');
        $this->call_('patch', '/meetings/'.$this->mtg(1).'/attendance', 'DSN001', ['attendanceMethod' => 'BUTTON'])->assertOk();
        $this->assertError($this->call_('get', '/meetings/'.$this->mtg(1).'/qr', 'DSN001'), 422, 'QR_NOT_ENABLED');
    }

    // ------------------------------------------------------------------ 7. POST /attendance/submit

    public function test_mahasiswa_presensi_qr_201(): void
    {
        $qr = QrToken::current($this->mtg(1))['qrToken'];
        $r = $this->call_('post', '/attendance/submit', '11520001', ['meetingId' => $this->mtg(1), 'method' => 'QR', 'qrToken' => $qr]);
        $this->assertSuccess($r, 201);
        $r->assertJsonPath('data.status', 'ATTENDED')->assertJsonPath('data.method', 'QR')
            ->assertJsonPath('data.studentId', T4Seeder::id('std', 1))->assertJsonStructure(['data' => ['attendanceId', 'timestamp']]);
    }

    public function test_mahasiswa_presensi_button_201_dan_studentid_dari_token(): void
    {
        // studentId di body diabaikan; identitas dari token (Siti)
        $r = $this->call_('post', '/attendance/submit', '11520002', ['meetingId' => $this->mtg(1), 'method' => 'button', 'studentId' => T4Seeder::id('std', 1)]);
        $this->assertSuccess($r, 201);
        $r->assertJsonPath('data.studentId', T4Seeder::id('std', 2))->assertJsonPath('data.method', 'BUTTON');
    }

    public function test_presensi_ganda_ditolak_422(): void
    {
        $body = ['meetingId' => $this->mtg(1), 'method' => 'BUTTON'];
        $this->call_('post', '/attendance/submit', '11520001', $body)->assertCreated();
        $this->assertError($this->call_('post', '/attendance/submit', '11520001', $body), 422, 'ATTENDANCE_DUPLICATE');
    }

    public function test_presensi_mandiri_aturan_bisnis_422(): void
    {
        $this->assertError($this->call_('post', '/attendance/submit', '11520002', ['meetingId' => $this->mtg(2), 'method' => 'BUTTON']), 422, 'MEETING_ENDED');   // endTime lewat
        $this->assertError($this->call_('post', '/attendance/submit', '11520002', ['meetingId' => $this->mtg(4), 'method' => 'BUTTON']), 422, 'MEETING_ENDED');   // status Ended
        $this->assertError($this->call_('post', '/attendance/submit', '11520002', ['meetingId' => $this->mtg(3), 'method' => 'BUTTON']), 422, 'ATTENDANCE_CLOSED');
        $this->assertError($this->call_('post', '/attendance/submit', '11520002', ['meetingId' => $this->mtg(1), 'method' => 'QR', 'qrToken' => 'qr_salah']), 422, 'QR_EXPIRED');
        $this->assertError($this->call_('post', '/attendance/submit', '11520002', ['meetingId' => $this->mtg(7), 'method' => 'BUTTON']), 422, 'ATTENDANCE_METHOD_NOT_ALLOWED'); // Meeting QR-only
        $this->assertError($this->call_('post', '/attendance/submit', '11520003', ['meetingId' => $this->mtg(1), 'method' => 'BUTTON']), 422, 'STUDENT_NOT_ENROLLED');
        $this->assertError($this->call_('post', '/attendance/submit', '11520002', ['meetingId' => $this->mtg(1), 'method' => 'FACE']), 422, 'VALIDATION_FAILED');
    }

    public function test_presensi_mandiri_error_umum(): void
    {
        $this->assertError($this->call_('post', '/attendance/submit', '11520001', ['method' => 'BUTTON']), 400, 'MISSING_FIELD');
        $this->assertError($this->call_('post', '/attendance/submit', '11520001', ['meetingId' => $this->mtg(1), 'method' => 'QR']), 400, 'MISSING_FIELD'); // qrToken wajib untuk QR
        $this->assertError($this->call_('post', '/attendance/submit', '11520001', ['meetingId' => 'x', 'method' => 'BUTTON']), 400, 'INVALID_ID_FORMAT');
        $this->assertError($this->call_('post', '/attendance/submit', '11520001', ['meetingId' => T4Seeder::id('mtg', 99), 'method' => 'BUTTON']), 404, 'MEETING_NOT_FOUND');
        $this->assertError($this->call_('post', '/attendance/submit', 'DSN001', ['meetingId' => $this->mtg(1), 'method' => 'BUTTON']), 403, 'ROLE_NOT_ALLOWED');
    }

    // ------------------------------------------------------------------ 8. presensi manual

    public function test_presensi_manual_baru_201_lalu_update_200_dengan_audit(): void
    {
        $url = '/meetings/'.$this->mtg(4).'/attendance/manual'; // Meeting sudah berakhir: manual tetap boleh
        // Andi tidak terdaftar di IF201
        $this->assertError($this->call_('post', $url, 'DSN001', ['studentId' => T4Seeder::id('std', 3), 'status' => 'ATTENDED']), 422, 'STUDENT_NOT_ENROLLED');

        // Meeting 1: Siti belum punya catatan -> 201. confirmedBy dari token walau body mencoba mengisi.
        $new = $this->call_('post', '/meetings/'.$this->mtg(1).'/attendance/manual', 'DSN001', ['studentId' => T4Seeder::id('std', 2), 'status' => 'permission', 'confirmedBy' => 'usr_palsu']);
        $this->assertSuccess($new, 201);
        $new->assertJsonPath('data.status', 'PERMISSION')->assertJsonPath('data.method', 'MANUAL')->assertJsonPath('data.confirmedBy', T4Seeder::id('usr', 4));

        // Meeting 4: Siti sudah SICK -> update 200
        $upd = $this->call_('post', $url, 'DSN001', ['studentId' => T4Seeder::id('std', 2), 'status' => 'ATTENDED']);
        $this->assertSuccess($upd, 200);
        $upd->assertJsonPath('data.status', 'ATTENDED');
        $this->assertSame(2, AttendanceAudit::where('action_type', AttendanceAudit::MANUAL_ENTRY)->count());
    }

    public function test_presensi_manual_error(): void
    {
        $url = '/meetings/'.$this->mtg(1).'/attendance/manual';
        $ok = ['studentId' => T4Seeder::id('std', 2), 'status' => 'ATTENDED'];
        $this->assertError($this->call_('post', $url, 'DSN001', ['status' => 'ATTENDED']), 400, 'MISSING_FIELD');
        $this->assertError($this->call_('post', $url, 'DSN001', ['studentId' => T4Seeder::id('std', 2)]), 400, 'MISSING_FIELD');
        $this->assertError($this->call_('post', $url, 'DSN001', ['studentId' => 'budi'] + $ok), 400, 'INVALID_ID_FORMAT');
        $this->assertError($this->call_('post', $url, 'DSN001', ['status' => 'LIBUR'] + $ok), 422, 'VALIDATION_FAILED');
        $this->assertError($this->call_('post', $url, 'DSN001', ['studentId' => T4Seeder::id('std', 99)] + $ok), 404, 'STUDENT_NOT_FOUND');
        $this->assertError($this->call_('post', $url, 'DSN002', $ok), 403, 'NOT_MEETING_LECTURER');
        $this->assertError($this->call_('post', $url, '11520001', $ok), 403, 'ROLE_NOT_ALLOWED');
    }

    // ------------------------------------------------------------------ 9. koreksi

    public function test_koreksi_presensi_200_dengan_audit(): void
    {
        $att = T4Seeder::id('att', 1); // Budi, Meeting 4, ATTENDED
        $r = $this->call_('put', "/attendance/{$att}/correction", 'DSN001', ['status' => 'sick']);
        $this->assertSuccess($r, 200);
        $r->assertJsonPath('data.status', 'SICK')->assertJsonPath('data.confirmedBy', T4Seeder::id('usr', 4));

        $audit = AttendanceAudit::where('attendance_id', $att)->first();
        $this->assertSame('ATTENDED', $audit->old_status);
        $this->assertSame('SICK', $audit->new_status);
        $this->assertSame(AttendanceAudit::CORRECTION, $audit->action_type);
        $this->assertSame(T4Seeder::id('usr', 4), $audit->changed_by);
    }

    public function test_koreksi_presensi_error(): void
    {
        $att = T4Seeder::id('att', 1);
        $this->assertError($this->call_('put', "/attendance/{$att}/correction", 'DSN001', []), 400, 'MISSING_FIELD');
        $this->assertError($this->call_('put', "/attendance/{$att}/correction", 'DSN001', ['status' => 'LIBUR']), 422, 'VALIDATION_FAILED');
        $this->assertError($this->call_('put', "/attendance/{$att}/correction", 'DSN001', ['status' => 'ATTENDED']), 422, 'STATUS_UNCHANGED');
        $this->assertError($this->call_('put', '/attendance/'.T4Seeder::id('att', 99).'/correction', 'DSN001', ['status' => 'SICK']), 404, 'ATTENDANCE_NOT_FOUND');
        $this->assertError($this->call_('put', '/attendance/abc/correction', 'DSN001', ['status' => 'SICK']), 400, 'INVALID_ID_FORMAT');
        $this->assertError($this->call_('put', "/attendance/{$att}/correction", 'DSN002', ['status' => 'SICK']), 403, 'NOT_MEETING_LECTURER');
        $this->assertError($this->call_('put', "/attendance/{$att}/correction", '11520001', ['status' => 'SICK']), 403, 'ROLE_NOT_ALLOWED');
    }

    // ------------------------------------------------------------------ 10-11. rekap

    public function test_rekap_mahasiswa(): void
    {
        $r = $this->call_('get', '/attendance/recap/student/'.T4Seeder::id('std', 1), '11520001');
        $this->assertSuccess($r, 200);
        $row = $r->json('data.courses.0');
        $this->assertSame('IF201', $row['courseCode']);
        $this->assertSame(5, $row['totalSessions']);
        $this->assertSame(['attendedSessions' => 4, 'sickSessions' => 0, 'absentSessions' => 1, 'permissionSessions' => 0], $row['attendedDetail']);
        $this->assertEquals(80.0, $row['totalPercentage']);
        $this->assertTrue($row['isEligibleForExam']);

        $this->call_('get', '/attendance/recap/student/'.T4Seeder::id('std', 1), 'DSN001')->assertOk();
        $this->call_('get', '/attendance/recap/student/'.T4Seeder::id('std', 1).'?courseId='.T4Seeder::id('crs', 2), '11520001')
            ->assertOk()->assertJsonCount(1, 'data.courses');
    }

    public function test_rekap_mahasiswa_error(): void
    {
        $this->assertError($this->call_('get', '/attendance/recap/student/'.T4Seeder::id('std', 2), '11520001'), 403, 'NOT_OWN_DATA');
        $this->assertError($this->call_('get', '/attendance/recap/student/'.T4Seeder::id('std', 99), 'DSN001'), 404, 'STUDENT_NOT_FOUND');
        $this->assertError($this->call_('get', '/attendance/recap/student/budi', 'DSN001'), 400, 'INVALID_ID_FORMAT');
        $this->assertError($this->call_('get', '/attendance/recap/student/'.T4Seeder::id('std', 1), 'KPR001'), 403, 'ROLE_NOT_ALLOWED');
    }

    public function test_rekap_mata_kuliah(): void
    {
        $url = '/attendance/recap/course/'.T4Seeder::id('crs', 1);
        $r = $this->call_('get', $url, 'DSN001');
        $this->assertSuccess($r, 200);
        $r->assertJsonPath('data.totalMeetings', 5)->assertJsonPath('data.averagePercentage', 50)
            ->assertJsonCount(2, 'data.students')->assertJsonPath('meta.totalItems', 2)->assertJsonPath('meta.totalPages', 1);

        $this->call_('get', $url.'?page=2&limit=1', 'KPR001')->assertOk()->assertJsonPath('data.students.0.nim', '11520002')->assertJsonPath('meta.totalPages', 2);
        $this->call_('get', $url, 'ADM001')->assertOk();
    }

    public function test_rekap_mata_kuliah_error(): void
    {
        $url = '/attendance/recap/course/'.T4Seeder::id('crs', 1);
        $this->assertError($this->call_('get', $url, 'DSN002'), 403, 'NOT_COURSE_LECTURER');
        $this->assertError($this->call_('get', $url, '11520001'), 403, 'ROLE_NOT_ALLOWED');
        $this->assertError($this->call_('get', '/attendance/recap/course/'.T4Seeder::id('crs', 99), 'DSN001'), 404, 'COURSE_NOT_FOUND');
        $this->assertError($this->call_('get', '/attendance/recap/course/xyz', 'DSN001'), 400, 'INVALID_ID_FORMAT');
        $this->assertError($this->call_('get', $url.'?page=0', 'DSN001'), 400, 'INVALID_QUERY');
    }

    public function test_summary_kompatibel_kontrak(): void
    {
        $q = '?studentId='.T4Seeder::id('std', 1).'&classOfferingId='.T4Seeder::id('crs', 1);
        $r = $this->call_('get', '/attendances/summary'.$q, 'DSN001');
        $this->assertSuccess($r, 200);
        $r->assertJsonPath('data.totalSessionsHeld', 5)->assertJsonPath('data.attendedSessions', 4)->assertJsonPath('data.attendancePercentage', 80)
            ->assertJsonPath('data.isEligibleForExam', true);
        $this->assertError($this->call_('get', '/attendances/summary?studentId='.T4Seeder::id('std', 1), 'DSN001'), 400, 'MISSING_FIELD');
    }

    // ------------------------------------------------------------------ Ketahanan

    public function test_alur_dinamis_rekap_berubah_setelah_meeting_berakhir(): void
    {
        $recap = fn () => $this->call_('get', '/attendance/recap/student/'.T4Seeder::id('std', 1).'?courseId='.T4Seeder::id('crs', 1), '11520001')->json('data.courses.0');
        $this->assertSame(5, $recap()['totalSessions']);

        $this->call_('post', '/attendance/submit', '11520001', ['meetingId' => $this->mtg(1), 'method' => 'BUTTON'])->assertCreated();
        $this->assertSame(5, $recap()['totalSessions']); // Meeting 1 belum dimulai dosen
        $this->call_('post', '/meetings/'.$this->mtg(1).'/start', 'DSN001')->assertOk();
        $this->assertSame(6, $recap()['totalSessions']);
        $this->assertSame(5, $recap()['attendedDetail']['attendedSessions']);
    }

    public function test_error_500_tidak_membocorkan_stack_trace(): void
    {
        $this->app->bind(RecapService::class, fn () => throw new RuntimeException('rahasia-internal'));
        config(['app.debug' => true]);
        $r = $this->call_('get', '/attendance/recap/course/'.T4Seeder::id('crs', 1), 'DSN001');
        $this->assertError($r, 500, 'INTERNAL_ERROR');
        $this->assertStringNotContainsString('rahasia-internal', $r->getContent());
        $this->assertStringNotContainsString('.php', $r->getContent());
    }

    public function test_rate_limit_429(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $this->postJson(self::API.'/auth/login', ['username' => 'x', 'password' => 'y']);
        }
        $this->assertError($this->postJson(self::API.'/auth/login', ['username' => 'x', 'password' => 'y']), 429, 'RATE_LIMIT_EXCEEDED');
    }

    public function test_method_tidak_diizinkan_dan_route_tidak_ada(): void
    {
        $this->assertError($this->call_('delete', '/meetings/'.$this->mtg(1).'/qr', 'DSN001'), 405, 'METHOD_NOT_ALLOWED');
        $this->assertError($this->call_('get', '/sessions', 'DSN001'), 404, 'RESOURCE_NOT_FOUND');
    }

    public function test_format_waktu_iso8601_utc_dengan_offset_masuk(): void
    {
        $r = $this->call_('post', '/meetings', 'DSN001', [
            'courseId' => T4Seeder::id('crs', 1), 'startTime' => '2026-12-01T08:00:00+07:00', 'endTime' => '2026-12-01T10:30:00+07:00',
        ]);
        $this->assertSuccess($r, 201);
        $r->assertJsonPath('data.startTime', '2026-12-01T01:00:00Z')->assertJsonPath('data.endTime', '2026-12-01T03:30:00Z');
    }
}
