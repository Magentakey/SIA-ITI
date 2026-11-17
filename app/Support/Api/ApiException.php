<?php

namespace App\Support\Api;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * Kesalahan API yang dipetakan ke envelope error standar (SIA-API-INT bagian 4.3).
 *
 * $type  : kategori ("BAD_REQUEST", "VALIDATION_ERROR", "BUSINESS_RULE_VIOLATION", ...)
 * $errorCode : kode machine-readable ("QR_EXPIRED", "MEETING_ENDED", ...)
 */
class ApiException extends Exception
{
    /** @param array<int, array{field?: string, message: string}> $details */
    public function __construct(
        public readonly int $status,
        public readonly string $type,
        public readonly string $errorCode,
        string $message,
        public readonly array $details = [],
    ) {
        parent::__construct($message);
    }

    public static function badRequest(string $errorCode, string $message, array $details = []): self
    {
        return new self(400, 'BAD_REQUEST', $errorCode, $message, $details);
    }

    public static function unauthorized(string $errorCode, string $message): self
    {
        return new self(401, 'AUTHENTICATION_FAILED', $errorCode, $message);
    }

    public static function forbidden(string $errorCode, string $message): self
    {
        return new self(403, 'FORBIDDEN', $errorCode, $message);
    }

    public static function notFound(string $errorCode, string $message): self
    {
        return new self(404, 'NOT_FOUND', $errorCode, $message);
    }

    /** 422 untuk nilai field yang tidak diizinkan. */
    public static function validation(string $message, array $details): self
    {
        return new self(422, 'VALIDATION_ERROR', 'VALIDATION_FAILED', $message, $details);
    }

    /** 422 untuk pelanggaran aturan bisnis. */
    public static function rule(string $errorCode, string $message): self
    {
        return new self(422, 'BUSINESS_RULE_VIOLATION', $errorCode, $message);
    }

    public function render(): JsonResponse
    {
        return ApiResponse::error($this->status, $this->message, $this->type, $this->errorCode, $this->details);
    }
}
