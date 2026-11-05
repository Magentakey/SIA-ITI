@extends('layouts.app')
@section('title', 'Laporan Kehadiran')
@section('role', 'admin')

@section('content')
@php
    $courses = config('t4dummy.admin.courses');
    $avg = \App\Support\PresensiFormat::average(array_column($courses, 5));
@endphp
    <nav class="crumb">Beranda / Laporan Kehadiran</nav>
    <h1>Laporan Kehadiran</h1>
    <p class="sub">Periode {{ config('t4dummy.periode') }} · pemantauan presensi seluruh mata kuliah (hanya baca)</p>

    <div class="tiles wide">
        <div class="tile"><span>Mata kuliah</span><b>{{ count($courses) }}</b><span>periode aktif</span></div>
        <div class="tile"><span>Rata-rata kehadiran</span><b>{{ \App\Support\PresensiFormat::label($avg) }}%</b><span>semua mata kuliah</span></div>
        <div class="tile"><span>Mahasiswa di bawah ambang</span><b>{{ array_sum(array_column($courses, 6)) }}</b><span>ambang {{ \App\Support\PresensiFormat::label(\App\Support\PresensiFormat::PASSING_PERCENT) }}% (sementara)</span></div>
    </div>

    <section class="card">
        <div class="card-h"><div><h2>Kehadiran per mata kuliah</h2><p class="cap">Rata-rata totalPercentage seluruh mahasiswa pada mata kuliah tersebut.</p></div></div>
        <div class="tbl-wrap">
            <table class="tbl">
                <thead><tr><th>Kode</th><th>Mata kuliah</th><th>Dosen</th><th class="r">Mahasiswa</th><th class="r">Pertemuan</th><th class="r">Rata-rata</th><th class="r">Di bawah ambang</th></tr></thead>
                <tbody>
                    @foreach ($courses as [$kode, $nama, $dosen, $mhs, $pertemuan, $rata, $bawah])
                        <tr>
                            <td class="mono">{{ $kode }}</td><td>{{ $nama }}</td><td>{{ $dosen }}</td>
                            <td class="r num">{{ $mhs }}</td><td class="r num">{{ $pertemuan }}</td>
                            <td class="r num">{{ \App\Support\PresensiFormat::label($rata) }}%</td>
                            <td class="r num">{{ $bawah }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection
