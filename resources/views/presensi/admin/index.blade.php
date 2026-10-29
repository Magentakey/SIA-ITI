@extends('layouts.app')
@section('title', 'Monitoring Presensi')

@section('content')
    @include('presensi._tabs')

    <h1 class="text-3xl font-bold text-[#1b2d55]">Monitoring Presensi</h1>
    <p class="text-slate-500 mt-1 mb-6">Ringkasan kehadiran seluruh mata kuliah pada periode aktif. {{ $totalStudents }} mahasiswa terdata.</p>

    <div class="rounded-xl bg-white border border-slate-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="text-left px-5 py-3">Kode</th>
                    <th class="text-left px-5 py-3">Mata Kuliah</th>
                    <th class="text-left px-5 py-3">Dosen</th>
                    <th class="text-left px-5 py-3">Rata-rata Kehadiran</th>
                    <th class="text-left px-5 py-3">Di Bawah {{ $threshold }}%</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $r)
                    <tr class="border-t border-slate-100">
                        <td class="px-5 py-4 text-[#2f5aa8]">{{ $r['code'] }}</td>
                        <td class="px-5 py-4 font-semibold">{{ $r['name'] }}</td>
                        <td class="px-5 py-4">{{ $r['lecturer'] }}</td>
                        <td class="px-5 py-4">{{ $r['recap']['average'] }}%</td>
                        <td class="px-5 py-4">
                            @if ($r['recap']['below'] > 0)
                                <span class="inline-block rounded-full px-3 py-0.5 text-xs font-medium bg-red-50 text-red-700">{{ $r['recap']['below'] }} mahasiswa</span>
                            @else
                                <span class="inline-block rounded-full px-3 py-0.5 text-xs font-medium bg-emerald-50 text-emerald-700">Aman</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-6 rounded-lg bg-blue-50 text-blue-900 text-sm px-4 py-3">Data presensi bersumber dari modul Presensi (T4) dan digunakan bersama modul nilai.</div>
@endsection
