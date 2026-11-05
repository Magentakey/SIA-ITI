@extends('layouts.app')
@section('title', 'Riwayat kehadiran')
@section('role', 'mahasiswa')

@section('content')
@php
    $r = config('t4dummy.mahasiswa.riwayat');
    $periode = config('t4dummy.mahasiswa.periode');
    $count = ['Hadir' => 0, 'Sakit' => 0, 'Izin' => 0, 'Alpa' => 0];
    foreach ($r['meetings'] as $m) { $count[$m[2]]++; }
    $total = count($r['meetings']);
    $pct = \App\Support\PresensiFormat::percent($count['Hadir'], $total);
    $ok = \App\Support\PresensiFormat::isEligible($pct);
@endphp
    <nav class="crumb"><a href="{{ url('/presensi') }}">Beranda / Presensi</a> / {{ $r['code'] }}</nav>
    <h1>Riwayat kehadiran {{ $r['name'] }}</h1>
    <p class="sub">Periode {{ $periode }} · {{ $r['schedule'] }}</p>

    <div class="grid2">
        <section class="card">
            <div class="card-h">
                <div><h2>Daftar pertemuan</h2><p class="cap">{{ $total }} pertemuan · urut dari yang terlama</p></div>
            </div>
            <div class="tbl-wrap">
                <table class="tbl">
                    <thead><tr><th class="r">Pertemuan</th><th>Tanggal</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($r['meetings'] as [$no, $tanggal, $status])
                            <tr class="{{ $loop->last ? 'is-selected' : '' }}">
                                <td class="r num">{{ $no }}</td>
                                <td>{{ $tanggal }}</td>
                                <td>@include('presensi._badge', ['label' => $status, 'tone' => \App\Support\PresensiFormat::tone($status)])</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <aside>
            <section class="card">
                <div class="card-b">
                    <h2>Presensi</h2>
                    <p class="cap">{{ $count['Hadir'] }} dari {{ $total }} pertemuan · <span class="num">{{ \App\Support\PresensiFormat::label($pct) }}%</span></p>
                    <div class="tiles">
                        @foreach ($count as $label => $n)
                            <div class="tile"><b>{{ $n }}</b><span>{{ $label }}</span></div>
                        @endforeach
                    </div>
                    @include('presensi._badge', ['label' => $ok ? 'Memenuhi syarat ujian' : 'Belum memenuhi syarat ujian', 'tone' => $ok ? 'success' : 'danger'])
                </div>
            </section>
            <div class="banner banner-info">
                @include('presensi._icon', ['name' => 'info', 'size' => 20])
                <div><strong>Presensi dicatat oleh dosen</strong><p>Bila status kehadiran tidak sesuai, temui dosen pengampu secara langsung. Perbaikan dicatat oleh dosen.</p></div>
            </div>
        </aside>
    </div>
@endsection
