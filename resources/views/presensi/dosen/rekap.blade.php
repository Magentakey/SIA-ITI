@extends('layouts.app')
@section('title', 'Rekap Presensi')

@section('content')
    @include('presensi._tabs')

    <h1 class="text-3xl font-bold text-[#1b2d55]">Rekap Presensi</h1>
    <p class="text-slate-500 mt-1 mb-6">Rekap kehadiran mahasiswa per mata kuliah. Persentase mata kuliah = rata-rata persentase mahasiswa.</p>

    <div class="space-y-6">
        @foreach ($courses as $c)
            <section class="rounded-xl bg-white border border-slate-200 overflow-x-auto">
                <div class="px-5 py-4 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="font-semibold text-[#1b2d55]">{{ $c['name'] }} <span class="text-slate-400 font-normal">· {{ $c['code'] }}</span></h2>
                        <div class="text-xs text-slate-500">{{ $c['lecturer'] }}</div>
                    </div>
                    <div class="text-sm">Rata-rata kehadiran: <span class="font-bold text-[#1b2d55]">{{ $c['recap']['average'] }}%</span>
                        @if ($c['recap']['below'] > 0)
                            <span class="text-red-600 ml-2">{{ $c['recap']['below'] }} mahasiswa di bawah {{ $threshold }}%</span>
                        @endif
                    </div>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="text-left px-5 py-3">NIM</th><th class="text-left px-5 py-3">Nama</th>
                            <th class="px-3 py-3">Hadir</th><th class="px-3 py-3">Sakit</th><th class="px-3 py-3">Izin</th><th class="px-3 py-3">Alpa</th>
                            <th class="px-3 py-3">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($c['recap']['rows'] as $row)
                            <tr class="border-t border-slate-100 text-center">
                                <td class="px-5 py-3 text-left text-[#2f5aa8]">{{ $row['nim'] }}</td>
                                <td class="px-5 py-3 text-left font-medium">{{ $row['name'] }}</td>
                                <td class="px-3 py-3">{{ $row['recap']['attendedSessions'] }}</td>
                                <td class="px-3 py-3">{{ $row['recap']['sickSessions'] }}</td>
                                <td class="px-3 py-3">{{ $row['recap']['permissionSessions'] }}</td>
                                <td class="px-3 py-3">{{ $row['recap']['absentSessions'] }}</td>
                                <td class="px-3 py-3 font-semibold {{ $row['recap']['totalPercentage'] < $threshold ? 'text-red-600' : '' }}">{{ $row['recap']['totalPercentage'] }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        @endforeach
    </div>
@endsection
