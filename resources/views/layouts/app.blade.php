<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SIA ITI') · SIA ITI</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex flex-col bg-slate-50 text-slate-800">
{{-- NOTE: layout sementara dari T4, mengikuti desain Dashboard Admin. Samakan dengan layout bersama bila sudah ada. --}}
<header class="bg-[#1b2d55] text-white">
    <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
        <div>
            <div class="text-xl font-bold">SIA ITI</div>
            <div class="text-xs text-slate-300">Sistem Informasi Akademik · Institut Teknologi Indonesia</div>
        </div>
        <div class="text-sm text-slate-200">Ganjil 2026/2027</div>
    </div>
</header>

<nav class="bg-white border-b border-slate-200">
    <div class="max-w-6xl mx-auto px-6 flex gap-8 text-sm overflow-x-auto">
        @foreach (['Dashboard', 'Academic Master & User Management', 'Kurikulum & KRS', 'Jadwal & Perkuliahan'] as $item)
            <a href="#" class="py-4 text-slate-500 whitespace-nowrap hover:text-[#1b2d55]">{{ $item }}</a>
        @endforeach
        <a href="{{ route('presensi.index') }}" class="py-4 whitespace-nowrap font-semibold text-[#2f5aa8] border-b-2 border-[#2f5aa8]">Presensi</a>
        <a href="#" class="py-4 text-slate-500 whitespace-nowrap hover:text-[#1b2d55]">Nilai & Transkrip</a>
    </div>
</nav>

<main class="max-w-6xl w-full mx-auto px-6 py-8 flex-1">
    @if (session('status'))
        <div class="mb-4 rounded-lg bg-emerald-50 text-emerald-800 text-sm px-4 py-3" role="status">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 text-red-800 text-sm px-4 py-3" role="alert">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 text-red-800 text-sm px-4 py-3" role="alert">{{ $errors->first() }}</div>
    @endif

    @yield('content')
</main>

<footer class="max-w-6xl w-full mx-auto px-6 py-4 text-xs text-slate-500 flex justify-between">
    <span>© 2026 Institut Teknologi Indonesia · SIA ITI</span>
    <span>Modul Presensi (T4)</span>
</footer>
</body>
</html>
