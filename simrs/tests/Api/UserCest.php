<?php

declare(strict_types=1);

namespace app\tests\Api;

use app\tests\Support\ApiTester;
use app\tests\Support\FixtureUser;

class UserCest
{
    private const USERNAME_BARU = 'uji_baru';
    private const PASSWORD_BARU = 'rahasia123';

    public function _before(ApiTester $I): void
    {
        $this->bersihkan($I);
    }

    public function _after(ApiTester $I): void
    {
        $this->bersihkan($I);
    }

    private function bersihkan(ApiTester $I): void
    {
        FixtureUser::hapus($I, self::USERNAME_BARU);
        FixtureUser::pulihkanAdmin($I);
    }

    public function listUserAdminBerhasil(ApiTester $I): void
    {
        $I->haveHttpHeader('Authorization', 'Bearer ' . FixtureUser::token($I, 'admin', 'admin123'));
        $I->sendGET('user');

        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'data' => [
                'items' => [
                    ['username' => 'admin', 'full_name' => 'Administrator'],
                ],
            ],
        ]);
    }

    public function listUserDitolakUntukKasir(ApiTester $I): void
    {
        FixtureUser::buat($I, FixtureUser::KASIR, FixtureUser::PASSWORD_KASIR, 'kasir');
        $token = FixtureUser::token($I, FixtureUser::KASIR, FixtureUser::PASSWORD_KASIR);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->sendGET('user');

        $I->seeResponseCodeIs(403);
        $I->seeResponseContainsJson(['message' => 'Anda tidak punya akses untuk aksi ini']);
    }

    public function buatUserBerhasil(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'admin', 'admin123');

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('user', [
            'username' => self::USERNAME_BARU,
            'full_name' => 'Uji Baru',
            'email' => 'uji_baru@simrs.local',
            'role_id' => FixtureUser::idRole($I, 'kasir'),
            'password' => self::PASSWORD_BARU,
        ]);

        $I->seeResponseCodeIs(201);
        $I->seeResponseContainsJson([
            'data' => ['username' => self::USERNAME_BARU, 'role' => ['code' => 'kasir']],
        ]);

        $tokenBaru = FixtureUser::token($I, self::USERNAME_BARU, self::PASSWORD_BARU);
        $I->assertNotEmpty($tokenBaru);
    }

    public function buatUserUsernameDuplikat(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'admin', 'admin123');
        $body = [
            'username' => 'admin',
            'full_name' => 'Uji Duplikat',
            'role_id' => FixtureUser::idRole($I, 'kasir'),
            'password' => self::PASSWORD_BARU,
        ];

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('user', $body);

        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['message' => 'Username sudah dipakai']);
    }

    public function buatUserPasswordPendek(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'admin', 'admin123');

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('user', [
            'username' => self::USERNAME_BARU,
            'full_name' => 'Uji Baru',
            'role_id' => FixtureUser::idRole($I, 'kasir'),
            'password' => 'pendek',
        ]);

        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['message' => 'Password minimal 8 karakter']);
    }

    public function ubahUserDanGantiRole(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'admin', 'admin123');
        $idRoleKasir = FixtureUser::idRole($I, 'kasir');
        $idRoleAdmin = FixtureUser::idRole($I, 'admin');

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('user', [
            'username' => self::USERNAME_BARU,
            'full_name' => 'Uji Baru',
            'role_id' => $idRoleKasir,
            'password' => self::PASSWORD_BARU,
        ]);
        $id = (string) $I->grabDataFromResponseByJsonPath('$.data.id')[0];

        $I->sendPUT('user/' . $id, [
            'full_name' => 'Uji Baru Jadi Admin',
            'email' => 'uji_baru@simrs.local',
            'role_id' => $idRoleAdmin,
        ]);

        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'data' => ['full_name' => 'Uji Baru Jadi Admin', 'role' => ['code' => 'admin']],
        ]);
        $I->assertSame(3, (int) $I->grabNumRecords('user_access', ['user_id' => $id]));
    }

    public function nonaktifkanUser(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'admin', 'admin123');

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('user', [
            'username' => self::USERNAME_BARU,
            'full_name' => 'Uji Baru',
            'role_id' => FixtureUser::idRole($I, 'kasir'),
            'password' => self::PASSWORD_BARU,
        ]);
        $id = (string) $I->grabDataFromResponseByJsonPath('$.data.id')[0];

        $I->sendPUT('user/' . $id . '/status', ['is_active' => false]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['data' => ['is_active' => false]]);

        FixtureUser::cobaToken($I, self::USERNAME_BARU, self::PASSWORD_BARU);
        $I->seeResponseCodeIs(403);
        $I->seeResponseContainsJson(['message' => 'Akun tidak aktif']);

        $I->sendPUT('user/' . $id . '/status', ['is_active' => true]);
        $I->seeResponseCodeIs(200);

        FixtureUser::cobaToken($I, self::USERNAME_BARU, self::PASSWORD_BARU);
        $I->seeResponseCodeIs(200);
    }

    public function hapusUser(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'admin', 'admin123');

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('user', [
            'username' => self::USERNAME_BARU,
            'full_name' => 'Uji Baru',
            'role_id' => FixtureUser::idRole($I, 'kasir'),
            'password' => self::PASSWORD_BARU,
        ]);
        $id = (string) $I->grabDataFromResponseByJsonPath('$.data.id')[0];

        $I->sendDELETE('user/' . $id);
        $I->seeResponseCodeIs(200);
        $I->assertSame(0, (int) $I->grabNumRecords('user', ['username' => self::USERNAME_BARU, 'is_delete' => 'false']));

        FixtureUser::cobaToken($I, self::USERNAME_BARU, self::PASSWORD_BARU);
        $I->seeResponseCodeIs(401);
    }

    public function hapusDiriSendiriDitolak(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'admin', 'admin123');
        $id = FixtureUser::idUser($I, 'admin');

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->sendDELETE('user/' . $id);

        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['message' => 'Akun yang sedang login tidak bisa dihapus']);
    }

    public function nonaktifkanDiriSendiriDitolak(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'admin', 'admin123');
        $id = FixtureUser::idUser($I, 'admin');

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPUT('user/' . $id . '/status', ['is_active' => false]);

        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['message' => 'Akun yang sedang login tidak bisa dinonaktifkan']);
    }

    public function gantiPassword(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'admin', 'admin123');

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('user', [
            'username' => self::USERNAME_BARU,
            'full_name' => 'Uji Baru',
            'role_id' => FixtureUser::idRole($I, 'kasir'),
            'password' => self::PASSWORD_BARU,
        ]);
        $id = (string) $I->grabDataFromResponseByJsonPath('$.data.id')[0];

        $I->sendPUT('user/' . $id . '/password', ['password' => 'passwordBaru99']);
        $I->seeResponseCodeIs(200);

        FixtureUser::cobaToken($I, self::USERNAME_BARU, self::PASSWORD_BARU);
        $I->seeResponseCodeIs(401);

        FixtureUser::cobaToken($I, self::USERNAME_BARU, 'passwordBaru99');
        $I->seeResponseCodeIs(200);
    }

    public function userTidakDitemukan(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'admin', 'admin123');

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->sendGET('user/00000000-0000-0000-0000-000000000000');

        $I->seeResponseCodeIs(404);
        $I->seeResponseContainsJson(['message' => 'User tidak ditemukan']);
    }
}
