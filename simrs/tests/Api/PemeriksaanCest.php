<?php

declare(strict_types=1);

namespace app\tests\Api;

use app\tests\Support\ApiTester;
use app\tests\Support\FixtureUser;

class PemeriksaanCest
{
    private string $tokenAdmin = '';
    private string $tokenDokter = '';
    private string $tokenLab = '';

    public function _before(ApiTester $I): void
    {
        $this->bersihkan($I);
        $this->tokenAdmin = FixtureUser::token($I, 'admin', 'admin123');
        $this->tokenDokter = FixtureUser::token($I, 'andi', 'password123');
        $this->tokenLab = FixtureUser::token($I, 'lina', 'password123');
    }

    public function _after(ApiTester $I): void
    {
        $this->bersihkan($I);
    }

    private function bersihkan(ApiTester $I): void
    {
        $I->hapusBaris('DELETE FROM outbox_lab WHERE order_lab_id IN (SELECT id FROM order_lab WHERE kunjungan_id IN (SELECT id FROM kunjungan WHERE no_kunjungan LIKE ?))', ['KJ-%']);
        $I->hapusBaris('DELETE FROM order_lab WHERE kunjungan_id IN (SELECT id FROM kunjungan WHERE no_kunjungan LIKE ?)', ['KJ-%']);
        $I->hapusBaris('DELETE FROM pemeriksaan_tindakan WHERE kunjungan_id IN (SELECT id FROM kunjungan WHERE no_kunjungan LIKE ?)', ['KJ-%']);
        $I->hapusBaris('DELETE FROM pemeriksaan WHERE kunjungan_id IN (SELECT id FROM kunjungan WHERE no_kunjungan LIKE ?)', ['KJ-%']);
        $I->hapusBaris('DELETE FROM resep_obat WHERE resep_id IN (SELECT id FROM resep WHERE kunjungan_id IN (SELECT id FROM kunjungan WHERE no_kunjungan LIKE ?))', ['KJ-%']);
        $I->hapusBaris('DELETE FROM resep WHERE kunjungan_id IN (SELECT id FROM kunjungan WHERE no_kunjungan LIKE ?)', ['KJ-%']);
        $I->hapusBaris('DELETE FROM kunjungan WHERE no_kunjungan LIKE ?', ['KJ-%']);
        $I->hapusBaris('DELETE FROM counter_antrean');
        $I->hapusBaris('DELETE FROM tindakan WHERE nama LIKE ?', ['Uji Tindakan%']);
        $I->hapusBaris('DELETE FROM jenis_lab WHERE nama LIKE ?', ['Uji Lab%']);
        FixtureUser::pulihkanAdmin($I);
    }

