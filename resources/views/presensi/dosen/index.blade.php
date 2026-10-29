@extends('layouts.app')
@section('title', 'Presensi Dosen')

@section('content')
    @include('presensi._tabs')

    <h1 class="text-3xl font-bold text-[#1b2d55]">Sesi Kuliah Hari Ini</h1>
    <p class="text-slate-500 mt-1 mb-6">Mulai dan selesaikan kuliah untuk membuka/menutup absen mahasiswa.</p>

    <div class="rounded-xl bg-white border border-slate-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="text-left px-5 py-3">Waktu</th>
                    <th class="text-left px-5 py-3">Mata Kuliah</th>
                    <th class="text-left px-5 py-3">Ruang</th>
                    <th class="text-left px-5 py-3">Pertemuan</th>
                    <th class="text-left px-5 py-3">Status</th>
                    <th class="text-left px-5 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sessions as $s)
                    <tr class="border-t border-slate-100">
                        <td class="px-5 py-4 font-medium">{{ $s['start'] }}–{{ $s['end'] }}</td>
                        <td class="px-5 py-4"><div class="font-semibold text-[#1b2d55]">{{ $s['name'] }}</div><div class="text-xs text-slate-500">{{ $s['course'] }} · {{ $s['lecturer'] }}</div></td>
                        <td class="px-5 py-4">{{ $s['room'] }}</td>
                        <td class="px-5 py-4">{{ $s['meeting'] }}</td>
                        <td class="px-5 py-4">@include('presensi._badge', ['status' => $s['state']['status']])</td>
                        <td class="px-5 py-4"><a class="text-[#2f5aa8] font-medium hover:underline" href="{{ route('presensi.dosen.sesi', $s['id']) }}">Kelola</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
