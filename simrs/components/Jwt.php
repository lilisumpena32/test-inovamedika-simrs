<?php

declare(strict_types=1);

namespace app\components;

use app\models\User;
use Firebase\JWT\JWT as FirebaseJwt;
use Firebase\JWT\Key;
use RuntimeException;

final class Jwt
{
    private const MASA_BERLAKU = 8 * 3600;

    public static function terbitkan(User $user): string
    {
        $sekarang = time();

        return FirebaseJwt::encode([
            'sub' => $user->id,
            'username' => $user->username,
            'role_code' => $user->role->role_code,
            'nama' => $user->full_name,
            'iat' => $sekarang,
            'exp' => $sekarang + self::MASA_BERLAKU,
        ], self::rahasia(), 'HS256');
    }

    public static function baca(string $token): ?array
    {
        try {
            $klaim = FirebaseJwt::decode($token, new Key(self::rahasia(), 'HS256'));
        } catch (\Throwable) {
            return null;
        }

        return (array) $klaim;
    }

    private static function rahasia(): string
    {
        $rahasia = getenv('JWT_SECRET');

        if ($rahasia === false || $rahasia === '') {
            throw new RuntimeException('JWT_SECRET belum diisi');
        }

        return $rahasia;
    }
}
