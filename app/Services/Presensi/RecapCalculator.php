<?php

namespace App\Services\Presensi;

/**
 * Menghitung rekap presensi. Nama key mengikuti hasil diskusi T4:
 * attendedSessions, sickSessions, absentSessions, permissionSessions, totalPercentage.
 */
class RecapCalculator
{
    /**
     * @param  array<int, AttendanceStatus|string>  $statuses
     * @return array{attendedSessions:int, sickSessions:int, absentSessions:int, permissionSessions:int, totalSessions:int, totalPercentage:float}
     */
    public static function summarize(array $statuses): array
    {
        $counts = array_count_values(array_map(
            fn ($s) => $s instanceof AttendanceStatus ? $s->value : (string) $s,
            $statuses
        ));

        $total = count($statuses);
        $attended = $counts[AttendanceStatus::Hadir->value] ?? 0;

        return [
            'attendedSessions' => $attended,
            'sickSessions' => $counts[AttendanceStatus::Sakit->value] ?? 0,
            'absentSessions' => $counts[AttendanceStatus::Alpa->value] ?? 0,
            'permissionSessions' => $counts[AttendanceStatus::Izin->value] ?? 0,
            'totalSessions' => $total,
            // Asumsi: hanya "hadir" yang dihitung sebagai kehadiran (sakit/izin tidak).
            'totalPercentage' => $total > 0 ? round($attended / $total * 100, 1) : 0.0,
        ];
    }

    /**
     * Persentase kehadiran mata kuliah (untuk dosen) = rata-rata totalPercentage mahasiswa.
     *
     * @param  array<int, float|int>  $percentages
     */
    public static function average(array $percentages): float
    {
        return count($percentages) > 0
            ? round(array_sum($percentages) / count($percentages), 1)
            : 0.0;
    }
}
