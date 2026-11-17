<?php

/*
 | Konfigurasi API modul Presensi (T4). Nilai sensitif dibaca dari .env, tidak ditulis di sini (SEC-INT-001).
 */
return [
    // Token JWT (stand-in Tim 1 untuk keperluan pengujian). Bila T4_JWT_SECRET kosong, dipakai turunan APP_KEY.
    'jwt' => [
        'secret' => env('T4_JWT_SECRET'),
        'issuer' => env('T4_JWT_ISSUER', 'https://identity.sia.iti.ac.id'),
        'ttl' => (int) env('T4_JWT_TTL', 43200),
    ],

    // QR dinamis: token berganti setiap N detik (arsitektur T4 bagian 7: QR berkala 30 detik).
    'qr_ttl_seconds' => (int) env('T4_QR_TTL', 30),

    // Ambang syarat ujian (placeholder, SRS T4 bagian 7.3 no. 3 belum final).
    'exam_threshold' => (float) env('T4_EXAM_THRESHOLD', 75),

    // Password akun seed. Kosong = dibuat acak dan ditampilkan di terminal saat `db:seed`.
    'dev_password' => env('T4_DEV_PASSWORD'),
];
