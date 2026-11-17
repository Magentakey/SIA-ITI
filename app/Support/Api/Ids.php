<?php

namespace App\Support\Api;

use Illuminate\Support\Str;

/** Pembuatan dan validasi format ID: <prefix>_<uuid>. */
class Ids
{
    public const PATTERN = '/^%s_[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    public static function make(string $prefix): string
    {
        return $prefix.'_'.Str::uuid()->toString();
    }

    /** Lempar 400 bila format ID salah. Mengembalikan ID apa adanya. */
    public static function assert(string $prefix, mixed $value, string $field): string
    {
        if (! is_string($value) || ! preg_match(sprintf(self::PATTERN, preg_quote($prefix, '/')), $value)) {
            throw ApiException::badRequest(
                'INVALID_ID_FORMAT',
                "Format {$field} tidak valid.",
                [['field' => $field, 'message' => "Harus berformat {$prefix}_<uuid>."]],
            );
        }

        return $value;
    }
}
