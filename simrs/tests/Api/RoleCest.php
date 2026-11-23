<?php

declare(strict_types=1);

namespace app\tests\Api;

use app\tests\Support\ApiTester;
use app\tests\Support\FixtureUser;

class RoleCest
{
    public function _before(ApiTester $I): void
    {
        FixtureUser::hapus($I, 'dokter_uji');
        $I->hapusBaris('DELETE FROM role WHERE role_code = ?', ['uji_role']);
        FixtureUser::pulihkanAdmin($I);
        $this->pulihkanRoleLain($I);
    }

    public function _after(ApiTester $I): void
    {
        FixtureUser::hapus($I, 'dokter_uji');
        $I->hapusBaris('DELETE FROM role WHERE role_code = ?', ['uji_role']);
        FixtureUser::pulihkanAdmin($I);
        $this->pulihkanRoleLain($I);
    }

    private function pulihkanRoleLain(ApiTester $I): void
    {
        foreach (['dokter', 'kasir'] as $kode) {
            FixtureUser::pulihkanAksesRole($I, $kode);
            $I->updateInDatabase('role', ['is_active' => 'true', 'role_name' => ucfirst($kode)], ['role_code' => $kode]);
        }
    }

    public function listRoleAdminBerhasil(ApiTester $I): void
    {
        $I->haveHttpHeader('Authorization', 'Bearer ' . FixtureUser::token($I, 'admin', 'admin123'));
        $I->sendGET('role');

        $I->seeResponseCodeIs(200);

        $kode = $I->grabDataFromResponseByJsonPath('$.data.items[*].code');
        $I->assertContains('admin', $kode);
        $I->assertContains('kasir', $kode);
        $I->assertContains('pendaftaran', $kode);
        $I->assertContains('dokter', $kode);
        $I->assertContains('farmasi', $kode);
        $I->assertContains('laboratorium', $kode);
    }

    public function matrixAdminSesuaiSeeder(ApiTester $I): void
    {
        $I->haveHttpHeader('Authorization', 'Bearer ' . FixtureUser::token($I, 'admin', 'admin123'));
        $I->sendGET('role');

        $I->seeResponseCodeIs(200);

        $idRoleAdmin = FixtureUser::idRole($I, 'admin');
        $idMenuUser = (string) $I->grabFromDatabase('menu', 'id', ['kode' => 'pengaturan.user']);

        $I->assertTrue(
            (bool) $I->grabDataFromResponseByJsonPath(
                sprintf("$.data.matrix.%s.%s.can_create", $idRoleAdmin, $idMenuUser),
            )[0],
        );
    }

    public function listRoleDitolakUntukKasir(ApiTester $I): void
    {
        FixtureUser::buat($I, FixtureUser::KASIR, FixtureUser::PASSWORD_KASIR, 'kasir');
        $token = FixtureUser::token($I, FixtureUser::KASIR, FixtureUser::PASSWORD_KASIR);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->sendGET('role');

        $I->seeResponseCodeIs(403);
        $I->seeResponseContainsJson(['message' => 'Anda tidak punya akses untuk aksi ini']);
    }

    public function ubahRole(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'admin', 'admin123');
        $idRole = FixtureUser::idRole($I, 'dokter');

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPUT('role/' . $idRole, ['role_name' => 'Dokter Jaga']);

        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['message' => 'Role diubah.']);
        $I->seeResponseContainsJson(['data' => ['name' => 'Dokter Jaga']]);
        $I->seeInDatabase('role', ['role_code' => 'dokter', 'role_name' => 'Dokter Jaga']);
    }

    public function buatRole(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'admin', 'admin123');

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('role', ['role_code' => 'uji_role', 'role_name' => 'Uji Role']);

        $I->seeResponseCodeIs(201);
        $I->seeResponseContainsJson(['message' => 'Role ditambahkan.']);
        $I->seeResponseContainsJson(['data' => ['code' => 'uji_role', 'name' => 'Uji Role', 'user_count' => 0]]);
        $I->seeInDatabase('role', ['role_code' => 'uji_role']);
    }

    public function buatRoleKodeDuplikat(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'admin', 'admin123');

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('role', ['role_code' => 'kasir', 'role_name' => 'Kasir Baru']);

        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['message' => 'Kode role sudah dipakai']);
    }

    public function nonaktifkanRole(ApiTester $I): void
    {
        FixtureUser::buat($I, 'dokter_uji', 'rahasia123', 'dokter');
        $token = FixtureUser::token($I, 'admin', 'admin123');
        $idRole = FixtureUser::idRole($I, 'dokter');

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPUT('role/' . $idRole . '/status', ['is_active' => false]);

        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['message' => 'Status role diubah.', 'data' => ['is_active' => false]]);

        FixtureUser::cobaToken($I, 'dokter_uji', 'rahasia123');
        $I->seeResponseCodeIs(403);
        $I->seeResponseContainsJson(['message' => 'Role tidak aktif']);

        $I->sendPUT('role/' . $idRole . '/status', ['is_active' => true]);
        $I->seeResponseCodeIs(200);

        FixtureUser::cobaToken($I, 'dokter_uji', 'rahasia123');
        $I->seeResponseCodeIs(200);
    }

    public function nonaktifkanRoleSendiriDitolak(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'admin', 'admin123');
        $idRole = FixtureUser::idRole($I, 'admin');

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPUT('role/' . $idRole . '/status', ['is_active' => false]);

        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['message' => 'Role yang sedang dipakai tidak bisa dinonaktifkan']);
    }

    public function ubahAccessRole(ApiTester $I): void
    {
        FixtureUser::buat($I, 'dokter_uji', 'rahasia123', 'dokter');
        $token = FixtureUser::token($I, 'admin', 'admin123');
        $idRole = FixtureUser::idRole($I, 'dokter');
        $idMenuUser = (string) $I->grabFromDatabase('menu', 'id', ['kode' => 'pengaturan.user']);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPUT('role/' . $idRole . '/access', [
            'access' => [
                $idMenuUser => ['can_view' => true, 'can_create' => true],
            ],
        ]);

        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['message' => 'Access role diubah.']);
        $I->assertSame(1, (int) $I->grabNumRecords('role_access', ['role_id' => $idRole]));
        $I->seeInDatabase('role_access', [
            'role_id' => $idRole,
            'menu_id' => $idMenuUser,
            'can_view' => 'true',
            'can_create' => 'true',
        ]);

        $tokenDokter = FixtureUser::token($I, 'dokter_uji', 'rahasia123');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $tokenDokter);
        $I->sendGET('menu');
        $I->seeResponseCodeIs(200);
        $I->assertSame(
            ['pengaturan'],
            $I->grabDataFromResponseByJsonPath('$.data[*].kode'),
        );

        $idDokter = FixtureUser::idUser($I, 'dokter_uji');
        $I->assertSame(1, (int) $I->grabNumRecords('user_access', ['user_id' => $idDokter, 'menu_id' => $idMenuUser]));
    }

    public function accessMenuTidakDitemukan(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'admin', 'admin123');

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPUT('role/' . FixtureUser::idRole($I, 'dokter') . '/access', [
            'access' => [
                '00000000-0000-0000-0000-000000000000' => ['can_view' => true],
            ],
        ]);

        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['message' => 'Menu tidak ditemukan']);
    }
}
