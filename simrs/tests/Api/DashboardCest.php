<?php

declare(strict_types=1);

namespace app\tests\Api;

use app\tests\Support\ApiTester;
use app\tests\Support\FixtureUser;

class DashboardCest
{
    private string $dari = '';
    private string $sampai = '';

    public function _before(ApiTester $I): void
    {
        FixtureUser::pulihkanAdmin($I);

        // Isi data performa bila kosong (fixture suite menghapus kunjungan
        // berprefix KJ-, data performa memakai prefix PF- dan tetap tersisa).
        $total = (int) $I->grabNumRecords('kunjungan');

        if ($total === 0) {
            exec('php /var/www/simrs/yii seed/performa', $output, $kode);

            if ($kode !== 0) {
                throw new \RuntimeException('seed/performa gagal');
            }
        }

        // Rentang lebar agar mencakup 350 hari data performa.
        $this->dari = date('Y-m-d', strtotime('-400 days'));
        $this->sampai = date('Y-m-d');
    }

    public function ringkasanOperasional(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'admin', 'admin123');

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->sendGET('dashboard/ringkasan', [
            'dari' => $this->dari,
            'sampai' => $this->sampai,
        ]);

        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['data' => ['periode' => ['dari' => $this->dari]]]);
        $I->assertGreaterThan(0, (int) $I->grabDataFromResponseByJsonPath('$.data.kunjungan_total')[0]);
        $I->assertNotEmpty($I->grabDataFromResponseByJsonPath('$.data.kunjungan_per_poli[*].poli'));
        $I->assertGreaterThanOrEqual(0, (int) $I->grabDataFromResponseByJsonPath('$.data.tindakan_total')[0]);
    }

    public function pendapatanPeriode(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'admin', 'admin123');

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->sendGET('dashboard/pendapatan', [
            'dari' => $this->dari,
            'sampai' => $this->sampai,
        ]);

        $I->seeResponseCodeIs(200);
        $I->assertGreaterThanOrEqual(0, (float) $I->grabDataFromResponseByJsonPath('$.data.total')[0]);
        $I->assertIsArray($I->grabDataFromResponseByJsonPath('$.data.per_hari'));
        $I->assertIsArray($I->grabDataFromResponseByJsonPath('$.data.per_metode'));
    }

    public function obatTerpakai(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'admin', 'admin123');

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->sendGET('dashboard/obat', [
            'dari' => $this->dari,
            'sampai' => $this->sampai,
        ]);

        $I->seeResponseCodeIs(200);
        $I->assertIsArray($I->grabDataFromResponseByJsonPath('$.data.items[*].nama_obat'));
    }

    public function periodeSalahDitolak(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'admin', 'admin123');

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->sendGET('dashboard/ringkasan', ['dari' => 'salah', 'sampai' => 'salah juga']);

        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['message' => 'Parameter dari dan sampai wajib format YYYY-MM-DD']);
    }
}
