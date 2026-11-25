<?php

declare(strict_types=1);

namespace app\tests\Api;

use app\tests\Support\ApiTester;
use app\tests\Support\FixtureUser;

class TagihanCest
{
    private string $tokenAdmin = '';
    private string $tokenBudi = '';

    public function _before(ApiTester $I): void
    {
        $this->bersihkan($I);
        $this->tokenAdmin = FixtureUser::token($I, 'admin', 'admin123');
        $this->tokenBudi = FixtureUser::token($I, 'budi', 'password123');
    }

    public function _after(ApiTester $I): void
    {
        $this->bersihkan($I);
    }

    private function bersihkan(ApiTester $I): void
    {
        $I->hapusBaris('DELETE FROM pembayaran WHERE tagihan_id IN (SELECT t.id FROM tagihan t JOIN kunjungan k ON k.id = t.kunjungan_id WHERE k.no_kunjungan LIKE ?)', ['KJ-%']);
        $I->hapusBaris('DELETE FROM mutasi_obat WHERE referensi IN (SELECT r.id FROM resep r JOIN kunjungan k ON k.id = r.kunjungan_id WHERE k.no_kunjungan LIKE ?)', ['KJ-%']);
        $I->hapusBaris('DELETE FROM tagihan_item WHERE tagihan_id IN (SELECT t.id FROM tagihan t JOIN kunjungan k ON k.id = t.kunjungan_id WHERE k.no_kunjungan LIKE ?)', ['KJ-%']);
        $I->hapusBaris('DELETE FROM tagihan WHERE kunjungan_id IN (SELECT id FROM kunjungan WHERE no_kunjungan LIKE ?)', ['KJ-%']);
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

    public function buatTagihanDanBayarLunasBertahap(ApiTester $I): void
    {
        $kunjungan = $this->kunjunganMenungguBayar($I);
        
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->tokenBudi);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('tagihan', ['kunjungan_id' => $kunjungan['id']]);

        $I->seeResponseCodeIs(201);
        $I->seeResponseContainsJson(['message' => 'Tagihan dibuat.', 'data' => ['status' => 'belum', 'total' => 43500]]);
        $idTagihan = (string) $I->grabDataFromResponseByJsonPath('$.data.id')[0];

        // Jika tagihan sudah ada, mengembalikan data bukan buat baru.
        $I->sendPOST('tagihan', ['kunjungan_id' => $kunjungan['id']]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['message' => 'Tagihan sudah ada untuk kunjungan ini.']);

        // Bayar sebagian.
        $I->sendPUT('tagihan/' . $idTagihan . '/bayar', [
            'idempotency_key' => 'bayar-1-' . $kunjungan['id'],
            'metode' => 'tunai',
            'nominal' => 20000,
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['message' => 'Pembayaran dicatat.', 'data' => ['status' => 'sebagian', 'dibayar' => 20000, 'sisa' => 23500]]);

        // Bayar hingga lunas.
        $I->sendPUT('tagihan/' . $idTagihan . '/bayar', [
            'idempotency_key' => 'bayar-2-' . $kunjungan['id'],
            'metode' => 'non_tunai',
            'nominal' => 23500,
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['data' => ['status' => 'lunas', 'sisa' => 0]]);

        // Kunjungan otomatis selesai saat lunas.
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->tokenAdmin);
        $I->sendGET('kunjungan/' . $kunjungan['id']);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['data' => ['status' => 'selesai']]);

        // Riwayat pembayaran tampil pada detail (dua pembayaran).
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->tokenBudi);
        $I->sendGET('tagihan/' . $idTagihan);
        $I->seeResponseCodeIs(200);
        $I->assertSame(2, count($I->grabDataFromResponseByJsonPath('$.data.pembayaran[*].id')));
    }

    public function pembayaranGandaSatuBaris(ApiTester $I): void
    {
        $tagihanId = $this->tagihanId($I);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->tokenBudi);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPUT('tagihan/' . $tagihanId . '/bayar', [
            'idempotency_key' => 'bayar-ganda-1',
            'metode' => 'tunai',
            'nominal' => 10000,
        ]);
        $I->seeResponseCodeIs(200);

        $I->sendPUT('tagihan/' . $tagihanId . '/bayar', [
            'idempotency_key' => 'bayar-ganda-1',
            'metode' => 'tunai',
            'nominal' => 10000,
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['message' => 'Pembayaran sudah tercatat sebelumnya.']);

        $I->assertSame('sebagian', (string) $I->grabFromDatabase('tagihan', 'status', ['id' => $tagihanId]));
        $I->assertSame(1, (int) $I->grabNumRecords('pembayaran', ['tagihan_id' => $tagihanId]));
    }

    public function nominalMelebihiSisaDitolak(ApiTester $I): void
    {
        $tagihanId = $this->tagihanId($I);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->tokenBudi);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPUT('tagihan/' . $tagihanId . '/bayar', [
            'idempotency_key' => 'bayar-lebih',
            'metode' => 'tunai',
            'nominal' => 999999,
        ]);

        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['message' => 'Nominal pembayaran melebihi sisa tagihan 43.500,00']);
    }

    public function dokterDitolak(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'andi', 'password123');

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->sendGET('tagihan');

        $I->seeResponseCodeIs(403);
        $I->seeResponseContainsJson(['message' => 'Anda tidak punya akses untuk aksi ini']);
    }

    public function tagihanUntukKunjunganBelumSelesaiDitolak(ApiTester $I): void
    {
        $kunjungan = $this->kunjunganMenungguBayar($I);

        // Kembalikan status ke diperiksa untuk menguji tolakannya.
        $I->eksekusi('UPDATE kunjungan SET status = ? WHERE id = ?', ['diperiksa', $kunjungan['id']]);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->tokenBudi);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('tagihan', ['kunjungan_id' => $kunjungan['id']]);

        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['message' => 'Tagihan hanya dibuat untuk kunjungan berstatus menunggu_bayar']);
    }

    private function tagihanId(ApiTester $I): string
    {
        $kunjungan = $this->kunjunganMenungguBayar($I);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->tokenBudi);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('tagihan', ['kunjungan_id' => $kunjungan['id']]);
        $I->seeResponseCodeIs(201);

        return (string) $I->grabDataFromResponseByJsonPath('$.data.id')[0];
    }

    private function kunjunganMenungguBayar(ApiTester $I): array
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

        $I->sendPOST('pasien', ['nama' => 'Uji Pasien Kasir', 'jenis_kelamin' => 'L']);
        $idPasien = (string) $I->grabDataFromResponseByJsonPath('$.data.id')[0];

        $I->sendPOST('kunjungan', [
            'idempotency_key' => 'kunjungan-kasir-' . uniqid(),
            'pasien_id' => $idPasien,
            'jadwal_id' => $idJadwal,
            'tgl_kunjungan' => $tanggal,
        ]);
        $I->seeResponseCodeIs(201);
        $idKunjungan = (string) $I->grabDataFromResponseByJsonPath('$.data.id')[0];

        // Dokter memeriksa lalu membuat resep; resep diserahkan farmasi
        // sehingga status kunjungan mencapai menunggu_bayar.
        $tokenDokter = FixtureUser::token($I, 'andi', 'password123');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $tokenDokter);
        $I->sendPOST('kunjungan/' . $idKunjungan . '/pemeriksaan', ['diagnosis_utama' => 'Flu']);
        $I->seeResponseCodeIs(200);

        $idObat = (string) $I->grabFromDatabase('obat', 'id', ['nama_obat' => 'Paracetamol 500 mg']);
        $I->sendPOST('kunjungan/' . $idKunjungan . '/resep', [
            'items' => [['obat_id' => $idObat, 'dosis' => '1', 'frekuensi' => '3x', 'jumlah' => 9]],
        ]);
        $I->seeResponseCodeIs(201);
        $idResep = (string) $I->grabDataFromResponseByJsonPath('$.data.id')[0];

        $tokenFarmasi = FixtureUser::token($I, 'maya', 'password123');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $tokenFarmasi);
        $I->sendPUT('resep/' . $idResep . '/serah');
        $I->seeResponseCodeIs(200);

        return ['id' => $idKunjungan, 'tanggal' => $tanggal];
    }
}
