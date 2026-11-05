@extends('layouts.app')
@section('title', 'Kelola pertemuan')
@section('role', 'dosen')
@section('tabs')
    @include('presensi._tabs', ['active' => 'kelas'])
@endsection

@section('content')
@php
    $m = config('t4dummy.dosen.meeting');
    $state = request()->query('state', 'berlangsung');
    if (! array_key_exists($state, $m['states'])) { $state = 'berlangsung'; }
    $s = $m['states'][$state];
    $isPresent = $s['cp1'] !== null && $s['cp2'] !== null; // SR-T4-001: hadir hanya jika kedua checkpoint terpenuhi
    $ended = $state === 'berakhir';
    $statuses = ['Hadir', 'Sakit', 'Izin', 'Alpa'];
@endphp
    @include('presensi._devstates', ['items' => array_keys($m['states']), 'current' => $state, 'path' => '/presensi/dosen/meeting'])

    <nav class="crumb"><a href="{{ url('/presensi/dosen') }}">Beranda / Presensi</a> / {{ $m['code'] }} / Pertemuan {{ $m['number'] }}</nav>
    <div class="row" style="justify-content:space-between;align-items:flex-start">
        <div>
            <h1>{{ $m['title'] }} · {{ $m['class'] }}</h1>
            <p class="sub">Pertemuan {{ $m['number'] }} · {{ $m['info'] }}</p>
        </div>
        <div class="row">
            @include('presensi._badge', ['label' => $s['status'][0], 'tone' => $s['status'][1]])
            @if ($state === 'berlangsung')
                <button type="button" class="btn btn-secondary btn-sm" data-open="reminder-modal">Pratinjau pop-up reminder</button>
            @endif
        </div>
    </div>

    @if ($ended)
        <div class="banner banner-warning" role="status">
            @include('presensi._icon', ['name' => 'warning', 'size' => 20])
            <div><strong>Kelas telah berakhir</strong><p>Presensi mandiri ditutup dan QR Code tidak lagi valid. Presensi mahasiswa hanya dapat dicatat manual oleh dosen.</p></div>
        </div>
    @endif

    <section class="card">
        <div class="card-h">
            <div><h2>Presensi dosen</h2><p class="cap">Dua checkpoint: memulai dan mengakhiri kelas.</p></div>
            @include('presensi._badge', ['label' => $isPresent ? 'Hadir' : 'Belum lengkap', 'tone' => $isPresent ? 'success' : 'warning'])
        </div>
        <div class="card-b">
            <div class="cp">
                <div><strong>Checkpoint 1 · Mulai kelas</strong><p class="cap" style="margin:0">{{ $s['cp1'] ? 'Tercatat pukul '.$s['cp1'].' WIB' : 'Belum tercatat' }}</p></div>
                <button type="button" class="btn btn-primary btn-sm" data-action="start-meeting" {{ $s['cp1'] ? 'disabled' : '' }}>Mulai kelas</button>
            </div>
            <div class="cp">
                <div><strong>Checkpoint 2 · Akhiri kelas</strong><p class="cap" style="margin:0">{{ $s['cp2'] ? 'Tercatat pukul '.$s['cp2'].' WIB' : 'Belum tercatat' }}</p></div>
                <button type="button" class="btn btn-primary btn-sm" data-action="end-meeting" {{ ($s['cp1'] && ! $s['cp2']) ? '' : 'disabled' }}>Akhiri kelas</button>
            </div>
            <p class="cap" style="margin:8px 0 0">Status hadir dosen bernilai Hadir hanya jika kedua checkpoint terpenuhi.</p>
        </div>
    </section>

    <section class="card">
        <div class="card-h">
            <div><h2>Pintu absensi mahasiswa</h2><p class="cap">Presensi mandiri hanya saat kelas berlangsung dan pintu dibuka.</p></div>
            @include('presensi._badge', ['label' => $s['door'] ? 'Terbuka' : 'Tertutup', 'tone' => $s['door'] ? 'success' : 'neutral'])
        </div>
        <div class="card-b row" style="align-items:flex-start;gap:24px">
            <div>
                <label for="metode">Metode presensi</label>
                <select id="metode" {{ $state === 'berlangsung' ? '' : 'disabled' }}>
                    @foreach (['QR Code', 'Button', 'Keduanya'] as $opt)
                        <option {{ $s['method'] === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
                <div class="row" style="margin-top:12px">
                    <button type="button" class="btn btn-primary btn-sm" data-action="open-door" {{ ($state === 'berlangsung' && ! $s['door']) ? '' : 'disabled' }}>Buka pintu</button>
                    <button type="button" class="btn btn-secondary btn-sm" data-action="close-door" {{ $s['door'] ? '' : 'disabled' }}>Tutup pintu</button>
                </div>
            </div>
            @if ($s['door'])
                <div class="row">
                    <div class="qr">Area QR Code</div>
                    <p class="cap" style="max-width:220px">QR diperbarui berkala (usulan 30 detik) agar tidak mudah dibagikan.</p>
                </div>
            @endif
        </div>
    </section>

    <section class="card">
        <div class="card-h">
            <div><h2>Presensi mahasiswa</h2><p class="cap">{{ $ended ? 'Pencatatan manual oleh dosen.' : 'Mahasiswa presensi mandiri; dosen dapat mencatat atau mengoreksi.' }}</p></div>
        </div>
        <div class="tbl-wrap">
            <table class="tbl">
                <thead><tr><th>NIM</th><th>Nama</th><th>Status</th><th>Metode</th><th class="r">Waktu</th><th>Catat / koreksi</th></tr></thead>
                <tbody>
                    @foreach ($m['students'] as $i => [$nim, $nama])
                        @php $row = $s['rows'][$i] ?? null; @endphp
                        <tr>
                            <td class="mono">{{ $nim }}</td>
                            <td>{{ $nama }}</td>
                            <td>@if ($row)@include('presensi._badge', ['label' => $row[0], 'tone' => \App\Support\PresensiFormat::tone($row[0])])@else<span class="cap">Belum tercatat</span>@endif</td>
                            <td>{{ $row[1] ?? '–' }}</td>
                            <td class="r num">{{ $row[2] ?? '–' }}</td>
                            <td>
                                <div class="row" style="gap:8px">
                                    <select aria-label="Status {{ $nama }}" {{ $state === 'belum' ? 'disabled' : '' }}>
                                        @foreach ($statuses as $opt)<option {{ ($row[0] ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>@endforeach
                                    </select>
                                    <button type="button" class="btn btn-secondary btn-sm" data-action="manual-attendance" data-nim="{{ $nim }}" {{ $state === 'belum' ? 'disabled' : '' }}>Catat</button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="tbl-note">Setiap pencatatan manual dan koreksi disimpan pada jejak audit (pelaku, waktu, status lama dan baru). Satu mahasiswa hanya memiliki satu catatan per pertemuan.</p>
    </section>

    {{-- Pop-up reminder: 10 menit sebelum kelas berakhir, hanya bila Meeting belum diselesaikan. Tanpa snooze/alarm berkelanjutan. --}}
    <div class="scrim" id="reminder-modal" role="dialog" aria-modal="true" aria-labelledby="reminder-title">
        <div class="modal">
            <h2 id="reminder-title">Kelas berakhir 10 menit lagi</h2>
            <p>{{ $m['title'] }} · {{ $m['class'] }} dijadwalkan berakhir pukul 10.00 WIB. Jangan lupa mengakhiri kelas (checkpoint 2).</p>
            <div class="row">
                <button type="button" class="btn btn-ghost btn-sm" data-close="reminder-modal">Tutup</button>
                <button type="button" class="btn btn-primary btn-sm" data-close="reminder-modal">Akhiri kelas</button>
            </div>
        </div>
    </div>
    <script>
        (function () {
            var modal = document.getElementById('reminder-modal');
            document.querySelectorAll('[data-open="reminder-modal"]').forEach(function (b) { b.addEventListener('click', function () { modal.classList.add('open'); }); });
            document.querySelectorAll('[data-close="reminder-modal"]').forEach(function (b) { b.addEventListener('click', function () { modal.classList.remove('open'); }); });
            if (new URLSearchParams(location.search).get('reminder') === '1') { modal.classList.add('open'); }
        })();
    </script>
@endsection
