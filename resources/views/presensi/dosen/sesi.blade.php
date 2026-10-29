@extends('layouts.app')
@section('title', 'Kelola Sesi')

@section('content')
    @include('presensi._tabs')

    <a href="{{ route('presensi.dosen.index') }}" class="text-sm text-[#2f5aa8]">← Kembali / Sesi Hari Ini</a>
    <div class="flex flex-wrap items-start justify-between gap-4 mt-2 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-[#1b2d55]">{{ $session['name'] }}</h1>
            <p class="text-slate-500 mt-1">{{ $session['course'] }} · {{ $session['start'] }}–{{ $session['end'] }} · {{ $session['room'] }} · Pertemuan {{ $session['meeting'] }}</p>
        </div>
        <div class="flex items-center gap-3">
            @include('presensi._badge', ['status' => $state['status']])
            @if ($state['status'] === 'belum')
                <form method="POST" action="{{ route('presensi.dosen.sesi.mulai', $session['id']) }}">@csrf
                    <button class="rounded-lg bg-[#2f5aa8] text-white text-sm font-medium px-5 py-2.5 hover:bg-[#244a8f]">Mulai Kuliah</button>
                </form>
            @elseif ($state['status'] === 'berlangsung')
                <form method="POST" action="{{ route('presensi.dosen.sesi.selesai', $session['id']) }}">@csrf
                    <button class="rounded-lg bg-[#1b2d55] text-white text-sm font-medium px-5 py-2.5 hover:bg-[#14224a]">Selesaikan Kuliah</button>
                </form>
            @endif
        </div>
    </div>

    {{-- Reminder: muncul 10 menit setelah jam kuliah selesai, sampai kuliah diselesaikan --}}
    @if ($state['status'] === 'berlangsung')
        <div id="reminder" class="hidden mb-4 rounded-lg border border-amber-300 bg-amber-50 text-amber-900 px-4 py-3 text-sm" role="alert">
            <div class="font-semibold">Jam kuliah sudah selesai.</div>
            <div class="mb-3">Jangan lupa menyelesaikan kuliah agar absen ditutup.</div>
            <div class="flex gap-2">
                <form method="POST" action="{{ route('presensi.dosen.sesi.tunda', $session['id']) }}">@csrf
                    <button class="rounded-lg border border-amber-400 px-4 py-1.5 hover:bg-amber-100">Ingatkan nanti</button>
                </form>
                <form method="POST" action="{{ route('presensi.dosen.sesi.selesai', $session['id']) }}">@csrf
                    <button class="rounded-lg bg-amber-600 text-white px-4 py-1.5 hover:bg-amber-700">Selesaikan Kuliah</button>
                </form>
            </div>
        </div>
        <script>
            (function () {
                var box = document.getElementById('reminder');
                function check() {
                    fetch(@json(route('presensi.dosen.sesi.reminder', $session['id'])), { headers: { 'Accept': 'application/json' } })
                        .then(function (r) { return r.json(); })
                        .then(function (d) { box.classList.toggle('hidden', !d.remind); })
                        .catch(function () {});
                }
                check();
                setInterval(check, 30000);
            })();
        </script>

        <div class="mb-6 rounded-xl bg-white border border-slate-200 p-5 flex items-center gap-5">
            {{-- TODO: render QR sungguhan (butuh package QR, lihat docs/T4-presensi.md) --}}
            <div class="w-28 h-28 rounded-lg border-2 border-dashed border-slate-300 flex items-center justify-center text-xs text-slate-400 text-center">Area QR Code</div>
            <div>
                <div class="text-xs text-slate-500">Kode absen QR / token</div>
                <div class="text-3xl font-bold tracking-widest text-[#1b2d55]">{{ $state['token'] }}</div>
                <div class="text-xs text-slate-500">Bagikan kode ini ke mahasiswa, atau mahasiswa cukup menekan tombol absen.</div>
            </div>
        </div>
    @endif

    <div class="rounded-xl bg-white border border-slate-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="text-left px-5 py-3">NIM</th>
                    <th class="text-left px-5 py-3">Nama Mahasiswa</th>
                    <th class="text-left px-5 py-3">Status</th>
                    <th class="text-left px-5 py-3">Ubah Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($students as $st)
                    <tr class="border-t border-slate-100">
                        <td class="px-5 py-3 text-[#2f5aa8]">{{ $st['nim'] }}</td>
                        <td class="px-5 py-3 font-medium">{{ $st['name'] }}</td>
                        <td class="px-5 py-3">@include('presensi._badge', ['status' => $st['status']])</td>
                        <td class="px-5 py-3">
                            <form method="POST" action="{{ route('presensi.dosen.sesi.mark', [$session['id'], $st['nim']]) }}" class="flex gap-2">
                                @csrf
                                <select name="status" class="rounded-lg border border-slate-300 px-2 py-1 text-sm">
                                    @foreach ($statuses as $opt)
                                        <option value="{{ $opt->value }}" @selected($st['status'] === $opt)>{{ $opt->label() }}</option>
                                    @endforeach
                                </select>
                                <button class="rounded-lg border border-[#2f5aa8] text-[#2f5aa8] px-3 py-1 text-sm hover:bg-blue-50">Simpan</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
