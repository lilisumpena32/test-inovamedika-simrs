<?php

declare(strict_types=1);

namespace app\tests\Api;

use app\tests\Support\ApiTester;
use app\tests\Support\FixtureUser;

class ResepCest
{
    private string $tokenAdmin = '';
    private string $tokenDokter = '';
    private string $tokenFarmasi = '';

    public function _before(ApiTester $I): void
    {
        $this->bersihkan($I);
        $this->tokenAdmin = FixtureUser::token($I, 'admin', 'admin123');
        $this->tokenDokter = FixtureUser::token($I, 'andi', 'password123');
        $this->tokenFarmasi = FixtureUser::token($I, 'maya', 'password123');
    }

    public function _after(ApiTester $I): void
    {
        $this->bersihkan($I);
    }

    private function bersihkan(ApiTester $I): void
    {
        $I->hapusBaris('DELETE FROM mutasi_obat WHERE referensi IN (SELECT id FROM resep WHERE kunjungan_id IN (SELECT id FROM kunjungan WHERE no_kunjungan LIKE ?))', ['KJ-%']);
        $I->hapusBaris('DELETE FROM resep_obat WHERE resep_id IN (SELECT id FROM resep WHERE kunjungan_id IN (SELECT id FROM kunjungan WHERE no_kunjungan LIKE ?))', ['KJ-%']);
        $I->hapusBaris('DELETE FROM resep WHERE kunjungan_id IN (SELECT id FROM kunjungan WHERE no_kunjungan LIKE ?)', ['KJ-%']);
        $I->hapusBaris('DELETE FROM order_lab WHERE kunjungan_id IN (SELECT id FROM kunjungan WHERE no_kunjungan LIKE ?)', ['KJ-%']);
        $I->hapusBaris('DELETE FROM pemeriksaan_tindakan WHERE kunjungan_id IN (SELECT id FROM kunjungan WHERE no_kunjungan LIKE ?)', ['KJ-%']);
        $I->hapusBaris('DELETE FROM pemeriksaan WHERE kunjungan_id IN (SELECT id FROM kunjungan WHERE no_kunjungan LIKE ?)', ['KJ-%']);
        $I->hapusBaris('DELETE FROM kunjungan WHERE no_kunjungan LIKE ?', ['KJ-%']);
        $I->hapusBaris('DELETE FROM counter_antrean');
        $I->hapusBaris('UPDATE stok_obat SET jumlah = 30 WHERE no_batch = ?', ['B-001']);
        $I->hapusBaris('UPDATE stok_obat SET jumlah = 120 WHERE no_batch = ?', ['B-002']);
        FixtureUser::pulihkanAdmin($I);
    }

    public function buatResepUbahStatusKunjungan(ApiTester $I): void
    {
        $kunjungan = $this->kunjunganDiperiksa($I);
        $obat = $this->obatUji($I);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->tokenDokter);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('kunjungan/' . $kunjungan['id'] . '/resep', [
            'items' => [
                ['obat_id' => $obat, 'dosis' => '1 tablet', 'frekuensi' => '3x sehari', 'jumlah' => 9],
            ],
        ]);

        $I->seeResponseCodeIs(201);
        $I->seeResponseContainsJson(['message' => 'Resep dibuat.', 'data' => ['status' => 'menunggu']]);
        $idResep = (string) $I->grabDataFromResponseByJsonPath('$.data.id')[0];

