<?php

namespace App\Http\Middleware;

use App\Support\Api\ApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** 403 bila role pada token tidak termasuk role yang diizinkan (SEC-002). Pemakaian: role:LECTURER,KAPRODI */
class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $own = $request->attributes->get('auth')['roles'] ?? [];
        if (array_intersect($own, $roles) === []) {
            throw ApiException::forbidden('ROLE_NOT_ALLOWED', 'Role Anda tidak memiliki akses ke endpoint ini.');
        }

        return $next($request);
    }
}
