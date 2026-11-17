<?php

namespace App\Services\Presensi;

use App\Models\T4\Lecturer;
use App\Models\T4\Student;
use App\Support\Api\ApiException;
use Illuminate\Http\Request;

/** Pelaku request, diambil dari token (bukan dari body). */
class Actor
{
    public function __construct(
        public readonly string $userId,
        public readonly string $identifier,
        public readonly array $roles,
    ) {}

    public static function from(Request $request): self
    {
        $auth = $request->attributes->get('auth', []);

        return new self($auth['userId'] ?? '', $auth['identifier'] ?? '', $auth['roles'] ?? []);
    }

    public function hasRole(string ...$roles): bool
    {
        return array_intersect($this->roles, $roles) !== [];
    }

    public function isProdi(): bool
    {
        return $this->hasRole('KAPRODI', 'ADMIN_ACADEMIC');
    }

    public function lecturer(): Lecturer
    {
        return Lecturer::where('nip', $this->identifier)->first()
            ?? throw ApiException::forbidden('LECTURER_PROFILE_NOT_FOUND', 'Profil dosen untuk akun ini tidak ditemukan.');
    }

    public function student(): Student
    {
        return Student::where('nim', $this->identifier)->first()
            ?? throw ApiException::forbidden('STUDENT_PROFILE_NOT_FOUND', 'Profil mahasiswa untuk akun ini tidak ditemukan.');
    }
}
