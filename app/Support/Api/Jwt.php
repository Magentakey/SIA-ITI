<?php

namespace App\Support\Api;

/**
 * JWT HS256 sederhana. Stand-in untuk Auth Service Tim 1 agar T4 bisa diuji terisolasi.
 * Klaim mengikuti SIA-API-INT v1.1 bagian 3.2 (iss, sub, identifier, roles, permissions, iat, exp, jti).
 */
class Jwt
{
    private static function secret(): string
    {
        $secret = config('t4.jwt.secret') ?: hash('sha256', 'jwt|'.config('app.key'));

        return (string) $secret;
    }

    private static function b64(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private static function unb64(string $text): string|false
    {
        return base64_decode(strtr($text, '-_', '+/'), true);
    }

    /** @param array<string, mixed> $claims */
    public static function encode(array $claims): string
    {
        $header = self::b64(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = self::b64(json_encode($claims));
        $signature = self::b64(hash_hmac('sha256', "{$header}.{$payload}", self::secret(), true));

        return "{$header}.{$payload}.{$signature}";
    }

    /** @return array<string, mixed> */
    public static function decode(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw ApiException::unauthorized('TOKEN_INVALID', 'Sesi telah berakhir atau token tidak valid.');
        }

        [$header, $payload, $signature] = $parts;
        $expected = self::b64(hash_hmac('sha256', "{$header}.{$payload}", self::secret(), true));
        $headerJson = self::unb64($header);
        $payloadJson = self::unb64($payload);
        $headerData = $headerJson === false ? null : json_decode($headerJson, true);
        $claims = $payloadJson === false ? null : json_decode($payloadJson, true);

        if (! hash_equals($expected, $signature)
            || ! is_array($headerData) || ($headerData['alg'] ?? null) !== 'HS256'
            || ! is_array($claims)) {
            throw ApiException::unauthorized('TOKEN_INVALID', 'Sesi telah berakhir atau token tidak valid.');
        }

        if (! isset($claims['exp']) || ! is_numeric($claims['exp']) || $claims['exp'] < time()) {
            throw ApiException::unauthorized('TOKEN_EXPIRED', 'Sesi telah berakhir. Silakan login kembali.');
        }

        return $claims;
    }
}
