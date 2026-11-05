@extends('layouts.app')
@section('title', 'Presensi')
@section('role', 'mahasiswa')

@section('content')
@php
    $d = config('t4dummy.mahasiswa');
    $state = request()->query('state', 'normal');
    if (! array_key_exists($state, $d['states'])) { $state = 'normal'; }
    $s = $d['states'][$state];
@endphp
    @include('presensi._devstates', ['items' => array_keys($d['states']), 'current' => $state, 'path' => '/presensi'])

    <nav class="crumb">Beranda / Presensi</nav>
    <h1>Presensi</h1>
    <p class="sub">Periode {{ $d['periode'] }} · {{ $s['now'] }}</p>

    @if ($state === 'error')
        <div class="banner banner-danger" role="alert">
            @include('presensi._icon', ['name' => 'warning', 'size' => 20])
            <div><strong>Gagal memuat presensi</strong><p>Server gagal memproses permintaan. Coba lagi beberapa saat.</p></div>
            <a class="btn btn-secondary btn-sm" href="{{ url('/presensi') }}">Coba lagi</a>
        </div>
    @elseif ($state === 'loading')
        <div aria-busy="true">
            <span class="sr-only">Memuat presensi</span>
            @foreach ([1, 2] as $block)
                <div class="card"><div class="card-b"><div class="sk" style="width:30%"></div><div class="sk" style="width:60%"></div><div class="sk" style="width:45%"></div></div></div>
            @endforeach
        </div>
    @else
        <section class="card">
            <div class="card-h">
                <div>
                    <h2>Kelas hari ini</h2>
                    <p class="cap">{{ $s['date'] }}@if (count($s['classes']) > 0) · {{ count($s['classes']) }} kelas · presensi dicatat oleh dosen @endif</p>
                </div>
            </div>
            @forelse ($s['classes'] as $c)
                <div class="class-row" style="--c: {{ $c['color'] }}">
                    <div>
                        <h3>{{ $d['courses'][$c['code']]['name'] }}</h3>
                        <p>Pertemuan {{ $c['meeting'] }} · <span class="num">{{ $c['time'] }}</span> · {{ $c['room'] }} · {{ $c['lecturer'] }}</p>
                        <p class="note">@include('presensi._icon', ['name' => $c['icon']]) {{ $c['note'] }}</p>
                    </div>
                    @include('presensi._badge', ['label' => $c['badge'][0], 'tone' => $c['badge'][1]])
                </div>
            @empty
                <div class="empty">
                    <h3>Tidak ada kelas hari ini</h3>
                    <p>{{ $s['next'] }}</p>
                </div>
            @endforelse
        </section>

        <section class="card">
            <div class="card-h">
                <div>
                    <h2>Riwayat per mata kuliah</h2>
                    <p class="cap">Periode {{ $d['periode'] }} · {{ $s['table_note'] ?? $d['table_note'] }}</p>
                </div>
                <a href="{{ url('/presensi/riwayat') }}" class="btn btn-ghost btn-sm">Lihat riwayat Pemrograman Web</a>
            </div>
            <div class="tbl-wrap">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>Kode</th><th>Mata kuliah</th>
                            <th class="r">Hadir</th><th class="r">Sakit</th><th class="r">Izin</th><th class="r">Alpa</th>
                            <th class="r">Pertemuan</th><th class="r">Kehadiran</th><th>Syarat ujian</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($d['courses'] as $code => $course)
                            @php
                                [$hadir, $sakit, $izin, $alpa, $total] = $s['recap'][$code] ?? $d['recap'][$code];
                                $pct = \App\Support\PresensiFormat::percent($hadir, $total);
                                $ok = \App\Support\PresensiFormat::isEligible($pct);
                            @endphp
                            <tr class="{{ ($s['highlight'] ?? null) === $code ? 'is-selected' : '' }}">
                                <td class="mono">{{ $code }}</td>
                                <td>{{ $course['name'] }}</td>
                                <td class="r num">{{ $hadir }}</td><td class="r num">{{ $sakit }}</td>
                                <td class="r num">{{ $izin }}</td><td class="r num">{{ $alpa }}</td>
                                <td class="r num">{{ $total }}</td>
                                <td class="r num">{{ \App\Support\PresensiFormat::label($pct) }}%</td>
                                <td>@include('presensi._badge', ['label' => $ok ? 'Memenuhi' : 'Belum memenuhi', 'tone' => $ok ? 'success' : 'danger'])</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="tbl-note">Kehadiran = hadir ÷ pertemuan yang sudah berlangsung. Status syarat ujian dari sistem; ambang dan perlakuan sakit/izin belum ditetapkan PO. Bila status kehadiran tidak sesuai, temui dosen pengampu secara langsung. Perbaikan dicatat oleh dosen.</p>
        </section>
    @endif
@endsection