    public function simpanPemeriksaanUbahStatus(ApiTester $I): void
    {
        $kunjungan = $this->kunjunganUji($I);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->tokenDokter);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('kunjungan/' . $kunjungan['id'] . '/pemeriksaan', [
            'keluhan' => 'Demam 3 hari',
            'anamnesis' => 'Demam, batuk, nyeri tenggorokan',
            'tensi' => '120/80',
            'suhu' => 38.5,
            'nadi' => 96,
            'pernapasan' => 20,
            'tinggi_cm' => 170,
            'berat_kg' => 65,
            'hasil_fisik' => 'Tonsilitis',
            'diagnosis_utama' => 'Tonsilitis akut',
        ]);

        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['message' => 'Pemeriksaan disimpan.']);
        $I->seeResponseContainsJson(['data' => ['diagnosis_utama' => 'Tonsilitis akut']]);

        $I->sendGET('kunjungan/' . $kunjungan['id']);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['data' => ['status' => 'diperiksa']]);
    }

    public function diagnosisWajib(ApiTester $I): void
    {
        $kunjungan = $this->kunjunganUji($I);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->tokenDokter);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('kunjungan/' . $kunjungan['id'] . '/pemeriksaan', ['keluhan' => 'Demam']);

        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['message' => 'Diagnosis utama wajib diisi maksimal 128 karakter']);
    }

    public function pemeriksaanUntukKunjunganBatalDitolak(ApiTester $I): void
    {
        $kunjungan = $this->kunjunganUji($I);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->tokenAdmin);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPUT('kunjungan/' . $kunjungan['id'] . '/batal');
        $I->seeResponseCodeIs(200);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->tokenDokter);
        $I->sendPOST('kunjungan/' . $kunjungan['id'] . '/pemeriksaan', ['diagnosis_utama' => 'Flu']);

        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['message' => 'Kunjungan tidak sedang menunggu atau dalam pemeriksaan']);
    }

    public function tindakanTersalinTarif(ApiTester $I): void
    {
        $kunjungan = $this->kunjunganUji($I);
        $idTindakan = (string) $I->grabFromDatabase('tindakan', 'id', ['nama' => 'Injeksi intramuskular']);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->tokenDokter);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('kunjungan/' . $kunjungan['id'] . '/tindakan', ['tindakan_id' => $idTindakan]);
        $I->seeResponseCodeIs(201);
        $I->seeResponseContainsJson(['data' => ['tarif' => 15000]]);

        $I->sendPOST('kunjungan/' . $kunjungan['id'] . '/tindakan', ['tindakan_id' => $idTindakan]);
        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['message' => 'Tindakan ini sudah ditambahkan pada kunjungan']);

        $I->sendGET('kunjungan/' . $kunjungan['id'] . '/tindakan');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['data' => ['items' => [['tindakan' => ['nama' => 'Injeksi intramuskular']]]]]);
    }

    public function orderLabDilaksanakan(ApiTester $I): void
    {
        $kunjungan = $this->kunjunganUji($I);
        $idJenisLab = (string) $I->grabFromDatabase('jenis_lab', 'id', ['nama' => 'Darah lengkap']);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->tokenDokter);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('kunjungan/' . $kunjungan['id'] . '/lab', ['jenis_lab_id' => $idJenisLab]);
        $I->seeResponseCodeIs(201);
        $I->seeResponseContainsJson(['data' => ['status' => 'diminta', 'tarif' => 90000]]);

        $idOrder = (string) $I->grabDataFromResponseByJsonPath('$.data.id')[0];

        // Dokter tidak boleh mengisi hasil lab.
        $I->sendPUT('lab/' . $idOrder . '/hasil', ['hasil' => 'Leukosit naik']);
        $I->seeResponseCodeIs(403);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->tokenLab);
        $I->sendPUT('lab/' . $idOrder . '/hasil', ['hasil' => 'Leukosit naik 12.000']);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['data' => ['status' => 'selesai']]);

        $I->sendGET('lab', ['tanggal' => $kunjungan['tanggal']]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['data' => ['items' => [['id' => $idOrder]]]]);

        // Hasil terbaca dokter dari riwayat kunjungan.
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->tokenDokter);
        $I->sendGET('kunjungan/' . $kunjungan['id'] . '/lab');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['data' => ['items' => [['status' => 'selesai', 'hasil' => 'Leukosit naik 12.000']]]]);
    }

    public function selesaiTanpaResep(ApiTester $I): void
    {
        $kunjungan = $this->kunjunganUji($I);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->tokenDokter);
        $I->haveHttpHeader('Content-Type', 'application/json');

        $I->sendPOST('kunjungan/' . $kunjungan['id'] . '/pemeriksaan', ['diagnosis_utama' => 'Sembuh tanpa resep']);
        $I->seeResponseCodeIs(200);

        $I->sendPUT('kunjungan/' . $kunjungan['id'] . '/selesai');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['data' => ['status' => 'menunggu_bayar']]);
    }

    public function orderLabMasukOutbox(ApiTester $I): void
    {
        $kunjungan = $this->kunjunganUji($I);
        $idJenisLab = (string) $I->grabFromDatabase('jenis_lab', 'id', ['nama' => 'Urine rutin']);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->tokenDokter);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('kunjungan/' . $kunjungan['id'] . '/lab', ['jenis_lab_id' => $idJenisLab]);
        $I->seeResponseCodeIs(201);
        $idOrder = (string) $I->grabDataFromResponseByJsonPath('$.data.id')[0];

        $I->assertSame(
            1,
            (int) $I->grabNumRecords('outbox_lab', ['order_lab_id' => $idOrder, 'status' => 'pending']),
        );
        $payload = (string) $I->grabFromDatabase('outbox_lab', 'payload', ['order_lab_id' => $idOrder]);
        $I->assertStringContainsString('Urine rutin', $payload);
    }

    public function pullHasilMenerapkanHasilEksternal(ApiTester $I): void
    {
        $kunjungan = $this->kunjunganUji($I);
        $idJenisLab = (string) $I->grabFromDatabase('jenis_lab', 'id', ['nama' => 'Gula darah sewaktu']);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->tokenDokter);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('kunjungan/' . $kunjungan['id'] . '/lab', ['jenis_lab_id' => $idJenisLab]);
        $I->seeResponseCodeIs(201);
        $idOrder = (string) $I->grabDataFromResponseByJsonPath('$.data.id')[0];

        $I->eksekusi(
            "UPDATE outbox_lab SET status = 'sent', hasil = ? WHERE order_lab_id = ?",
            ['GDS 148 mg/dL', $idOrder],
        );

        $params = require dirname(__DIR__, 2) . '/config/params.php';
        $I->haveHttpHeader('X-Api-Key', $params['labApiKey']);
        $I->sendPOST('lab/pull-hasil');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['data' => ['diterapkan' => 1]]);

        $I->assertSame(
            'selesai',
            (string) $I->grabFromDatabase('order_lab', 'status', ['id' => $idOrder]),
        );
        $I->assertSame(
            'GDS 148 mg/dL',
            (string) $I->grabFromDatabase('order_lab', 'hasil', ['id' => $idOrder]),
        );
        $I->assertSame(
            'applied',
            (string) $I->grabFromDatabase('outbox_lab', 'status', ['order_lab_id' => $idOrder]),
        );

        $I->haveHttpHeader('X-Api-Key', 'salah');
        $I->sendPOST('lab/pull-hasil');
        $I->seeResponseCodeIs(401);
    }

    public function kasirDitolak(ApiTester $I): void
    {
        FixtureUser::buat($I, FixtureUser::KASIR, FixtureUser::PASSWORD_KASIR, 'kasir');
        $token = FixtureUser::token($I, FixtureUser::KASIR, FixtureUser::PASSWORD_KASIR);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->sendGET('lab');

        $I->seeResponseCodeIs(403);
        $I->seeResponseContainsJson(['message' => 'Anda tidak punya akses untuk aksi ini']);
    }

    private function kunjunganUji(ApiTester $I): array
    {
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->tokenAdmin);
        $I->haveHttpHeader('Content-Type', 'application/json');

        $idKaryawan = (string) $I->grabFromDatabase('karyawan', 'id', ['nama_karyawan' => 'dr. Andi Pratama']);
        $idJadwal = (string) $I->grabFromDatabase('jadwal_praktek', 'id', ['karyawan_id' => $idKaryawan, 'hari' => 1]);
        $idPoli = (string) $I->grabFromDatabase('poli', 'id', ['kode' => 'POL-0001']);

        $tanggal = '';
        for ($i = 1; $i <= 7; $i++) {
            $t = date('Y-m-d', strtotime("+{$i} days"));

            if (date('N', strtotime($t)) === '1') {
                $tanggal = $t;
                break;
            }
        }

        $I->sendPOST('pasien', ['nama' => 'Uji Pasien', 'jenis_kelamin' => 'L']);
        $idPasien = (string) $I->grabDataFromResponseByJsonPath('$.data.id')[0];

        $I->sendPOST('kunjungan', [
            'idempotency_key' => 'kunjungan-pemeriksaan-' . uniqid(),
            'pasien_id' => $idPasien,
            'jadwal_id' => $idJadwal,
            'tgl_kunjungan' => $tanggal,
        ]);
        $I->seeResponseCodeIs(201);

        return [
            'id' => (string) $I->grabDataFromResponseByJsonPath('$.data.id')[0],
            'tanggal' => $tanggal,
            'poli_id' => $idPoli,
        ];
    }
}
