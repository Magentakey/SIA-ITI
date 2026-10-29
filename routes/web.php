<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Modul Presensi (Team 4)
require __DIR__.'/presensi.php';
