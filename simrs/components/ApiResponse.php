<?php

declare(strict_types=1);

namespace app\components;

final class ApiResponse
{
    public static function ok(mixed $data, int $kode = 200, string $pesan = ''): array
    {
        return [
            'status' => true,
            'code' => $kode,
            'message' => $pesan,
            'data' => $data,
        ];
    }

    public static function error(string $pesan, int $kode): array
    {
        return [
            'status' => false,
            'code' => $kode,
            'message' => $pesan,
        ];
    }
}
