<?php

namespace App\Support;

/**
 * Helper murni (tanpa framework) untuk tampilan presensi T4.
 * Rumus mengikuti SRS-SIA-T4: totalPercentage = hadir / total pertemuan yang telah berakhir x 100 (SR-T4-009).
 */
class PresensiFormat
{
    /** Ambang syarat ujian. OPEN ISSUE: belum ditetapkan PO/Prodi, nilai ini hanya placeholder. */
    public const PASSING_PERCENT = 75.0;

    /** Peta status (label UI) ke tone badge sesuai panduan "Pemetaan status" pada 00 Foundation. */
    private const TONES = ['Hadir' => 'success', 'Sakit' => 'info', 'Izin' => 'warning', 'Alpa' => 'danger'];

    public static function percent(int $attended, int $total): float
    {
        return $total > 0 ? round($attended / $total * 100, 2) : 0.0;
    }

    /** Format Indonesia tanpa nol berlebih: 90,91 / 90 / 100. */
    public static function label(float $percent): string
    {
        return rtrim(rtrim(number_format($percent, 2, ',', '.'), '0'), ',');
    }

    public static function isEligible(float $percent, float $threshold = self::PASSING_PERCENT): bool
    {
        return $percent >= $threshold;
    }

    /** Rata-rata persentase (SR-T4-007). */
    public static function average(array $percentages): float
    {
        return count($percentages) > 0 ? round(array_sum($percentages) / count($percentages), 2) : 0.0;
    }

    public static function tone(string $status): string
    {
        return self::TONES[$status] ?? 'neutral';
    }
}
