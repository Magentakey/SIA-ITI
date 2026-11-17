<?php

namespace App\Support\Api;

use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;

/** Envelope respons global (SIA-API-INT bagian 4). */
class ApiResponse
{
    public static function ok(string $message, mixed $data = null, ?array $meta = null): JsonResponse
    {
        return self::success(200, $message, $data, $meta);
    }

    public static function created(string $message, mixed $data = null): JsonResponse
    {
        return self::success(201, $message, $data, null);
    }

    public static function success(int $code, string $message, mixed $data, ?array $meta): JsonResponse
    {
        return response()->json([
            'success' => true,
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
        ], $code);
    }

    /** @param array<int, array{field?: string, message: string}> $details */
    public static function error(int $code, string $message, string $type, string $errorCode, array $details = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => $code,
            'message' => $message,
            'error' => [
                'type' => $type,
                'code' => $errorCode,
                'details' => $details,
            ],
        ], $code);
    }

    /** ISO 8601 UTC: YYYY-MM-DDTHH:mm:ssZ */
    public static function iso(?CarbonInterface $time): ?string
    {
        return $time?->copy()->utc()->format('Y-m-d\TH:i:s\Z');
    }
}
