@extends('layouts.app')
@section('title', 'Rekap mata kuliah')
@section('role', 'dosen')
@section('tabs')
    @include('presensi._tabs', ['active' => 'rekap'])
@endsection

@section('content')
@php
    $r = config('t4dummy.dosen.rekap');
    $rows = [];
    foreach ($r['students'] as [$nim, $nama, $c]) {
        $total = array_sum($c);
        $rows[] = ['nim' => $nim, 'nama' => $nama, 'c' => $c, 'pct' => \App\Support\PresensiFormat::percent($c[0], $total)];
    }
    $avg = \App\Support\PresensiFormat::average(array_column($rows, 'pct'));
    $below = count(array_filter($rows, fn ($x) => ! \App\Support\PresensiFormat::isEligible($x['pct'])));
@endphp
    <nav class="crumb">Beranda / Presensi / Rekap</nav>
    <h1>Rekap mata kuliah</h1>
    <p class="sub">Periode {{ config('t4dummy.periode') }} · <span class="mono">{{ $r['code'] }}</span> {{ $r['name'] }}</p>

    <div class="tiles wide">
        <div class="tile"><span>Rata-rata kehadiran</span><b>{{ \App\Support\PresensiFormat::label($avg) }}%</b><span>seluruh mahasiswa</span></div>
        <div class="tile"><span>Mahasiswa</span><b>{{ count($rows) }}</b><span>peserta kelas</span></div>
        <div class="tile"><span>Pertemuan</span><b>{{ $r['meetings'] }}</b><span>sudah berlangsung</span></div>
        <div class="tile"><span>Di bawah ambang</span><b>{{ $below }}</b><span>ambang {{ \App\Support\PresensiFormat::label(\App\Support\PresensiFormat::PASSING_PERCENT) }}% (sementara)</span></div>
    </div>

    <section class="card">
        <div class="card-h"><div><h2>Rekap per mahasiswa</h2><p class="cap">Persentase mata kuliah = rata-rata persentase seluruh mahasiswa.</p></div></div>
        <div class="tbl-wrap">
            <table class="tbl">
                <thead><tr><th>NIM</th><th>Nama</th><th class="r">Hadir</th><th class="r">Sakit</th><th class="r">Izin</th><th class="r">Alpa</th><th class="r">Kehadiran</th></tr></thead>
                <tbody>
                    @foreach ($rows as $x)
                        <tr>
                            <td class="mono">{{ $x['nim'] }}</td><td>{{ $x['nama'] }}</td>
                            <td class="r num">{{ $x['c'][0] }}</td><td class="r num">{{ $x['c'][1] }}</td><td class="r num">{{ $x['c'][2] }}</td><td class="r num">{{ $x['c'][3] }}</td>
                            <td class="r num">{{ \App\Support\PresensiFormat::label($x['pct']) }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="tbl-note">Kehadiran = hadir ÷ pertemuan yang sudah berlangsung. Perlakuan sakit/izin belum ditetapkan Prodi.</p>
    </section>
@endsection
