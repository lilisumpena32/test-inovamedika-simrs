<?php

declare(strict_types=1);

namespace app\tests\Support;

final class FixtureUser
{
    public const KASIR = 'kasir_uji';
    public const PASSWORD_KASIR = 'kasir12345';

    public static function token(ApiTester $I, string $username, string $password): string
    {
        $token = self::cobaToken($I, $username, $password);
        $I->seeResponseCodeIs(200);

        return $token;
    }

    public static function cobaToken(ApiTester $I, string $username, string $password): string
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('auth/login', ['username' => $username, 'password' => $password]);

        return (string) ($I->grabDataFromResponseByJsonPath('$.data.token')[0] ?? '');
    }

    public static function idRole(ApiTester $I, string $kode): string
    {
        return (string) $I->grabFromDatabase('role', 'id', ['role_code' => $kode]);
    }

    public static function idUser(ApiTester $I, string $username): string
    {
        return (string) $I->grabFromDatabase('user', 'id', ['username' => $username]);
    }

    public static function buat(ApiTester $I, string $username, string $password, string $kodeRole): string
    {
        self::hapus($I, $username);

        $I->haveInDatabase('user', [
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'full_name' => 'Petugas Uji',
            'email' => $username . '@simrs.local',
            'role_id' => self::idRole($I, $kodeRole),
            'is_active' => 'true',
            'is_delete' => 'false',
        ]);

        return self::idUser($I, $username);
    }

    public static function hapus(ApiTester $I, string $username): void
    {
        $I->hapusBaris('DELETE FROM "user" WHERE username = ?', [$username]);
    }

    public static function pulihkanAksesRole(ApiTester $I, string $kodeRole): void
    {
        // Pulihan memakai rbac/init supaya matriks role selalu kembali ke
        // sumber kebenaran di RbacController, bukan salinan yang bisa drift.
        exec('cd /var/www/simrs && php yii rbac/init', $output, $kode);

        if ($kode !== 0) {
            throw new \RuntimeException('rbac/init gagal: ' . implode("\n", $output));
        }
    }

    public static function pulihkanAdmin(ApiTester $I): void
    {
        $I->updateInDatabase('role', ['is_active' => 'true'], ['role_code' => 'admin']);
        $I->updateInDatabase(
            'user',
            ['is_active' => 'true', 'is_delete' => 'false'],
            ['username' => 'admin'],
        );
        self::hapus($I, self::KASIR);

        $roleId = self::idRole($I, 'admin');
        $userId = self::idUser($I, 'admin');

        foreach ($I->grabColumnFromDatabase('role_access', 'menu_id', ['role_id' => $roleId, 'can_view' => 'true']) as $menuId) {
            if ((int) $I->grabNumRecords('user_access', ['user_id' => $userId, 'menu_id' => $menuId]) === 0) {
                $I->eksekusi(
                    'INSERT INTO user_access (user_id, menu_id) VALUES (?, ?)',
                    [$userId, $menuId],
                );
            }
        }
    }
}
