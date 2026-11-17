<?php

use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\MeetingController;
use App\Http\Controllers\Api\V1\RecapController;
use Illuminate\Support\Facades\Route;

/*
 | API Presensi (T4), prefix /api/v1 (prefix "api" otomatis dari bootstrap/app.php).
 | Urutan middleware: jwt (401) -> role (403) -> json.body (400). Rate limit 100 request/menit per IP (SEC-INT-003).
 */
Route::prefix('v1')->middleware('throttle:100,1')->group(function () {
    // Stand-in Auth Tim 1 (untuk pengujian)
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('json.body');

    Route::middleware('jwt')->group(function () {
        // --- Dosen ---
        Route::middleware(['role:LECTURER', 'json.body'])->group(function () {
            Route::post('meetings', [MeetingController::class, 'store']);                                  // 1
            Route::patch('meetings/{meetingId}/attendance', [MeetingController::class, 'updateAttendance']); // 2
            Route::post('meetings/{meetingId}/start', [MeetingController::class, 'start']);                // 3
            Route::post('meetings/{meetingId}/end', [MeetingController::class, 'end']);                    // 4
            Route::get('meetings/{meetingId}/qr', [MeetingController::class, 'qr']);                       // 6
            Route::post('meetings/{meetingId}/attendance/manual', [AttendanceController::class, 'manual']);   // 8
            Route::put('attendance/{attendanceId}/correction', [AttendanceController::class, 'correction']);  // 9
        });

        // --- Dosen, Prodi ---
        Route::middleware(['role:LECTURER,KAPRODI,ADMIN_ACADEMIC'])->group(function () {
            Route::get('meetings/{meetingId}', [MeetingController::class, 'show']);                        // tambahan
            Route::get('meetings/{meetingId}/lecturer-attendance', [MeetingController::class, 'lecturerAttendance']); // 5
            Route::get('attendance/recap/course/{id}', [RecapController::class, 'course']);                // 11
        });

        // --- Mahasiswa ---
        Route::middleware(['role:STUDENT', 'json.body'])->group(function () {
            Route::post('attendance/submit', [AttendanceController::class, 'submit']);                     // 7
        });

        // --- Mahasiswa, Dosen (rekap) ---
        Route::middleware('role:STUDENT,LECTURER')->group(function () {
            Route::get('attendance/recap/student/{id}', [RecapController::class, 'student']);              // 10
        });

        // tambahan kompatibel kontrak (dikonsumsi T5)
        Route::get('attendances/summary', [RecapController::class, 'summary'])
            ->middleware('role:STUDENT,LECTURER,ADMIN_ACADEMIC,KAPRODI');
    });
});
