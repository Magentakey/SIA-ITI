<?php

namespace App\Services\Presensi;

use Carbon\CarbonInterface;

/**
 * Aturan reminder untuk dosen: 10 menit setelah jam kuliah berakhir, reminder terus
 * muncul sampai dosen menekan "Selesaikan Kuliah". "Ingatkan nanti" menunda sementara.
 */
class ReminderPolicy
{
    public const DELAY_MINUTES = 10;

    public const SNOOZE_MINUTES = 5; // asumsi, belum diputuskan tim

    public static function shouldRemind(
        CarbonInterface $endsAt,
        bool $finished,
        ?CarbonInterface $snoozedUntil,
        CarbonInterface $now
    ): bool {
        if ($finished) {
            return false;
        }

        if ($now->lt($endsAt->copy()->addMinutes(self::DELAY_MINUTES))) {
            return false;
        }

        return $snoozedUntil === null || $now->gte($snoozedUntil);
    }
}
