<?php

namespace App\Services\Presensi;

/**
 * Token QR dinamis tanpa penyimpanan: HMAC(meetingId, slot waktu). Berganti tiap t4.qr_ttl_seconds.
 * Validasi menerima slot sekarang dan slot sebelumnya (toleransi scan di batas pergantian).
 */
class QrToken
{
    private static function ttl(): int
    {
        return max(1, (int) config('t4.qr_ttl_seconds'));
    }

    private static function slot(?int $time = null): int
    {
        return intdiv($time ?? time(), self::ttl());
    }

    private static function make(string $meetingId, int $slot): string
    {
        $mac = hash_hmac('sha256', "{$meetingId}|{$slot}", hash('sha256', 'qr|'.config('app.key')));

        return 'qr_'.substr($mac, 0, 32);
    }

    /** @return array{qrToken: string, refreshIntervalSeconds: int, expiresAt: int} */
    public static function current(string $meetingId): array
    {
        $slot = self::slot();

        return [
            'qrToken' => self::make($meetingId, $slot),
            'refreshIntervalSeconds' => self::ttl(),
            'expiresAt' => ($slot + 1) * self::ttl(),
        ];
    }

    public static function isValid(string $meetingId, string $token): bool
    {
        $slot = self::slot();

        return hash_equals(self::make($meetingId, $slot), $token)
            || hash_equals(self::make($meetingId, $slot - 1), $token);
    }
}
