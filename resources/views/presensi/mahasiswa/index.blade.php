@extends('layouts.app')
@section('title', 'Presensi Saya')

@section('content')
    @include('presensi._tabs')

    <h1 class="text-3xl font-bold text-[#1b2d55]">Presensi Saya</h1>
    <p class="text-slate-500 mt-1 mb-6">Rekap kehadiran {{ $student }} ({{ $nim }}) per mata kuliah.</p>

    <div class="grid gap-4 md:grid-cols-3">
        @foreach ($rows as $row)
            @php($r = $row['recap'])
            <section class="rounded-xl bg-white border border-slate-200 p-5">
                <div class="text-xs text-slate-500">{{ $row['course']['code'] }}</div>
                <h2 class="font-semibold text-[#1b2d55]">{{ $row['course']['name'] }}</h2>
                <div class="text-xs text-slate-500 mb-4">{{ $row['course']['lecturer'] }}</div>

                <div class="flex items-end justify-between">
                    <div class="text-3xl font-bold text-[#1b2d55]">{{ $r['totalPercentage'] }}%</div>
                    @if ($r['totalPercentage'] < $threshold)
                        <span class="text-xs text-red-600">Di bawah {{ $threshold }}%</span>
                    @endif
                </div>
                <div class="h-2 rounded-full bg-slate-100 mt-2 overflow-hidden">
                    <div class="h-full bg-[#2f5aa8]" style="width: {{ $r['totalPercentage'] }}%"></div>
                </div>

                <dl class="grid grid-cols-4 gap-2 text-center mt-4 text-xs text-slate-500">
                    <div><dd class="text-base font-semibold text-emerald-700">{{ $r['attendedSessions'] }}</dd><dt>Hadir</dt></div>
                    <div><dd class="text-base font-semibold text-amber-700">{{ $r['sickSessions'] }}</dd><dt>Sakit</dt></div>
                    <div><dd class="text-base font-semibold text-sky-700">{{ $r['permissionSessions'] }}</dd><dt>Izin</dt></div>
                    <div><dd class="text-base font-semibold text-red-700">{{ $r['absentSessions'] }}</dd><dt>Alpa</dt></div>
                </dl>
                <div class="text-xs text-slate-400 mt-3">Total {{ $r['totalSessions'] }} pertemuan tercatat</div>
            </section>
        @endforeach
    </div>

    <div class="mt-6 rounded-lg bg-blue-50 text-blue-900 text-sm px-4 py-3">
        Persentase kehadiran dihitung dari pertemuan berstatus Hadir dibanding total pertemuan tercatat.
        <a class="underline font-medium" href="{{ route('presensi.mahasiswa.absen') }}">Absen sekarang →</a>
    </div>
@endsection
