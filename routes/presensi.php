<?php

use App\Http\Controllers\Presensi\AdminPresensiController;
use App\Http\Controllers\Presensi\DosenPresensiController;
use App\Http\Controllers\Presensi\MahasiswaPresensiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Route modul Presensi (Team 4)
|--------------------------------------------------------------------------
| TODO (setelah autentikasi T1 siap): bungkus tiap grup dengan middleware role,
| mis. ->middleware(['auth', 'role:dosen']). Otorisasi harus di backend (SEC-002).
*/

Route::prefix('presensi')->name('presensi.')->group(function () {
    Route::redirect('/', '/presensi/mahasiswa')->name('index');

    Route::prefix('mahasiswa')->name('mahasiswa.')->group(function () {
        Route::get('/', [MahasiswaPresensiController::class, 'index'])->name('index');
        Route::get('/absen', [MahasiswaPresensiController::class, 'absen'])->name('absen');
        Route::post('/absen/{session}', [MahasiswaPresensiController::class, 'store'])->whereNumber('session')->name('absen.store');
    });

    Route::prefix('dosen')->name('dosen.')->group(function () {
        Route::get('/', [DosenPresensiController::class, 'index'])->name('index');
        Route::get('/rekap', [DosenPresensiController::class, 'rekap'])->name('rekap');
        Route::get('/sesi/{session}', [DosenPresensiController::class, 'show'])->whereNumber('session')->name('sesi');
        Route::post('/sesi/{session}/mulai', [DosenPresensiController::class, 'start'])->whereNumber('session')->name('sesi.mulai');
        Route::post('/sesi/{session}/selesai', [DosenPresensiController::class, 'finish'])->whereNumber('session')->name('sesi.selesai');
        Route::post('/sesi/{session}/tunda', [DosenPresensiController::class, 'snooze'])->whereNumber('session')->name('sesi.tunda');
        Route::get('/sesi/{session}/reminder', [DosenPresensiController::class, 'reminder'])->whereNumber('session')->name('sesi.reminder');
        Route::post('/sesi/{session}/mahasiswa/{nim}', [DosenPresensiController::class, 'mark'])->whereNumber('session')->name('sesi.mark');
    });

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminPresensiController::class, 'index'])->name('index');
    });
});
