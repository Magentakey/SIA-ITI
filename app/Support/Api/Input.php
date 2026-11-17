<?php

namespace App\Support\Api;

use Carbon\CarbonImmutable;
use Throwable;

/**
 * Pembaca input body. Aturan pemetaan (Tugas-09 Table 1):
 *  - field wajib hilang / tipe tidak terbaca / format salah -> 400
 *  - nilai di luar himpunan yang diizinkan, atau aturan nilai gagal -> 422
 */
class Input
{
    /** @param array<string, mixed> $data */
    public static function requireString(array $data, string $field, int $max = 255): string
    {
        if (! array_key_exists($field, $data) || $data[$field] === null || $data[$field] === '') {
            throw ApiException::badRequest('MISSING_FIELD', "Field {$field} wajib diisi.", [['field' => $field, 'message' => 'Wajib diisi.']]);
        }

        return self::string($data, $field, $max);
    }

    /** @param array<string, mixed> $data */
    public static function optionalString(array $data, string $field, int $max = 255): ?string
    {
        if (! array_key_exists($field, $data) || $data[$field] === null) {
            return null;
        }

        return self::string($data, $field, $max);
    }

    /** @param array<string, mixed> $data */
    private static function string(array $data, string $field, int $max): string
    {
        $value = $data[$field];
        if (! is_string($value)) {
            throw ApiException::badRequest('INVALID_FIELD_TYPE', "Field {$field} harus berupa string.", [['field' => $field, 'message' => 'Harus berupa string.']]);
        }
        if (mb_strlen($value) > $max) {
            throw ApiException::validation('Validasi permintaan gagal.', [['field' => $field, 'message' => "Maksimal {$max} karakter."]]);
        }

        return trim($value);
    }

    /** @param array<string, mixed> $data */
    public static function optionalBool(array $data, string $field): ?bool
    {
        if (! array_key_exists($field, $data)) {
            return null;
        }
        if (! is_bool($data[$field])) {
            throw ApiException::badRequest('INVALID_FIELD_TYPE', "Field {$field} harus berupa boolean.", [['field' => $field, 'message' => 'Harus berupa boolean (true/false).']]);
        }

        return $data[$field];
    }

    /** @param array<int, string> $allowed */
    public static function enum(string $value, string $field, array $allowed): string
    {
        $upper = strtoupper($value);
        if (! in_array($upper, $allowed, true)) {
            throw ApiException::validation('Validasi permintaan gagal.', [[
                'field' => $field,
                'message' => 'Nilai harus salah satu dari: '.implode(', ', $allowed).'.',
            ]]);
        }

        return $upper;
    }

    /** Parse ISO 8601 (wajib menyertakan zona, mis. Z atau +07:00). */
    public static function datetime(string $value, string $field): CarbonImmutable
    {
        $valid = preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?(Z|[+-]\d{2}:\d{2})$/', $value) === 1;
        if ($valid) {
            try {
                return CarbonImmutable::parse($value)->utc();
            } catch (Throwable) {
                $valid = false;
            }
        }

        throw ApiException::badRequest('INVALID_DATETIME', "Format {$field} tidak valid.", [['field' => $field, 'message' => 'Harus ISO 8601, contoh 2026-11-20T08:00:00Z.']]);
    }
}
