<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\T4\ApiUser;
use App\Support\Api\ApiException;
use App\Support\Api\ApiResponse;
use App\Support\Api\Input;
use App\Support\Api\Jwt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Stand-in POST /auth/login milik Tim 1, hanya agar T4 bisa diuji tanpa menunggu Auth Service.
 * Bentuk respons mengikuti SIA-API-INT v1.1 bagian 6.1. Buang saat Auth Service Tim 1 siap.
 */
class AuthController extends Controller
{
    private const PERMISSIONS = [
        'STUDENT' => ['attendance:checkin', 'attendance:read'],
        'LECTURER' => ['meeting:write', 'attendance:write', 'attendance:read'],
        'KAPRODI' => ['attendance:read'],
        'ADMIN_ACADEMIC' => ['attendance:read'],
    ];

    public function login(Request $request): JsonResponse
    {
        $data = $request->attributes->get('jsonBody', []);
        $username = Input::requireString($data, 'username');
        $password = Input::requireString($data, 'password', 200);

        $user = ApiUser::where('identifier', $username)->first();
        if (! $user || ! Hash::check($password, $user->password)) {
            throw new ApiException(401, 'AUTHENTICATION_FAILED', 'INVALID_CREDENTIALS', 'Username atau password salah.');
        }

        $ttl = (int) config('t4.jwt.ttl');
        $now = time();
        $token = Jwt::encode([
            'iss' => config('t4.jwt.issuer'),
            'sub' => $user->user_id,
            'identifier' => $user->identifier,
            'roles' => [$user->role],
            'permissions' => self::PERMISSIONS[$user->role] ?? [],
            'iat' => $now,
            'exp' => $now + $ttl,
            'jti' => 'jwt_'.Str::random(16),
        ]);

        return ApiResponse::ok('Authentication successful', [
            'tokenType' => 'Bearer',
            'accessToken' => $token,
            'expiresIn' => $ttl,
            'user' => [
                'userId' => $user->user_id,
                'identifier' => $user->identifier,
                'fullName' => $user->full_name,
                'roles' => [$user->role],
            ],
        ]);
    }
}