        $I->sendGET('kunjungan/' . $kunjungan['id']);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['data' => ['status' => 'menunggu_obat']]);

        $I->sendPOST('kunjungan/' . $kunjungan['id'] . '/resep', [
            'items' => [['obat_id' => $obat, 'jumlah' => 1]],
        ]);
        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['message' => 'Resep sudah dibuat untuk kunjungan ini']);
    }

    public function stokTidakCukupDitolak(ApiTester $I): void
    {
        $kunjungan = $this->kunjunganDiperiksa($I);
        $obat = $this->obatUji($I);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->tokenDokter);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('kunjungan/' . $kunjungan['id'] . '/resep', [
            'items' => [['obat_id' => $obat, 'jumlah' => 9999]],
        ]);

        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['message' => 'Stok obat Paracetamol 500 mg tidak cukup']);
    }

    public function serahFefoDanTanpaMinus(ApiTester $I): void
    {
        $kunjungan = $this->kunjunganDiperiksa($I);
        $obat = $this->obatUji($I);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->tokenDokter);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('kunjungan/' . $kunjungan['id'] . '/resep', [
            'items' => [['obat_id' => $obat, 'dosis' => '1x', 'frekuensi' => '1x sehari', 'jumlah' => 45]],
        ]);
        $idResep = (string) $I->grabDataFromResponseByJsonPath('$.data.id')[0];

        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->tokenFarmasi);
        $I->sendPUT('resep/' . $idResep . '/serah');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['message' => 'Obat diserahkan.', 'data' => ['status' => 'selesai']]);

        $I->sendPUT('resep/' . $idResep . '/serah');
        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['message' => 'Resep sudah diproses sebelumnya']);

        // FEFO: batch terdekat kadaluarsanya (B-001, 30) habis dulu, sisanya B-002.
        $I->assertSame(0, (int) $I->grabFromDatabase('stok_obat', 'jumlah', ['obat_id' => $obat, 'no_batch' => 'B-001']));
        $I->assertSame(105, (int) $I->grabFromDatabase('stok_obat', 'jumlah', ['obat_id' => $obat, 'no_batch' => 'B-002']));

        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->tokenAdmin);
        $I->sendGET('kunjungan/' . $kunjungan['id']);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['data' => ['status' => 'menunggu_bayar']]);
    }

    public function ubahItemSebelumSerah(ApiTester $I): void    {
        $kunjungan = $this->kunjunganDiperiksa($I);
        $obat = $this->obatUji($I);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->tokenDokter);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('kunjungan/' . $kunjungan['id'] . '/resep', [
            'items' => [['obat_id' => $obat, 'jumlah' => 2]],
        ]);
        $I->seeResponseCodeIs(201);
        $idResep = (string) $I->grabDataFromResponseByJsonPath('$.data.id')[0];

        $I->sendPUT('kunjungan/' . $kunjungan['id'] . '/resep', [
            'items' => [['obat_id' => $obat, 'dosis' => '1', 'frekuensi' => '2x', 'jumlah' => 4]],
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['data' => ['items' => [['jumlah' => 4]]]]);
        $idItemBaru = (string) $I->grabDataFromResponseByJsonPath('$.data.items[0].id')[0];
        $I->assertSame('4', (string) $I->grabFromDatabase('resep_obat', 'jumlah', ['id' => $idItemBaru]));
    }

    private function kunjunganDiperiksa(ApiTester $I): array
    {
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->tokenAdmin);
        $I->haveHttpHeader('Content-Type', 'application/json');

        $idKaryawan = (string) $I->grabFromDatabase('karyawan', 'id', ['nama_karyawan' => 'dr. Andi Pratama']);
        $idJadwal = (string) $I->grabFromDatabase('jadwal_praktek', 'id', ['karyawan_id' => $idKaryawan, 'hari' => 1]);

        $tanggal = '';
        for ($i = 1; $i <= 7; $i++) {
            $t = date('Y-m-d', strtotime("+{$i} days"));

            if (date('N', strtotime($t)) === '1') {
                $tanggal = $t;
                break;
            }
        }

        $I->sendPOST('pasien', ['nama' => 'Uji Pasien Resep', 'jenis_kelamin' => 'L']);
        $idPasien = (string) $I->grabDataFromResponseByJsonPath('$.data.id')[0];

        $I->sendPOST('kunjungan', [
            'idempotency_key' => 'kunjungan-resep-' . uniqid(),
            'pasien_id' => $idPasien,
            'jadwal_id' => $idJadwal,
            'tgl_kunjungan' => $tanggal,
        ]);
        $I->seeResponseCodeIs(201);
        $idKunjungan = (string) $I->grabDataFromResponseByJsonPath('$.data.id')[0];

        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->tokenDokter);
        $I->sendPOST('kunjungan/' . $idKunjungan . '/pemeriksaan', ['diagnosis_utama' => 'Flu']);
        $I->seeResponseCodeIs(200);

        return ['id' => $idKunjungan, 'tanggal' => $tanggal];
    }

    private function obatUji(ApiTester $I): string
    {
        return (string) $I->grabFromDatabase('obat', 'id', ['nama_obat' => 'Paracetamol 500 mg']);
    }
}
