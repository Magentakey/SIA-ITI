<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SIA ITI') · SIA ITI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/sia-t4.css') }}">
</head>
<body>
{{-- Layout sementara T4, mengikuti 00 Foundation (Gaya B). Ganti dengan layout bersama bila sudah ada. --}}
@php
    $role = trim($__env->yieldContent('role', 'mahasiswa'));
    $modules = [
        'mahasiswa' => ['Modul Mahasiswa', ['Beranda', 'KRS', 'Jadwal', 'Presensi', 'Nilai dan KHS', 'Materi dan Tugas', 'Profil'], url('/presensi')],
        'dosen' => ['Modul Dosen', ['Beranda', 'Jadwal Mengajar', 'Kelas Diampu', 'Presensi', 'Nilai', 'Materi dan Tugas', 'Profil'], url('/presensi/dosen')],
        'admin' => ['Modul BAAK / Admin akademik', ['Dashboard', 'Mahasiswa', 'Dosen', 'Mata Kuliah dan Kurikulum', 'Jadwal', 'Laporan Kehadiran'], url('/presensi/admin')],
    ];
    [$moduleTitle, $moduleItems, $presensiUrl] = $modules[$role] ?? $modules['mahasiswa'];
@endphp
<header class="topbar">
    <div class="brand"><strong>SIA ITI</strong><span>Institut Teknologi Indonesia</span></div>
    <div class="topbar-right">
        <div class="period">
            @include('presensi._icon', ['name' => 'calendar'])
            <span>Periode</span> <b class="num">{{ config('t4dummy.periode') }}</b>
            @include('presensi._badge', ['label' => 'Aktif', 'tone' => 'success'])
            @include('presensi._icon', ['name' => 'caret-down'])
        </div>
        <a class="iconbtn" href="#" aria-label="Bantuan">@include('presensi._icon', ['name' => 'question', 'size' => 20])</a>
        <a class="iconbtn" href="#" aria-label="Notifikasi">@include('presensi._icon', ['name' => 'bell', 'size' => 20])</a>
        <details class="launcher">
            <summary class="iconbtn" aria-label="Peluncur modul">@include('presensi._icon', ['name' => 'grid', 'size' => 20])</summary>
            <div class="launcher-panel">
                <h4>{{ $moduleTitle }}</h4>
                <ul>
                    @foreach ($moduleItems as $item)
                        <li><a href="{{ in_array($item, ['Presensi', 'Laporan Kehadiran'], true) ? $presensiUrl : '#' }}" class="{{ in_array($item, ['Presensi', 'Laporan Kehadiran'], true) ? 'on' : '' }}">{{ $item }}</a></li>
                    @endforeach
                </ul>
                <div class="role">Masuk sebagai (sementara):
                    <a href="{{ url('/presensi') }}">Mahasiswa</a><a href="{{ url('/presensi/dosen') }}">Dosen</a><a href="{{ url('/presensi/admin') }}">BAAK</a>
                </div>
            </div>
        </details>
        <a class="iconbtn" href="#" aria-label="Profil">@include('presensi._icon', ['name' => 'user', 'size' => 20])</a>
    </div>
</header>

@yield('tabs')

<main class="container">
    @yield('content')
</main>
</body>
</html>
