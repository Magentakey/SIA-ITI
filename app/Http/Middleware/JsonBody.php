<?php

namespace App\Http\Middleware;

use App\Support\Api\ApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** 400 bila body JSON rusak atau bukan object JSON. Body kosong dianggap {}. */
class JsonBody
{
    public function handle(Request $request, Closure $next): Response
    {
        $raw = trim((string) $request->getContent());
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded) || (array_is_list($decoded) && $decoded !== [])) {
                throw ApiException::badRequest('INVALID_JSON', 'Body harus berupa JSON object yang valid.');
            }
            $request->attributes->set('jsonBody', $decoded);
        } else {
            $request->attributes->set('jsonBody', []);
        }

        return $next($request);
    }
}
