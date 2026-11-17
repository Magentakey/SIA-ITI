<?php

namespace App\Support\Api;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/** Memetakan exception framework ke envelope error. Tidak pernah membocorkan stack trace (NFR-007). */
class ErrorRenderer
{
    public static function render(Throwable $e): JsonResponse
    {
        if ($e instanceof ApiException) {
            return $e->render();
        }

        if ($e instanceof ValidationException) {
            $details = [];
            foreach ($e->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $details[] = ['field' => $field, 'message' => $message];
                }
            }

            return ApiResponse::error(422, 'Validasi permintaan gagal.', 'VALIDATION_ERROR', 'VALIDATION_FAILED', $details);
        }

        if ($e instanceof AuthenticationException) {
            return ApiResponse::error(401, 'Sesi telah berakhir atau token tidak valid.', 'AUTHENTICATION_FAILED', 'TOKEN_INVALID');
        }

        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            return ApiResponse::error(404, 'Endpoint atau data yang dicari tidak ditemukan.', 'NOT_FOUND', 'RESOURCE_NOT_FOUND');
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            return ApiResponse::error(405, 'Method HTTP tidak diizinkan untuk endpoint ini.', 'METHOD_NOT_ALLOWED', 'METHOD_NOT_ALLOWED');
        }

        if ($e instanceof ThrottleRequestsException) {
            return ApiResponse::error(429, 'Terlalu banyak permintaan. Batas 100 request per menit.', 'TOO_MANY_REQUESTS', 'RATE_LIMIT_EXCEEDED');
        }

        if ($e instanceof HttpExceptionInterface && $e->getStatusCode() < 500) {
            return ApiResponse::error($e->getStatusCode(), 'Permintaan tidak dapat diproses.', 'BAD_REQUEST', 'HTTP_ERROR');
        }

        return ApiResponse::error(500, 'Terjadi kesalahan pada server.', 'INTERNAL_SERVER_ERROR', 'INTERNAL_ERROR');
    }
}
