<?php

declare(strict_types=1);

namespace app\tests\Api;

use app\tests\Support\ApiTester;
use app\tests\Support\FixtureUser;
use Firebase\JWT\JWT;

class AuthCest
{
    private const PASSWORD_ADMIN = 'admin123';
    private const USERNAME_KASIR = 'kasir_uji';
    private const PASSWORD_KASIR = 'kasir12345';

    public function _before(ApiTester $I): void
    {
        $this->pulihkanAdmin($I);
        FixtureUser::pulihkanAksesRole($I, 'kasir');
        $this->hapusKasir($I);
        $this->buatKasir($I);
    }

    public function _after(ApiTester $I): void
    {
        $this->pulihkanAdmin($I);
        FixtureUser::pulihkanAksesRole($I, 'kasir');
        $this->hapusKasir($I);
    }

    public function loginAdminBerhasil(ApiTester $I): void
    {
        $token = $this->login($I, 'admin', self::PASSWORD_ADMIN);

        $I->assertNotEmpty($token);
        $I->seeResponseContainsJson([
            'status' => true,
            'data' => [
                'user' => [
                    'username' => 'admin',
                    'role' => ['code' => 'admin'],
                ],
            ],
        ]);

        $kodeMenu = $I->grabDataFromResponseByJsonPath('$..kode');
        $I->assertContains('dashboard', $kodeMenu);
        $I->assertContains('pengaturan.user', $kodeMenu);
        $I->assertContains('pengaturan.role', $kodeMenu);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->sendGET('auth/me');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'data' => ['user' => ['username' => 'admin', 'full_name' => 'Administrator']],
        ]);
    }

    public function passwordSalahDitolak(ApiTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('auth/login', ['username' => 'admin', 'password' => 'bukan-password']);

        $I->seeResponseCodeIs(401);
        $I->seeResponseContainsJson([
            'status' => false,
            'code' => 401,
            'message' => 'Username atau password salah',
        ]);
    }

    public function tanpaTokenDitolak(ApiTester $I): void
    {
        $I->sendGET('menu');

        $I->seeResponseCodeIs(401);
        $I->seeResponseContainsJson(['status' => false, 'message' => 'Token tidak ada']);
    }

    public function tokenKedaluwarsaDitolak(ApiTester $I): void
    {
        $userId = (string) $I->grabFromDatabase('user', 'id', ['username' => 'admin']);
        $sekarang = time();

        $token = JWT::encode([
            'sub' => $userId,
            'username' => 'admin',
            'role_code' => 'admin',
            'nama' => 'Administrator',
            'iat' => $sekarang - 7200,
            'exp' => $sekarang - 3600,
        ], (string) getenv('JWT_SECRET'), 'HS256');

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->sendGET('menu');

        $I->seeResponseCodeIs(401);
        $I->seeResponseContainsJson([
            'status' => false,
            'message' => 'Token tidak valid atau kedaluwarsa',
        ]);
    }

    public function userNonaktifDitolak(ApiTester $I): void
    {
        $token = $this->login($I, 'admin', self::PASSWORD_ADMIN);

        $I->updateInDatabase('user', ['is_active' => 'false'], ['username' => 'admin']);

        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('auth/login', ['username' => 'admin', 'password' => self::PASSWORD_ADMIN]);
        $I->seeResponseCodeIs(403);
        $I->seeResponseContainsJson(['message' => 'Akun tidak aktif']);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->sendGET('menu');
        $I->seeResponseCodeIs(403);
        $I->seeResponseContainsJson(['message' => 'Akun tidak aktif']);
    }

    public function roleNonaktifDitolak(ApiTester $I): void
    {
        $token = $this->login($I, 'admin', self::PASSWORD_ADMIN);

        $I->updateInDatabase('role', ['is_active' => 'false'], ['role_code' => 'admin']);

        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('auth/login', ['username' => 'admin', 'password' => self::PASSWORD_ADMIN]);
        $I->seeResponseCodeIs(403);
        $I->seeResponseContainsJson(['message' => 'Role tidak aktif']);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->sendGET('menu');
        $I->seeResponseCodeIs(403);
        $I->seeResponseContainsJson(['message' => 'Role tidak aktif']);
    }

    public function kasirHanyaMelihatMenuYangDiAssign(ApiTester $I): void
    {
        $token = $this->login($I, self::USERNAME_KASIR, self::PASSWORD_KASIR);

        $I->assertNotEmpty($token);
        $I->seeResponseContainsJson(['data' => ['user' => ['role' => ['code' => 'kasir']]]]);
        $I->assertSame(['dashboard'], $I->grabDataFromResponseByJsonPath('$.data.menus[*].kode'));

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->sendGET('menu');
        $I->seeResponseCodeIs(200);
        $I->assertSame(['dashboard'], $I->grabDataFromResponseByJsonPath('$.data[*].kode'));
    }

    public function cabutUserAccessLangsungBerlaku(ApiTester $I): void
    {
        $token = $this->login($I, 'admin', self::PASSWORD_ADMIN);
        $userId = (string) $I->grabFromDatabase('user', 'id', ['username' => 'admin']);
        $menuId = (string) $I->grabFromDatabase('menu', 'id', ['kode' => 'dashboard']);

        $I->hapusBaris(
            'DELETE FROM user_access WHERE user_id = ? AND menu_id = ?',
            [$userId, $menuId],
        );
        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->sendGET('menu');
        $I->seeResponseCodeIs(200);
        $I->assertSame(['master', 'pendaftaran', 'farmasi', 'pemeriksaan', 'laboratorium', 'kasir', 'laporan', 'pengaturan'], $I->grabDataFromResponseByJsonPath('$.data[*].kode'));

        $this->pulihkanAksesAdmin($I);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->sendGET('menu');
        $I->seeResponseCodeIs(200);
        $I->assertSame(
            ['dashboard', 'master', 'pendaftaran', 'farmasi', 'pemeriksaan', 'laboratorium', 'kasir', 'laporan', 'pengaturan'],
            $I->grabDataFromResponseByJsonPath('$.data[*].kode'),
        );
    }

    private function login(ApiTester $I, string $username, string $password): string
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('auth/login', ['username' => $username, 'password' => $password]);
        $I->seeResponseCodeIs(200);

        $token = $I->grabDataFromResponseByJsonPath('$.data.token');

        return (string) ($token[0] ?? '');
    }

    private function buatKasir(ApiTester $I): void
    {
        $roleId = (string) $I->grabFromDatabase('role', 'id', ['role_code' => 'kasir']);

        $I->haveInDatabase('user', [
            'username' => self::USERNAME_KASIR,
            'password_hash' => password_hash(self::PASSWORD_KASIR, PASSWORD_BCRYPT),
            'full_name' => 'Kasir Uji',
            'email' => 'kasir_uji@simrs.local',
            'role_id' => $roleId,
            'is_active' => 'true',
            'is_delete' => 'false',
        ]);

        $userId = (string) $I->grabFromDatabase('user', 'id', ['username' => self::USERNAME_KASIR]);
        $menuId = (string) $I->grabFromDatabase('menu', 'id', ['kode' => 'dashboard']);

        $I->haveInDatabase('user_access', ['user_id' => $userId, 'menu_id' => $menuId]);
    }

    private function hapusKasir(ApiTester $I): void
    {
        $I->hapusBaris('DELETE FROM "user" WHERE username = ?', [self::USERNAME_KASIR]);
    }

    private function pulihkanAdmin(ApiTester $I): void
    {
        $I->updateInDatabase('role', ['is_active' => 'true'], ['role_code' => 'admin']);
        $I->updateInDatabase(
            'user',
            ['is_active' => 'true', 'is_delete' => 'false'],
            ['username' => 'admin'],
        );
        $this->pulihkanAksesAdmin($I);
    }

    private function pulihkanAksesAdmin(ApiTester $I): void
    {
        $roleId = (string) $I->grabFromDatabase('role', 'id', ['role_code' => 'admin']);
        $userId = (string) $I->grabFromDatabase('user', 'id', ['username' => 'admin']);

        $menuIds = $I->grabColumnFromDatabase(
            'role_access',
            'menu_id',
            ['role_id' => $roleId, 'can_view' => 'true'],
        );

        foreach ($menuIds as $menuId) {
            $jumlah = $I->grabNumRecords(
                'user_access',
                ['user_id' => $userId, 'menu_id' => $menuId],
            );

            if ($jumlah === 0) {
                $I->haveInDatabase('user_access', ['user_id' => $userId, 'menu_id' => $menuId]);
            }
        }
    }
}
