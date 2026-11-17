# API T4 — Modul Presensi (untuk pengujian Tim 8)

Dasar: Tugas-09 Tim 4 (11 endpoint, istilah **Meeting**), SIA-API-INT v1.0 dan v1.1 (envelope, auth, aturan penamaan), SRS-SIA-T4 v1.1, `docs/T4-arsitektur.md`.
Status: API berjalan dengan database SQLite lokal + data seed. Belum terhubung ke UI, T1 (Auth), T2 (peserta kelas), T3 (jadwal).

## 1. Menjalankan

```bash
# dari folder project (setelah zip diekstrak, menimpa bootstrap/app.php dan database/seeders/DatabaseSeeder.php)
cp -n .env.example .env && php artisan key:generate     # bila belum ada
touch database/database.sqlite                           # bila belum ada
php artisan migrate:fresh --seed                         # catat password yang tampil di terminal
php artisan serve                                        # http://localhost:8000
```

- Base URL: `http://localhost:8000/api/v1`
- Password akun seed dibuat acak dan **hanya tampil di terminal** saat `db:seed` (SEC-INT-001: tidak ada kredensial di repo). Untuk password tetap, isi `T4_DEV_PASSWORD=...` di `.env` sebelum seed.
- Ingin mengulang skenario dari awal: jalankan lagi `php artisan migrate:fresh --seed`.
- Agar Tim 8 bisa memakai URL dari komputer lain: `php artisan serve --host=0.0.0.0` (atau tunnel), lalu ganti `baseUrl` di Postman.
- Test otomatis: `php artisan test --filter=PresensiApiTest` (31 test).
- Koleksi Postman: `docs/T4-postman-collection.json` (isi variabel `devPassword`, jalankan folder "0. Login" dulu). Spesifikasi OpenAPI 3.0: `docs/T4-openapi.yaml` (bisa dibuka di editor.swagger.io).

## 2. Akun seed

Login: `POST /api/v1/auth/login` dengan `{"username": "...", "password": "..."}`. Username = NIM/NIP.

| Username | Role | Keterangan |
|---|---|---|
| `DSN001` | LECTURER | Dr. Rina Kartika — pengampu IF201 |
| `DSN002` | LECTURER | Agus Prasetyo — pengampu IF203 |
| `11520001` | STUDENT | Budi Santoso — terdaftar IF201 dan IF203 |
| `11520002` | STUDENT | Siti Aminah — terdaftar IF201 |
| `11520003` | STUDENT | Andi Wijaya — terdaftar IF203 saja (TIDAK di IF201) |
| `KPR001` | KAPRODI | "Prodi" pada Tugas-09 |
| `ADM001` | ADMIN_ACADEMIC | "Prodi" pada Tugas-09 |

## 3. ID seed

Semua ID berformat `<prefix>_00000000-0000-4000-8000-0000000000NN`. Contoh: `mtg_00000000-0000-4000-8000-000000000001`.

| ID | Isi |
|---|---|
| `crs_…01` | IF201 Pemrograman Web (DSN001) |
| `crs_…02` | IF203 Basis Data (DSN002) |
| `std_…01` / `…02` / `…03` | Budi / Siti / Andi |
| `att_…01` | Budi, Meeting …04, ATTENDED (untuk uji koreksi) |

| Meeting | Skenario yang diuji |
|---|---|
| `mtg_…01` | IF201, Active, endTime jauh ke depan, pintu **terbuka**, metode BOTH. Skenario sukses presensi mandiri (QR/BUTTON), checkpoint start/end |
| `mtg_…02` | IF201, Active tetapi **endTime sudah lewat** → presensi mandiri `422 MEETING_ENDED` |
| `mtg_…03` | IF201, Active, pintu **tertutup** → `422 ATTENDANCE_CLOSED` |
| `mtg_…04` `…05` `…06` | IF201, sudah **Ended**; berisi catatan presensi (dasar rekap) |
| `mtg_…07` | IF201, Active, sudah checkpoint 1, metode **QR saja** → BUTTON ditolak `422 ATTENDANCE_METHOD_NOT_ALLOWED` |
| `mtg_…08` | IF203 milik DSN002 → DSN001 mendapat `403 NOT_MEETING_LECTURER` |

