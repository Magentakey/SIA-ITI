@extends('layouts.app')
@section('title', 'Presensi Dosen')
@section('role', 'dosen')
@section('tabs')
    @include('presensi._tabs', ['active' => 'kelas'])
@endsection

@section('content')
@php $d = config('t4dummy.dosen'); @endphp
    <nav class="crumb">Beranda / Presensi</nav>
    <h1>Presensi</h1>
    <p class="sub">Periode {{ config('t4dummy.periode') }} · {{ $d['date'] }} · {{ $d['name'] }}</p>

    <section class="card">
        <div class="card-h">
            <div>
                <h2>Kelas hari ini</h2>
                <p class="cap">{{ $d['date'] }} · {{ count($d['classes']) }} pertemuan</p>
            </div>
        </div>
        @foreach ($d['classes'] as $c)
            <div class="class-row" style="--c: {{ $c['color'] }}">
                <div>
                    <h3>{{ $c['name'] }} · {{ $c['class'] }}</h3>
                    <p>Pertemuan {{ $c['meeting'] }} · <span class="num">{{ $c['time'] }}</span> · {{ $c['room'] }} · <span class="mono">{{ $c['code'] }}</span></p>
                </div>
                <div class="row">
                    @include('presensi._badge', ['label' => $c['badge'][0], 'tone' => $c['badge'][1]])
                    <a class="btn {{ $loop->first ? 'btn-primary' : 'btn-secondary' }} btn-sm" href="{{ url('/presensi/dosen/meeting') }}{{ $loop->first ? '' : '?state=belum' }}">{{ $c['action'] }}</a>
                </div>
            </div>
        @endforeach
    </section>
@endsection
