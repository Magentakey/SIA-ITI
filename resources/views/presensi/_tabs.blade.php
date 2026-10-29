{{-- Tab sementara per peran. Hapus/ganti setelah login & role dari T1 tersedia. --}}
@php
    $tabs = [
        ['Mahasiswa · Rekap', 'presensi.mahasiswa.index', 'presensi.mahasiswa.index'],
        ['Mahasiswa · Absen', 'presensi.mahasiswa.absen', 'presensi.mahasiswa.absen'],
        ['Dosen · Sesi Hari Ini', 'presensi.dosen.index', 'presensi.dosen.index'],
        ['Dosen · Rekap', 'presensi.dosen.rekap', 'presensi.dosen.rekap'],
        ['Admin · Monitoring', 'presensi.admin.index', 'presensi.admin.*'],
    ];
@endphp
<div class="mb-6 flex flex-wrap gap-2 text-sm">
    @foreach ($tabs as [$label, $route, $pattern])
        <a href="{{ route($route) }}"
           class="rounded-full px-4 py-1.5 border {{ request()->routeIs($pattern) ? 'bg-[#2f5aa8] text-white border-[#2f5aa8]' : 'bg-white text-slate-600 border-slate-200 hover:border-[#2f5aa8]' }}">
            {{ $label }}
        </a>
    @endforeach
</div>
