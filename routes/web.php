<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Modul Presensi (T4) - Mahasiswa
Route::get('/presensi', function () {
    return view('presensi.index');
});

Route::get('/presensi/riwayat', function () {
    return view('presensi.riwayat');
});

// Modul Presensi (T4) - Dosen
Route::get('/presensi/dosen', function () {
    return view('presensi.dosen.index');
});

Route::get('/presensi/dosen/meeting', function () {
    return view('presensi.dosen.meeting');
});

Route::get('/presensi/dosen/rekap', function () {
    return view('presensi.dosen.rekap');
});

// Modul Presensi (T4) - BAAK / Admin
Route::get('/presensi/admin', function () {
    return view('presensi.admin.index');
});
