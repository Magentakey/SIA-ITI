<?php

namespace App\Http\Middleware;

use App\Support\Api\ApiException;
use App\Support\Api\Jwt;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** 401 bila token tidak ada, kedaluwarsa, atau tidak valid (SEC-001). Identitas disimpan di atribut request. */
class JwtAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = (string) $request->header('Authorization', '');
        if (! preg_match('/^Bearer\s+(\S+)$/i', $header, $m)) {
            throw ApiException::unauthorized('TOKEN_MISSING', 'Sesi telah berakhir atau token tidak valid.');
        }

        $claims = Jwt::decode($m[1]);
        $roles = is_array($claims['roles'] ?? null) ? $claims['roles'] : [];
        $request->attributes->set('auth', [
            'userId' => (string) ($claims['sub'] ?? ''),
            'identifier' => (string) ($claims['identifier'] ?? ''),
            'roles' => $roles,
        ]);

        return $next($request);
    }
}
