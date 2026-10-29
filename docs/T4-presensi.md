# Dokumentasi Pekerjaan T4 — Presensi

Referensi: SRS-SIA-00 (FR-008, BR-005/006/007, SEC-001/002) dan hasil diskusi T4 (3 Oktober 2026).
Status: **frontend + route + logika awal dengan data dummy**. Belum terhubung ke DB/auth (menunggu T1–T3).

## 1. Yang dikerjakan

| # | Pekerjaan | File |
|---|-----------|------|
| 1 | Route modul presensi (dipisah supaya minim konflik merge; `web.php` hanya `require` 1 baris) | `routes/presensi.php`, `routes/web.php` |
| 2 | Layout sementara (header navy, tab menu, flash message) mengikuti desain Dashboard | `resources/views/layouts/app.blade.php` |
| 3 | Halaman Mahasiswa: rekap presensi per mata kuliah | `presensi/mahasiswa/index.blade.php` |
| 4 | Halaman Mahasiswa: absen (tombol biasa + kode QR/token) | `presensi/mahasiswa/absen.blade.php` |
| 5 | Halaman Dosen: daftar sesi hari ini | `presensi/dosen/index.blade.php` |
| 6 | Halaman Dosen: kelola sesi (Mulai/Selesai kuliah, token QR, ubah status mahasiswa, reminder) | `presensi/dosen/sesi.blade.php` |
| 7 | Halaman Dosen: rekap per mata kuliah | `presensi/dosen/rekap.blade.php` |
| 8 | Halaman Admin: monitoring presensi | `presensi/admin/index.blade.php` |
| 9 | Controller (Mahasiswa/Dosen/Admin) | `app/Http/Controllers/Presensi/*` |
| 10 | Logika: rekap, aturan reminder, status, penyimpanan sementara | `app/Services/Presensi/*` |
| 11 | Test case (unit + feature) | `tests/Unit/Presensi/*`, `tests/Feature/Presensi/*` |

## 2. Daftar route

| Method | URL | Nama | Fungsi |
|---|---|---|---|
| GET | `/presensi` | `presensi.index` | redirect ke halaman mahasiswa |
| GET | `/presensi/mahasiswa` | `presensi.mahasiswa.index` | rekap presensi mahasiswa |
| GET | `/presensi/mahasiswa/absen` | `presensi.mahasiswa.absen` | halaman absen |
| POST | `/presensi/mahasiswa/absen/{session}` | `presensi.mahasiswa.absen.store` | submit absen (`method`: button/qr) |
| GET | `/presensi/dosen` | `presensi.dosen.index` | sesi hari ini |
| GET | `/presensi/dosen/rekap` | `presensi.dosen.rekap` | rekap per mata kuliah |
| GET | `/presensi/dosen/sesi/{session}` | `presensi.dosen.sesi` | kelola sesi |
| POST | `/presensi/dosen/sesi/{session}/mulai` | `presensi.dosen.sesi.mulai` | cek 1: mulai kuliah |
| POST | `/presensi/dosen/sesi/{session}/selesai` | `presensi.dosen.sesi.selesai` | cek 2: selesai kuliah |
| POST | `/presensi/dosen/sesi/{session}/tunda` | `presensi.dosen.sesi.tunda` | "Ingatkan nanti" |
| GET | `/presensi/dosen/sesi/{session}/reminder` | `presensi.dosen.sesi.reminder` | JSON `{remind: bool}` (dipolling 30 detik) |
| POST | `/presensi/dosen/sesi/{session}/mahasiswa/{nim}` | `presensi.dosen.sesi.mark` | ubah status mahasiswa |
| GET | `/presensi/admin` | `presensi.admin.index` | monitoring |

## 3. Aturan yang diimplementasikan

- Absen mahasiswa hanya bisa saat sesi `berlangsung` (dosen sudah menekan Mulai Kuliah).
- Absen QR wajib token yang cocok (tidak case-sensitive); satu mahasiswa hanya bisa absen sekali per sesi.
- Dosen bisa mengubah status (Hadir/Sakit/Izin/Alpa) kapan saja, termasuk setelah kuliah selesai.
- Reminder muncul **10 menit setelah jam kuliah berakhir** sampai dosen menekan "Selesaikan Kuliah"; "Ingatkan nanti" menunda 5 menit.
- Rekap memakai key `attendedSessions`, `sickSessions`, `absentSessions`, `permissionSessions`, `totalPercentage`.
- Persentase mata kuliah (dosen) = rata-rata `totalPercentage` seluruh mahasiswa.

## 4. Asumsi sementara (perlu konfirmasi)

1. `totalPercentage` = Hadir / total pertemuan tercatat (Sakit & Izin tidak dihitung hadir).
2. Saat kuliah diselesaikan, mahasiswa yang belum absen otomatis **Alpa**.
3. Snooze = 5 menit; batas kehadiran rendah = 75%.
4. Data mahasiswa/jadwal/mata kuliah dummy; mahasiswa login dianggap `115240001`.

## 5. TODO saat modul lain siap

- Ganti `AttendanceStore` dengan model/tabel nyata (+ migration) dan API sesi per mata kuliah (usulan T4).
- Pasang middleware auth + role (SEC-001/002) pada tiap grup route di `routes/presensi.php`.
- Render QR sungguhan (mis. package `simplesoftwareio/simple-qrcode`) dan buat token kedaluwarsa.
- Ganti layout sementara dengan layout bersama bila sudah ada; hapus `_tabs.blade.php` (tab per peran).

## 6. Menjalankan test

```bash
composer install
php artisan test --filter=Presensi
```

Test case: perhitungan rekap (4), aturan reminder (4), route/halaman (3), absen mahasiswa (5), kontrol sesi dosen (6).

## 7. Daftar file yang ditambah dan diubah

### Diubah (1 file)
- `routes/web.php` — hanya menambah 1 baris `require __DIR__.'/presensi.php';`

### Ditambah (23 file)
Route:
- `routes/presensi.php`

Controller:
- `app/Http/Controllers/Presensi/MahasiswaPresensiController.php`
- `app/Http/Controllers/Presensi/DosenPresensiController.php`
- `app/Http/Controllers/Presensi/AdminPresensiController.php`

Service / logika:
- `app/Services/Presensi/AttendanceStatus.php`
- `app/Services/Presensi/AttendanceStore.php`
- `app/Services/Presensi/RecapCalculator.php`
- `app/Services/Presensi/ReminderPolicy.php`

View:
- `resources/views/layouts/app.blade.php`
- `resources/views/presensi/_tabs.blade.php`
- `resources/views/presensi/_badge.blade.php`
- `resources/views/presensi/mahasiswa/index.blade.php`
- `resources/views/presensi/mahasiswa/absen.blade.php`
- `resources/views/presensi/dosen/index.blade.php`
- `resources/views/presensi/dosen/sesi.blade.php`
- `resources/views/presensi/dosen/rekap.blade.php`
- `resources/views/presensi/admin/index.blade.php`

Test:
- `tests/Unit/Presensi/RecapCalculatorTest.php`
- `tests/Unit/Presensi/ReminderPolicyTest.php`
- `tests/Feature/Presensi/PresensiRouteTest.php`
- `tests/Feature/Presensi/MahasiswaAbsenTest.php`
- `tests/Feature/Presensi/DosenSesiTest.php`

Dokumentasi:
- `docs/T4-presensi.md` (file ini)
