# Dokumentasi T4 — Modul Presensi (Halaman)

Referensi: SRS-SIA-T4 v1.1 (revisi notulen PTI), S2D Presensi Mahasiswa, 00 Foundation (Gaya B).
Status: **halaman + route + data dummy**. Logika absensi, QR, reminder, dan API belum dibuat (tahap berikutnya).

## 1. Ringkasan

- Halaman dibuat mengikuti desain tim design (S2D Presensi Mahasiswa) dan panduan gaya 00 Foundation: top bar navy, aksi plum, font Poppins/Inter/IBM Plex Mono, radius 4/6/8, badge status sesuai "Pemetaan status".
- Istilah mengikuti SRS T4 v1.1: **Meeting** (bukan Session), presensi dosen dua checkpoint, reminder 10 menit **sebelum** kelas berakhir (tanpa snooze), presensi mandiri hanya saat kelas berlangsung, setelah kelas berakhir hanya manual oleh dosen.
- Data masih dummy di `config/t4dummy.php`. Tidak ada controller, service, atau database.

## 2. Route (gaya `Route::get(..., function () { return view(...); })` di `routes/web.php`)

| URL | View | Pengguna | Keterangan |
|---|---|---|---|
| `/presensi` | `presensi.index` | Mahasiswa | Kelas hari ini + riwayat per mata kuliah |
| `/presensi/riwayat` | `presensi.riwayat` | Mahasiswa | Riwayat kehadiran per pertemuan (IF201) |
| `/presensi/dosen` | `presensi.dosen.index` | Dosen | Kelas hari ini (daftar Meeting) |
| `/presensi/dosen/meeting` | `presensi.dosen.meeting` | Dosen | Kelola Meeting: checkpoint, pintu absensi, presensi manual, pop-up reminder |
| `/presensi/dosen/rekap` | `presensi.dosen.rekap` | Dosen | Rekap mata kuliah + rata-rata |
| `/presensi/admin` | `presensi.admin.index` | BAAK/Prodi | Laporan kehadiran (hanya baca) |

Pratinjau status layar lewat query `?state=`:
- `/presensi?state=` normal · berlangsung · alpa · kosong · loading · error
- `/presensi/dosen/meeting?state=` belum · berlangsung · berakhir (pop-up reminder dibuka lewat tombol "Pratinjau pop-up reminder", atau tambahkan `?reminder=1`)
- Strip "Pratinjau status layar" dan menu "Masuk sebagai (sementara)" hanya untuk review; hapus saat data nyata terhubung.

## 3. Pemetaan desain/SRS ke halaman

| Kebutuhan | Halaman |
|---|---|
| US-T4-006 rekap mahasiswa (FR-T4-006) | `/presensi`, `/presensi/riwayat` |
| FR-T4-007 / SR-T4-007 rata-rata mata kuliah | `/presensi/dosen/rekap`, `/presensi/admin` |
| FR-T4-001 Meeting | `/presensi/dosen`, `/presensi/dosen/meeting` |
| FR-T4-002 / SR-T4-001 dua checkpoint | kartu "Presensi dosen" di halaman Meeting |
| FR-T4-003 / SR-T4-002 reminder 10 menit sebelum akhir | modal pada halaman Meeting |
| FR-T4-004 / SR-T4-003/004 presensi mandiri | kartu "Pintu absensi" (QR/Button), tutup otomatis saat berakhir |
| FR-T4-005, FR-T4-008 presensi manual + audit | tabel "Presensi mahasiswa" (kolom Catat/koreksi) |

## 4. Daftar file ditambah dan diubah (dibanding branch `feature/laravel-setup`)

Diubah (1): `routes/web.php`

Ditambah (18):
- Style: `public/css/sia-t4.css`
- Data dummy: `config/t4dummy.php`
- Helper: `app/Support/PresensiFormat.php`
- Layout: `resources/views/layouts/app.blade.php`
- Partial: `resources/views/presensi/_badge.blade.php`, `_icon.blade.php`, `_devstates.blade.php`, `_tabs.blade.php`
- Halaman: `resources/views/presensi/index.blade.php`, `riwayat.blade.php`, `dosen/index.blade.php`, `dosen/meeting.blade.php`, `dosen/rekap.blade.php`, `admin/index.blade.php`
- Test: `tests/Unit/Presensi/PresensiFormatTest.php`, `tests/Feature/Presensi/HalamanPresensiTest.php`
- Dokumentasi: `docs/T4-presensi.md`, `docs/T4-arsitektur.md`

Dihapus dibanding commit sebelumnya di branch `feature/t4-presensi` (versi awal dengan controller/session):
`app/Http/Controllers/Presensi/*` (3), `app/Services/Presensi/*` (4), `routes/presensi.php`, semua view lama di `resources/views/presensi/`, `tests/Unit/Presensi/*` lama (2), `tests/Feature/Presensi/*` lama (3).

## 5. Test

```bash
php artisan test --filter=Presensi
```
- Unit `PresensiFormatTest` (6): rumus persen, format Indonesia, ambang, rata-rata, pemetaan tone.
- Feature `HalamanPresensiTest` (12): semua halaman render, setiap state layar mahasiswa, tidak ada tombol absen di web mahasiswa, Meeting berlangsung/berakhir, rekap.

## 6. Open issue / catatan untuk tim

1. **Desain vs SRS**: desain mahasiswa web tidak punya tombol presensi ("presensi dicatat dosen", QR di aplikasi mobile nanti), sedangkan FR-T4-004 menyebut mahasiswa presensi QR/Button. Web mengikuti desain; alur QR/Button mahasiswa dibuat di tahap berikutnya.
2. **Kontrak API berbeda**: desain memakai `GET /attendances/summary` (+ `isEligibleForExam`); SRS memakai `/api/v1/attendance/recap/student/{id}` dan `/api/v1/meetings/...`. Perlu disepakati dengan T7. Riwayat per pertemuan dan "kelas hari ini" belum ada di kontrak.
3. Ambang syarat ujian (75%) hanyalah placeholder, perlakuan sakit/izin belum diputuskan (SRS bagian 7.3 no. 3).
4. Ikon sementara SVG sederhana; panduan memakai Phosphor Regular 16/20.
5. Font dimuat dari Google Fonts; jika offline akan memakai font bawaan sistem.