Rekap awal IF201: Budi 4 dari 5 pertemuan = 80% (layak), Siti 1 dari 5 = 20%, rata-rata 50%.

## 4. Endpoint

Header wajib untuk endpoint terproteksi: `Authorization: Bearer <accessToken>`. Body JSON (`Content-Type: application/json`).

| # | Method dan path | Role | Sukses | Error |
|---|---|---|---|---|
| 1 | `POST /meetings` | LECTURER (pengampu mata kuliah) | 201 | 400, 401, 403, 404, 422 |
| 2 | `PATCH /meetings/{meetingId}/attendance` | LECTURER (pengampu) | 200 | 400, 401, 403, 404, 422 |
| 3 | `POST /meetings/{meetingId}/start` | LECTURER (pengampu) | 200 | 400, 401, 403, 404, 422 |
| 4 | `POST /meetings/{meetingId}/end` | LECTURER (pengampu) | 200 | 400, 401, 403, 404, 422 |
| 5 | `GET /meetings/{meetingId}/lecturer-attendance` | LECTURER (pengampu), KAPRODI, ADMIN_ACADEMIC | 200 | 400, 401, 403, 404 |
| 6 | `GET /meetings/{meetingId}/qr` | LECTURER (pengampu) | 200 | 400, 401, 403, 404, 422 |
| 7 | `POST /attendance/submit` | STUDENT | 201 | 400, 401, 403, 404, 422 |
| 8 | `POST /meetings/{meetingId}/attendance/manual` | LECTURER (pengampu) | 201 (baru) / 200 (memperbarui) | 400, 401, 403, 404, 422 |
| 9 | `PUT /attendance/{attendanceId}/correction` | LECTURER (pengampu) | 200 | 400, 401, 403, 404, 422 |
| 10 | `GET /attendance/recap/student/{studentId}?courseId=` | STUDENT (milik sendiri), LECTURER | 200 | 400, 401, 403, 404 |
| 11 | `GET /attendance/recap/course/{courseId}?page=&limit=` | LECTURER (pengampu), KAPRODI, ADMIN_ACADEMIC | 200 | 400, 401, 403, 404 |
| + | `POST /auth/login` | publik (stand-in T1) | 200 | 400, 401 |
| + | `GET /meetings/{meetingId}` | LECTURER (pengampu), KAPRODI, ADMIN_ACADEMIC | 200 | 400, 401, 403, 404 |
| + | `GET /attendances/summary?studentId=&classOfferingId=` | STUDENT (sendiri), LECTURER, KAPRODI, ADMIN_ACADEMIC | 200 | 400, 401, 403, 404 |

Semua endpoint juga bisa mengembalikan `429` (rate limit 100 request/menit per IP) dan `500`.
Urutan pemeriksaan: token (401) → role (403) → format body/ID (400) → data ditemukan (404) → kepemilikan/pengampu (403) → aturan bisnis (422).

### Body request

