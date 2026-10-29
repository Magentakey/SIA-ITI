@extends('layouts.app')
@section('title', 'Absen Kuliah')

@section('content')
    @include('presensi._tabs')

    <h1 class="text-3xl font-bold text-[#1b2d55]">Absen Kuliah</h1>
    <p class="text-slate-500 mt-1 mb-6">Sesi kuliah hari ini. Absen hanya bisa dilakukan saat dosen sudah membuka kuliah.</p>

    <div class="space-y-4">
        @foreach ($sessions as $s)
            <section class="rounded-xl bg-white border border-slate-200 p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="font-semibold text-[#1b2d55]">{{ $s['name'] }} <span class="text-slate-400 font-normal">· {{ $s['course'] }}</span></h2>
                        <div class="text-sm text-slate-500">{{ $s['start'] }}–{{ $s['end'] }} · {{ $s['room'] }} · {{ $s['lecturer'] }} · Pertemuan {{ $s['meeting'] }}</div>
                    </div>
                    @include('presensi._badge', ['status' => $s['state']['status']])
                </div>

                <div class="mt-4">
                    @if ($s['record'])
                        <div class="text-sm text-slate-600">Status kehadiranmu: @include('presensi._badge', ['status' => $s['record']])</div>
                    @elseif ($s['state']['status'] === 'berlangsung')
                        <div class="grid gap-4 md:grid-cols-2">
                            <form method="POST" action="{{ route('presensi.mahasiswa.absen.store', $s['id']) }}">
                                @csrf
                                <input type="hidden" name="method" value="button">
                                <button class="rounded-lg bg-[#2f5aa8] text-white text-sm font-medium px-5 py-2.5 hover:bg-[#244a8f]">Absen Hadir</button>
                            </form>
                            <form method="POST" action="{{ route('presensi.mahasiswa.absen.store', $s['id']) }}" class="flex gap-2">
                                @csrf
                                <input type="hidden" name="method" value="qr">
                                <input name="token" required maxlength="20" placeholder="Kode QR / token dari dosen" autocomplete="off"
                                       class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm uppercase">
                                <button class="rounded-lg border border-[#2f5aa8] text-[#2f5aa8] text-sm font-medium px-4 py-2 hover:bg-blue-50">Kirim Kode</button>
                            </form>
                        </div>
                    @elseif ($s['state']['status'] === 'belum')
                        <p class="text-sm text-slate-500">Menunggu dosen memulai kuliah.</p>
                    @else
                        <p class="text-sm text-slate-500">Absen sudah ditutup.</p>
                    @endif
                </div>
            </section>
        @endforeach
    </div>
@endsection
