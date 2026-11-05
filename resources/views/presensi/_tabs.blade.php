<nav class="tabs" aria-label="Navigasi modul Presensi">
    <a href="{{ url('/presensi/dosen') }}" class="{{ $active === 'kelas' ? 'on' : '' }}">Kelas hari ini</a>
    <a href="{{ url('/presensi/dosen/rekap') }}" class="{{ $active === 'rekap' ? 'on' : '' }}">Rekap mata kuliah</a>
</nav>