| Endpoint | Body |
|---|---|
| 1 | `courseId`*, `startTime`*, `endTime`* (ISO 8601 dengan zona, mis. `2026-12-01T08:00:00+07:00` atau `...Z`), `scheduleId`, `topic`, `attendanceMethod` (`QR`/`BUTTON`/`BOTH`, default `BOTH`). Meeting baru berstatus `ACTIVE` dengan pintu **tertutup**; `meetingNumber` otomatis |
| 2 | `attendanceOpen` (boolean) dan/atau `attendanceMethod`; minimal salah satu |
| 3, 4, 6 | tanpa body |
| 7 | `meetingId`*, `method`* (`QR`/`BUTTON`), `qrToken` (wajib bila `method=QR`). `studentId` **diambil dari token**, bukan dari body |
| 8 | `studentId`*, `status`* (`ATTENDED`/`SICK`/`ABSENT`/`PERMISSION`). `confirmedBy` diambil dari token. Boleh dipakai walau Meeting sudah berakhir |
| 9 | `status`* (nilai sama seperti #8) |

### Contoh respons

Sukses (data tunggal): `{"success": true, "code": 201, "message": "...", "data": {...}, "meta": null}`
Sukses (daftar, #11): `data.students` berisi baris rekap dan `meta` berisi `page`, `limit`, `totalItems`, `totalPages`.
Gagal:
```json
{"success": false, "code": 422, "message": "QR Code kedaluwarsa atau tidak valid.",
 "error": {"type": "BUSINESS_RULE_VIOLATION", "code": "QR_EXPIRED", "details": []}}
```

Rekap (#10/#11) per mata kuliah:
```json
{"totalSessions": 5,
 "attendedDetail": {"attendedSessions": 4, "sickSessions": 0, "absentSessions": 1, "permissionSessions": 0},
 "totalPercentage": 80, "isEligibleForExam": true}
```

## 5. Kode error machine-readable (`error.code`)

| Status | Kode |
|---|---|
| 400 | `INVALID_JSON`, `MISSING_FIELD`, `INVALID_FIELD_TYPE`, `INVALID_ID_FORMAT`, `INVALID_DATETIME`, `INVALID_QUERY` |
| 401 | `TOKEN_MISSING`, `TOKEN_INVALID`, `TOKEN_EXPIRED`, `INVALID_CREDENTIALS` (login) |
| 403 | `ROLE_NOT_ALLOWED`, `NOT_MEETING_LECTURER`, `NOT_COURSE_LECTURER`, `NOT_OWN_DATA`, `LECTURER_PROFILE_NOT_FOUND`, `STUDENT_PROFILE_NOT_FOUND` |
| 404 | `MEETING_NOT_FOUND`, `COURSE_NOT_FOUND`, `STUDENT_NOT_FOUND`, `ATTENDANCE_NOT_FOUND`, `RESOURCE_NOT_FOUND` (route tidak ada) |
| 405 | `METHOD_NOT_ALLOWED` |
| 422 | `MEETING_ENDED`, `ATTENDANCE_CLOSED`, `QR_EXPIRED`, `ATTENDANCE_DUPLICATE` (4 kode usulan Tugas-09), `STUDENT_NOT_ENROLLED`, `ATTENDANCE_METHOD_NOT_ALLOWED`, `QR_NOT_ENABLED`, `CHECKPOINT_1_REQUIRED`, `STATUS_UNCHANGED`, `VALIDATION_FAILED` |
| 429 | `RATE_LIMIT_EXCEEDED` |
| 500 | `INTERNAL_ERROR` (tanpa stack trace) |

`error.type`: `BAD_REQUEST`, `AUTHENTICATION_FAILED`, `FORBIDDEN`, `NOT_FOUND`, `VALIDATION_ERROR` (nilai field di luar yang diizinkan), `BUSINESS_RULE_VIOLATION`, `TOO_MANY_REQUESTS`, `INTERNAL_SERVER_ERROR`.

## 6. Keputusan dan asumsi (perlu disepakati dengan T7/Tim 8)

Ini bagian yang berbeda dari kontrak SIA-API-INT atau tidak tertulis di Tugas-09. Semua mudah diubah.

1. **Prefix `/attendance` vs `/attendances`**: kontrak T7 memakai `/attendances/sessions`, Tugas-09 memakai `/meetings` dan `/attendance/...`. API mengikuti Tugas-09. `/attendances/summary` (kontrak v1.1 bagian 6.4, dikonsumsi T5) ditambahkan sebagai endpoint tambahan; `classOfferingId` diperlakukan sebagai `courseId`.
2. **Envelope error memakai `error`** (objek `{type, code, details}`) sesuai SIA-API-INT bagian 4.3. Tugas-09 menulis `errors`. Kode machine-readable diletakkan di `error.code`.
3. **Respons 401 dan 403** memakai `error.type` `AUTHENTICATION_FAILED` / `FORBIDDEN`. Respons sukses selalu memuat `meta` (null bila bukan daftar).
4. **400 vs 422** (Tugas-09 Table 1): field wajib hilang, tipe salah, JSON rusak, format ID/tanggal salah → 400. Nilai enum di luar yang diizinkan, `endTime <= startTime` → 422 `VALIDATION_FAILED`.
5. **Role "Prodi"** dipetakan ke `KAPRODI` dan `ADMIN_ACADEMIC` (glosarium kontrak v1.1).
6. **Format ID**: `<prefix>_<uuid>` (`mtg_`, `att_`, `std_`, `crs_`, `lec_`, `usr_`) mengikuti gaya contoh kontrak. ID yang formatnya salah → 400, format benar tapi tidak ada → 404.
7. **Status presensi**: `ATTENDED`, `SICK`, `ABSENT`, `PERMISSION` (kontrak memakai `HADIR`; dipetakan ke `ATTENDED`).
8. **Meeting dianggap berakhir** bila `status=ENDED` (checkpoint 2) atau `endTime` terlewat (SR-T4-004). Respons selalu menampilkan status efektif.
9. **Checkpoint**: `POST /start` bersifat idempotent (200 berulang). `POST /end` sebelum `/start` → 422 `CHECKPOINT_1_REQUIRED`; `/end` kedua kali → 422 `MEETING_ENDED`. `/end` masih boleh dipanggil setelah `endTime` lewat selama belum diakhiri. `isPresent` = start DAN end (turunan).
10. **QR**: token HMAC berganti tiap 30 detik (`T4_QR_TTL`), token periode sebelumnya masih diterima sebagai toleransi. Arsitektur T4 menyebut 30 detik, kontrak v1.1 menyebut 5 detik.
11. **Peserta kelas** (Hal #4 Tugas-09): disimulasikan tabel `course_enrollments`. Mahasiswa tidak terdaftar → 422 `STUDENT_NOT_ENROLLED` (presensi mandiri dan manual).
12. **Rekap**: `totalSessions` = Meeting yang sudah berlangsung (sudah checkpoint 1, atau Ended, atau endTime lewat). `absentSessions` = pertemuan tanpa catatan hadir/sakit/izin. `totalPercentage` = hadir ÷ total × 100 (sakit dan izin belum dihitung hadir; SRS 7.3 no. 3 belum final). Ambang ujian 75% (placeholder, `T4_EXAM_THRESHOLD`).
13. **Auth** dibuat stand-in (JWT HS256, klaim sesuai kontrak v1.1 bagian 3.2) agar T4 bisa diuji tanpa Tim 1. Buang `AuthController`, tabel `api_users`, dan ganti `JwtAuth` dengan validasi token T1 bila Auth Service siap. Rate limit 100/menit per IP memakai middleware throttle.
14. **Presensi dosen manual/koreksi** menulis tabel `attendance_audits` (siapa, status lama dan baru, jenis aksi). Belum ada endpoint untuk membaca audit.
15. **Reminder 10 menit** (`reminderSent`) belum ada pemicunya; field selalu `false` (menunggu T6).

## 7. Daftar file

Diubah: `bootstrap/app.php` (routing API, alias middleware, format error), `database/seeders/DatabaseSeeder.php` (memanggil `T4Seeder`; user contoh bawaan dihapus).
Ditambah: `routes/api.php`, `config/t4.php`, `app/Http/Controllers/Api/V1/*` (4), `app/Http/Middleware/*` (3), `app/Models/T4/*` (8), `app/Services/Presensi/*` (3), `app/Support/Api/*` (6), migration `2026_11_17_000001_create_t4_attendance_tables.php`, `database/seeders/T4Seeder.php`, `tests/Feature/Api/PresensiApiTest.php`, `docs/T4-api.md`, `docs/T4-openapi.yaml`, `docs/T4-postman-collection.json`.

Variabel `.env` opsional: `T4_DEV_PASSWORD`, `T4_JWT_SECRET`, `T4_JWT_TTL` (detik, default 43200), `T4_QR_TTL` (default 30), `T4_EXAM_THRESHOLD` (default 75).
