<?php

declare(strict_types=1);

namespace app\tests\Api;

use app\tests\Support\ApiTester;
use app\tests\Support\FixtureUser;

class KunjunganCest
{
    private const ID_KEY = 'kunjungan-test-0001';

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
        $I->hapusBaris('DELETE FROM kunjungan WHERE pasien_id IN (SELECT id FROM pasien WHERE nama LIKE ?)', ['Uji %']);
        $I->hapusBaris('DELETE FROM kunjungan WHERE dokter_id IN (SELECT id FROM karyawan WHERE nama_karyawan LIKE ?)', ['Uji %']);
        $I->hapusBaris('DELETE FROM kunjungan WHERE no_kunjungan LIKE ?', ['KJ-%']);
        $I->hapusBaris('DELETE FROM counter_antrean');
        $I->hapusBaris('DELETE FROM jadwal_praktek WHERE karyawan_id IN (SELECT id FROM karyawan WHERE nama_karyawan LIKE ?)', ['Uji %']);
        $I->hapusBaris('DELETE FROM jadwal_praktek WHERE poli_id IN (SELECT id FROM poli WHERE kode = ?)', ['UJIPOLI']);
        $I->hapusBaris('DELETE FROM poli WHERE kode = ?', ['UJIPOLI']);
        $I->hapusBaris('DELETE FROM pasien WHERE nama LIKE ?', ['Uji %']);
        $I->hapusBaris('DELETE FROM karyawan WHERE nama_karyawan LIKE ?', ['Uji %']);
        FixtureUser::pulihkanAdmin($I);
        FixtureUser::pulihkanAksesRole($I, 'kasir');
    }

    public function daftarKunjungan(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'admin', 'admin123');
        $isi = $this->siapkan($I, $token);
        $pasienKedua = $this->buatPasien($I, 'Uji Pasien Dua');

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('kunjungan', $this->payload($isi, $pasienKedua, self::ID_KEY));

        $I->seeResponseCodeIs(201);
        $I->seeResponseContainsJson(['message' => 'Kunjungan ditambahkan.']);
        $I->seeResponseContainsJson(['data' => ['no_antrean' => 1, 'status' => 'menunggu']]);
        $I->seeResponseContainsJson(['data' => ['kode_antrean' => 'UJP-001']]);

        $noKunjungan = (string) $I->grabDataFromResponseByJsonPath('$.data.no_kunjungan')[0];
        $id = (string) $I->grabDataFromResponseByJsonPath('$.data.id')[0];
        $I->assertMatchesRegularExpression('/^KJ-\d{8}-\d{4,}$/', $noKunjungan);

        $I->sendGET('kunjungan/' . $id);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['data' => ['no_antrean' => 1]]);

        $I->sendPOST('kunjungan', $this->payload($isi, $this->buatPasien($I, 'Uji Pasien Tiga'), self::ID_KEY . '-B'));
        $I->seeResponseCodeIs(201);
        $I->seeResponseContainsJson(['data' => ['no_antrean' => 2]]);
    }

    public function idempotencyMengembalikanKunjunganSama(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'admin', 'admin123');
        $isi = $this->siapkan($I, $token);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('kunjungan', $this->payload($isi, $isi['pasien_id'], self::ID_KEY));
        $I->seeResponseCodeIs(201);

        $I->sendPOST('kunjungan', $this->payload($isi, $isi['pasien_id'], self::ID_KEY));
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['message' => 'Kunjungan sudah terdaftar sebelumnya.']);

        $I->assertSame(1, (int) $I->grabNumRecords('kunjungan', ['jadwal_id' => $isi['jadwal_id']]));
    }

    public function pasienDobelDitolak(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'admin', 'admin123');
        $isi = $this->siapkan($I, $token);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('kunjungan', $this->payload($isi, $isi['pasien_id'], self::ID_KEY));
        $I->seeResponseCodeIs(201);

        $I->sendPOST('kunjungan', $this->payload($isi, $isi['pasien_id'], self::ID_KEY . '-B'));
        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['message' => 'Pasien ini sudah terdaftar pada jadwal dan tanggal yang sama']);
    }

    public function tanggalBukanHariPraktekDitolak(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'admin', 'admin123');
        $isi = $this->siapkan($I, $token);

        $isi['tanggal'] = date('Y-m-d', strtotime($isi['tanggal'] . ' +1 day'));

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('kunjungan', $this->payload($isi, $isi['pasien_id'], self::ID_KEY));

        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['message' => 'Tanggal kunjungan bukan hari praktek jadwal ini']);
    }

    public function batalKunjungan(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'admin', 'admin123');
        $isi = $this->siapkan($I, $token);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('kunjungan', $this->payload($isi, $isi['pasien_id'], self::ID_KEY));
        $I->seeResponseCodeIs(201);
        $id = (string) $I->grabDataFromResponseByJsonPath('$.data.id')[0];

        $I->sendPUT('kunjungan/' . $id . '/batal');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['message' => 'Kunjungan dibatalkan.', 'data' => ['status' => 'batal']]);

        $I->sendPOST('kunjungan', $this->payload($isi, $isi['pasien_id'], self::ID_KEY . '-B'));
        $I->seeResponseCodeIs(201);
        $I->seeResponseContainsJson(['data' => ['no_antrean' => 2]]);
    }

    public function transisiIlegalDitolak(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'admin', 'admin123');
        $isi = $this->siapkan($I, $token);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('kunjungan', $this->payload($isi, $isi['pasien_id'], self::ID_KEY));
        $I->seeResponseCodeIs(201);
        $id = (string) $I->grabDataFromResponseByJsonPath('$.data.id')[0];

        $I->sendPUT('kunjungan/' . $id . '/batal');
        $I->seeResponseCodeIs(200);

        $I->sendPUT('kunjungan/' . $id . '/batal');
        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['message' => 'Hanya kunjungan berstatus menunggu yang bisa dibatalkan']);

        $I->eksekusi('UPDATE kunjungan SET status = ? WHERE id = ?', ['selesai', $id]);
        $pesan = '';

        try {
            $I->eksekusi('SELECT fn_ubah_status_kunjungan(?, ?)', [$id, 'menunggu']);
        } catch (\PDOException $e) {
            $pesan = $e->getMessage();
        }

        $I->assertStringContainsString('tidak diizinkan', $pesan);
    }

    public function jadwalTersediaMenampilkanSisa(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'admin', 'admin123');
        $isi = $this->siapkan($I, $token);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->sendGET('kunjungan/jadwal-tersedia', [
            'poli_id' => $isi['poli_id'],
            'tanggal' => $isi['tanggal'],
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'data' => [
                'items' => [
                    ['id' => $isi['jadwal_id'], 'kuota' => 30, 'terisi' => 0, 'sisa' => 30],
                ],
            ],
        ]);    }

    public function kuotaPenuhDitolak(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'admin', 'admin123');
        $isi = $this->siapkan($I, $token, 1);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('kunjungan', $this->payload($isi, $isi['pasien_id'], self::ID_KEY));
        $I->seeResponseCodeIs(201);

        $I->sendPOST('kunjungan', $this->payload($isi, $this->buatPasien($I, 'Uji Pasien Dua'), self::ID_KEY . '-B'));
        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['message' => 'Kuota jadwal praktek sudah penuh']);
    }

    public function antreanTampil(ApiTester $I): void
    {        $token = FixtureUser::token($I, 'admin', 'admin123');
        $isi = $this->siapkan($I, $token);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('kunjungan', $this->payload($isi, $isi['pasien_id'], self::ID_KEY));
        $I->seeResponseCodeIs(201);

        $I->sendGET('antrean', ['poli_id' => $isi['poli_id'], 'tanggal' => $isi['tanggal']]);
        $I->seeResponseCodeIs(200);

        $antrean = $I->grabDataFromResponseByJsonPath('$.data.items[*].no_antrean');
        $I->assertContains(1, $antrean);
        $I->seeResponseContainsJson(['data' => ['items' => [['kode_antrean' => 'UJP-001']]]]);
    }

    public function kasirDitolak(ApiTester $I): void
    {
        FixtureUser::buat($I, FixtureUser::KASIR, FixtureUser::PASSWORD_KASIR, 'kasir');
        $token = FixtureUser::token($I, FixtureUser::KASIR, FixtureUser::PASSWORD_KASIR);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->sendGET('kunjungan');

        $I->seeResponseCodeIs(403);
        $I->seeResponseContainsJson(['message' => 'Anda tidak punya akses untuk aksi ini']);
    }

    private function siapkan(ApiTester $I, string $token, int $kuota = 30): array
    {
        $idJabatanDokter = (string) $I->grabFromDatabase('jabatan', 'id', ['kode_jabatan' => 'dokter']);
        $idSpesialisUmum = (string) $I->grabFromDatabase('spesialis', 'id', ['kode_spesialis' => 'umum']);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('karyawan', [
            'nama_karyawan' => 'Uji Dokter',
            'jenis_kelamin' => 'L',
            'jabatan_id' => $idJabatanDokter,
            'spesialis_id' => $idSpesialisUmum,
        ]);
        $idKaryawan = (string) $I->grabDataFromResponseByJsonPath('$.data.id')[0];

        $I->sendPOST('poli', [
            'kode' => 'UJIPOLI',
            'nama' => 'Poli Uji',
            'biaya_daftar' => 50000,
            'initial' => 'UJP',
        ]);
        $idPoli = (string) $I->grabDataFromResponseByJsonPath('$.data.id')[0];

        $I->sendPOST('jadwal', [
            'karyawan_id' => $idKaryawan,
            'poli_id' => $idPoli,
            'sesi' => [
                ['hari' => 1, 'jam_mulai' => '08:00', 'jam_selesai' => '12:00', 'kuota' => $kuota],
            ],
        ]);
        $I->seeResponseCodeIs(201);
        $idJadwal = (string) $I->grabDataFromResponseByJsonPath('$.data.items[0].id')[0];

        return [
            'jadwal_id' => $idJadwal,
            'poli_id' => $idPoli,
            'pasien_id' => $this->buatPasien($I, 'Uji Pasien'),
            'tanggal' => $this->tanggalSenin(),
        ];
    }

    private function payload(array $isi, string $pasienId, string $key): array
    {
        return [
            'idempotency_key' => $key,
            'pasien_id' => $pasienId,
            'jadwal_id' => $isi['jadwal_id'],
            'tgl_kunjungan' => $isi['tanggal'],
        ];
    }

    private function buatPasien(ApiTester $I, string $nama): string
    {
        $I->sendPOST('pasien', ['nama' => $nama, 'jenis_kelamin' => 'L']);
        $I->seeResponseCodeIs(201);

        return (string) $I->grabDataFromResponseByJsonPath('$.data.id')[0];
    }

    private function tanggalSenin(): string
    {
        foreach (range(1, 14) as $i) {
            $tanggal = date('Y-m-d', strtotime("+{$i} days"));

            if (date('N', strtotime($tanggal)) === '1') {
                return $tanggal;
            }
        }

        return date('Y-m-d');
    }
}
