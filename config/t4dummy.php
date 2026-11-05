<?php

/*
 | Data DUMMY modul Presensi (T4) untuk pengembangan halaman. Bukan sumber kebenaran.
 | Saat API T1/T3/T4 siap, ganti pembacaan config('t4dummy...') di view dengan data dari service/API.
 | Istilah mengikuti SRS-SIA-T4 v1.1: Meeting (bukan Session).
 */
return [
    'periode' => '20261',

    // ---------------------------------------------------------------- Mahasiswa
    'mahasiswa' => [
        'periode' => '20261',
        'courses' => [
            'IF201' => ['name' => 'Pemrograman Web'],
            'IF203' => ['name' => 'Basis Data'],
            'IF209' => ['name' => 'Jaringan Komputer'],
            'IF205' => ['name' => 'Interaksi Manusia dan Komputer'],
            'IF207' => ['name' => 'Rekayasa Perangkat Lunak'],
            'IF211' => ['name' => 'Statistika'],
        ],
        // [hadir, sakit, izin, alpa, pertemuan]
        'recap' => [
            'IF201' => [9, 1, 0, 0, 10],
            'IF203' => [10, 0, 0, 0, 10],
            'IF209' => [8, 1, 1, 0, 10],
            'IF205' => [6, 1, 1, 2, 10],
            'IF207' => [10, 0, 0, 0, 10],
            'IF211' => [7, 1, 0, 2, 10],
        ],
        'table_note' => 'pertemuan yang sudah berlangsung per kelas',
        'states' => [
            'normal' => [
                'now' => 'Senin, 2 Nov 2026, 10.15 WIB', 'date' => 'Senin, 2 Nov 2026',
                'recap' => ['IF201' => [10, 1, 0, 0, 11]], 'highlight' => 'IF201',
                'table_note' => 'Pemrograman Web 11 pertemuan, kelas lain 10 pertemuan',
                'classes' => [
                    ['code' => 'IF201', 'meeting' => 11, 'time' => '07.30–10.00', 'room' => 'R. 2.04', 'lecturer' => 'Dr. Rina Kartika, M.Kom.', 'color' => 'var(--c-biru)', 'badge' => ['Hadir', 'success'], 'icon' => 'check-circle', 'note' => 'Sudah dipresensi dosen'],
                    ['code' => 'IF203', 'meeting' => 11, 'time' => '13.00–15.30', 'room' => 'Lab. Komputer 2', 'lecturer' => 'Agus Prasetyo, S.T., M.T.', 'color' => 'var(--c-hijau)', 'badge' => ['Belum dimulai', 'neutral'], 'icon' => 'clock', 'note' => 'Kelas mulai 13.00.'],
                ],
            ],
            'berlangsung' => [
                'now' => 'Senin, 2 Nov 2026, 08.10 WIB', 'date' => 'Senin, 2 Nov 2026',
                'recap' => ['IF201' => [9, 1, 0, 0, 10]], 'highlight' => null,
                'classes' => [
                    ['code' => 'IF201', 'meeting' => 11, 'time' => '07.30–10.00', 'room' => 'R. 2.04', 'lecturer' => 'Dr. Rina Kartika, M.Kom.', 'color' => 'var(--c-biru)', 'badge' => ['Belum dipresensi', 'warning'], 'icon' => 'info', 'note' => 'Kelas sedang berlangsung. Dosen belum mencatat kehadiran pertemuan 11.'],
                    ['code' => 'IF203', 'meeting' => 11, 'time' => '13.00–15.30', 'room' => 'Lab. Komputer 2', 'lecturer' => 'Agus Prasetyo, S.T., M.T.', 'color' => 'var(--c-hijau)', 'badge' => ['Belum dimulai', 'neutral'], 'icon' => 'clock', 'note' => 'Kelas mulai 13.00.'],
                ],
            ],
            'alpa' => [
                'now' => 'Senin, 2 Nov 2026, 10.15 WIB', 'date' => 'Senin, 2 Nov 2026',
                'recap' => ['IF201' => [9, 1, 0, 1, 11]], 'highlight' => 'IF201',
                'table_note' => 'Pemrograman Web 11 pertemuan, kelas lain 10 pertemuan',
                'classes' => [
                    ['code' => 'IF201', 'meeting' => 11, 'time' => '07.30–10.00', 'room' => 'R. 2.04', 'lecturer' => 'Dr. Rina Kartika, M.Kom.', 'color' => 'var(--c-biru)', 'badge' => ['Alpa', 'danger'], 'icon' => 'info', 'note' => 'Dicatat tidak hadir. Bila Anda hadir, temui dosen pengampu secara langsung.'],
                    ['code' => 'IF203', 'meeting' => 11, 'time' => '13.00–15.30', 'room' => 'Lab. Komputer 2', 'lecturer' => 'Agus Prasetyo, S.T., M.T.', 'color' => 'var(--c-hijau)', 'badge' => ['Belum dimulai', 'neutral'], 'icon' => 'clock', 'note' => 'Kelas mulai 13.00.'],
                ],
            ],
            'kosong' => [
                'now' => 'Jumat, 6 Nov 2026, 09.15 WIB', 'date' => 'Jumat, 6 Nov 2026',
                'recap' => ['IF201' => [9, 1, 0, 0, 10]], 'highlight' => null,
                'classes' => [],
                'next' => 'Kelas berikutnya: Sabtu, 7 Nov 2026, 08.00 · Statistika, R. 1.03.',
            ],
            'loading' => ['now' => 'Senin, 2 Nov 2026, 10.15 WIB'],
            'error' => ['now' => 'Senin, 2 Nov 2026, 10.15 WIB'],
        ],
        // Riwayat per pertemuan IF201 (belum ada di kontrak API, lihat docs)
        'riwayat' => [
            'code' => 'IF201', 'name' => 'Pemrograman Web', 'schedule' => 'Senin, 07.30–10.00 · R. 2.04 · Dr. Rina Kartika, M.Kom.',
            'meetings' => [
                [1, 'Sen, 24 Agu 2026', 'Hadir'], [2, 'Sen, 31 Agu 2026', 'Hadir'], [3, 'Sen, 7 Sep 2026', 'Hadir'],
                [4, 'Sen, 14 Sep 2026', 'Sakit'], [5, 'Sen, 21 Sep 2026', 'Hadir'], [6, 'Sen, 28 Sep 2026', 'Hadir'],
                [7, 'Sen, 5 Okt 2026', 'Hadir'], [8, 'Sen, 12 Okt 2026', 'Hadir'], [9, 'Sen, 19 Okt 2026', 'Hadir'],
                [10, 'Sen, 26 Okt 2026', 'Hadir'], [11, 'Sen, 2 Nov 2026', 'Hadir'],
            ],
        ],
    ],

    // ---------------------------------------------------------------- Dosen
    'dosen' => [
        'name' => 'Dr. Rina Kartika, M.Kom.',
        'date' => 'Senin, 2 Nov 2026',
        'classes' => [
            ['code' => 'IF201', 'name' => 'Pemrograman Web', 'class' => 'Kelas A', 'meeting' => 11, 'time' => '07.30–10.00', 'room' => 'R. 2.04', 'color' => 'var(--c-biru)', 'badge' => ['Berlangsung', 'info'], 'action' => 'Kelola pertemuan'],
            ['code' => 'IF201', 'name' => 'Pemrograman Web', 'class' => 'Kelas B', 'meeting' => 11, 'time' => '13.00–15.30', 'room' => 'R. 2.05', 'color' => 'var(--c-hijau)', 'badge' => ['Belum dimulai', 'neutral'], 'action' => 'Buka pertemuan'],
        ],
        // Satu Meeting (IF201 Kelas A, Pertemuan 11) pada tiga kondisi
        'meeting' => [
            'title' => 'Pemrograman Web', 'class' => 'Kelas A', 'code' => 'IF201', 'number' => 11,
            'info' => 'Senin, 2 Nov 2026 · 07.30–10.00 · R. 2.04',
            'students' => [
                ['115240001', 'Aditya Pratama'], ['115240002', 'Nadia Putri'], ['115240003', 'Bima Saputra'],
                ['112240001', 'Citra Lestari'], ['113230001', 'Dewi Anggraini'], ['121250001', 'Fajar Ramadhan'],
            ],
            'states' => [
                'belum' => [
                    'status' => ['Belum dimulai', 'neutral'], 'cp1' => null, 'cp2' => null, 'door' => false, 'method' => 'Keduanya',
                    'rows' => [],
                ],
                'berlangsung' => [
                    'status' => ['Berlangsung', 'info'], 'cp1' => '07.28', 'cp2' => null, 'door' => true, 'method' => 'Keduanya',
                    'rows' => [['Hadir', 'QR Code', '07.41'], ['Hadir', 'Button', '07.43'], ['Hadir', 'QR Code', '07.52']],
                ],
                'berakhir' => [
                    'status' => ['Berakhir', 'neutral'], 'cp1' => '07.28', 'cp2' => '10.02', 'door' => false, 'method' => 'Keduanya',
                    'rows' => [['Hadir', 'QR Code', '07.41'], ['Hadir', 'Button', '07.43'], ['Hadir', 'QR Code', '07.52'], ['Sakit', 'Manual', '10.20'], ['Alpa', 'Manual', '10.21'], ['Hadir', 'Button', '07.58']],
                ],
            ],
        ],
        // Rekap IF201 per mahasiswa: [hadir, sakit, izin, alpa]
        'rekap' => [
            'code' => 'IF201', 'name' => 'Pemrograman Web', 'meetings' => 10,
            'students' => [
                ['115240001', 'Aditya Pratama', [8, 1, 1, 0]], ['115240002', 'Nadia Putri', [10, 0, 0, 0]],
                ['115240003', 'Bima Saputra', [9, 1, 0, 0]], ['112240001', 'Citra Lestari', [7, 0, 1, 2]],
                ['113230001', 'Dewi Anggraini', [5, 1, 0, 4]], ['121250001', 'Fajar Ramadhan', [9, 0, 1, 0]],
            ],
        ],
    ],

    // ---------------------------------------------------------------- BAAK / Admin
    'admin' => [
        // [kode, mata kuliah, dosen, jumlah mahasiswa, pertemuan, rata-rata kehadiran, di bawah ambang]
        'courses' => [
            ['IF201', 'Pemrograman Web', 'Dr. Rina Kartika, M.Kom.', 40, 10, 80.0, 6],
            ['IF203', 'Basis Data', 'Agus Prasetyo, S.T., M.T.', 38, 10, 92.5, 1],
            ['IF205', 'Interaksi Manusia dan Komputer', 'Dewi Lestari, M.T.', 35, 10, 71.4, 11],
            ['IF207', 'Rekayasa Perangkat Lunak', 'Budi Hartono, M.Kom.', 36, 10, 88.9, 3],
            ['IF209', 'Jaringan Komputer', 'Hendra Wijaya, M.T.', 34, 10, 84.1, 4],
            ['IF211', 'Statistika', 'Sri Wahyuni, M.Si.', 40, 10, 68.3, 15],
        ],
    ],
];
