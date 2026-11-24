<?php

declare(strict_types=1);

namespace app\tests\Api;

use app\tests\Support\ApiTester;
use app\tests\Support\FixtureUser;

class MasterCest
{
    private const PASIEN = 'Uji Pasien';
    
    public function _before(ApiTester $I): void
    {
        $I->hapusBaris('DELETE FROM obat WHERE nama_obat = ?', ['Paracetamol Uji']);
        $I->hapusBaris('DELETE FROM jadwal_praktek WHERE karyawan_id IN (SELECT id FROM karyawan WHERE nama_karyawan LIKE ?)', ['Uji %']);
        $I->hapusBaris('DELETE FROM karyawan WHERE nama_karyawan LIKE ?', ['Uji %']);
        $I->hapusBaris('DELETE FROM "user" WHERE username = ?', ['uji_akun']);
        $I->hapusBaris('DELETE FROM poli WHERE kode = ?', ['UJIPOLI']);
        $I->hapusBaris('DELETE FROM pasien WHERE nama LIKE ?', ['Uji %']);
        FixtureUser::pulihkanAdmin($I);
    }

    public function _after(ApiTester $I): void
    {
        $I->hapusBaris('DELETE FROM obat WHERE nama_obat = ?', ['Paracetamol Uji']);
        $I->hapusBaris('DELETE FROM jadwal_praktek WHERE karyawan_id IN (SELECT id FROM karyawan WHERE nama_karyawan LIKE ?)', ['Uji %']);
        $I->hapusBaris('DELETE FROM karyawan WHERE nama_karyawan LIKE ?', ['Uji %']);
        $I->hapusBaris('DELETE FROM "user" WHERE username = ?', ['uji_akun']);
        $I->hapusBaris('DELETE FROM poli WHERE kode = ?', ['UJIPOLI']);
        $I->hapusBaris('DELETE FROM pasien WHERE nama LIKE ?', ['Uji %']);
        FixtureUser::pulihkanAdmin($I);
    }

    public function buatPasien(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'admin', 'admin123');
        $body = [
            'nama' => self::PASIEN,
            'nik' => '3201010101010001',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'L',
            'no_hp' => '081234567890',
            'alamat' => 'Jl. Uji No. 1',
        ];

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('pasien', $body);
        $I->seeResponseCodeIs(201);
        $I->seeResponseContainsJson(['message' => 'Pasien ditambahkan.']);

        $noRm = (string) $I->grabDataFromResponseByJsonPath('$.data.no_rm')[0];
        $I->assertStringStartsWith('RM', $noRm);

        $I->sendPOST('pasien', $body);
        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['message' => 'NIK sudah dipakai pasien lain']);

        $I->grabFromDatabase('pasien', 'no_rm', ['nama' => self::PASIEN]);
        $I->assertNotEmpty($noRm);
    }

    public function ubahDanStatusPasien(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'admin', 'admin123');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('pasien', ['nama' => self::PASIEN, 'jenis_kelamin' => 'P']);
        $id = (string) $I->grabDataFromResponseByJsonPath('$.data.id')[0];

        $I->sendPUT('pasien/' . $id, [
            'nama' => self::PASIEN . ' Ubah',
            'jenis_kelamin' => 'P',
            'nik' => '3201010101010009',
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['message' => 'Pasien diubah.', 'data' => ['nama' => self::PASIEN . ' Ubah']]);

        $I->sendPUT('pasien/' . $id . '/status', ['is_active' => false]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['data' => ['is_active' => false]]);
    }

    public function nikTidakValidDitolak(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'admin', 'admin123');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('pasien', ['nama' => self::PASIEN, 'jenis_kelamin' => 'L', 'nik' => '12345']);

        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['message' => 'NIK harus 16 digit angka']);
    }

    public function buatPoliDanKodeDuplikat(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'admin', 'admin123');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->haveHttpHeader('Content-Type', 'application/json');

        $I->sendPOST('poli', [
            'kode' => 'UJIPOLI',
            'nama' => 'Poli Uji',
            'biaya_daftar' => 50000,
        ]);
        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['message' => 'Initial antrean wajib 3 huruf']);

        $I->sendPOST('poli', [
            'kode' => 'UJIPOLI',
            'nama' => 'Poli Uji',
            'biaya_daftar' => 50000,
            'initial' => 'uji',
        ]);
        $I->seeResponseCodeIs(201);
        $I->seeResponseContainsJson(['message' => 'Poli ditambahkan.', 'data' => ['initial' => 'UJI']]);
        $idPoli = (string) $I->grabDataFromResponseByJsonPath('$.data.id')[0];

        $I->sendPUT('poli/' . $idPoli, ['nama' => 'Poli Uji', 'biaya_daftar' => 50000, 'initial' => 'THT']);
        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['message' => 'Initial antrean sudah dipakai poli lain']);

        $I->sendPUT('poli/' . $idPoli, ['nama' => 'Poli Uji', 'biaya_daftar' => 50000, 'initial' => 'UJI']);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['data' => ['initial' => 'UJI']]);

        $I->sendPOST('poli', ['kode' => 'UJIPOLI', 'nama' => 'Poli Lain', 'initial' => 'UJI']);
        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['message' => 'Kode poli sudah dipakai']);
    }

    public function buatKaryawanDokter(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'admin', 'admin123');
        $idJabatanDokter = (string) $I->grabFromDatabase('jabatan', 'id', ['kode_jabatan' => 'dokter']);
        $idSpesialisUmum = (string) $I->grabFromDatabase('spesialis', 'id', ['kode_spesialis' => 'umum']);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->haveHttpHeader('Content-Type', 'application/json');

        $I->sendPOST('karyawan', [
            'nama_karyawan' => 'Uji Dokter',
            'jenis_kelamin' => 'L',
            'jabatan_id' => $idJabatanDokter,
            'spesialis_id' => $idSpesialisUmum,
            'tanggal_bergabung' => '2026-01-01',
        ]);
        $I->seeResponseCodeIs(201);
        $I->seeResponseContainsJson(['message' => 'Karyawan ditambahkan.']);

        $kode1 = (string) $I->grabDataFromResponseByJsonPath('$.data.kode_karyawan')[0];
        $I->assertStringStartsWith('KRY-', $kode1);

        $I->sendPOST('karyawan', [
            'nama_karyawan' => 'Uji Dokter Dua',
            'jenis_kelamin' => 'L',
            'jabatan_id' => $idJabatanDokter,
            'spesialis_id' => $idSpesialisUmum,
        ]);
        $kode2 = (string) $I->grabDataFromResponseByJsonPath('$.data.kode_karyawan')[0];
        $I->assertNotSame($kode1, $kode2);
    }

    public function karyawanDokterTanpaSpesialisDitolak(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'admin', 'admin123');
        $idJabatanDokter = (string) $I->grabFromDatabase('jabatan', 'id', ['kode_jabatan' => 'dokter']);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('karyawan', ['nama_karyawan' => 'Uji Dokter', 'jenis_kelamin' => 'L', 'jabatan_id' => $idJabatanDokter]);

        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['message' => 'Spesialis wajib dipilih untuk jabatan Dokter']);
    }

    public function jadwalBukanDokterDitolak(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'admin', 'admin123');
        $idJabatanKasir = (string) $I->grabFromDatabase('jabatan', 'id', ['kode_jabatan' => 'kasir']);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('karyawan', ['nama_karyawan' => 'Uji Kasir', 'jenis_kelamin' => 'P', 'jabatan_id' => $idJabatanKasir]);
        $idKaryawan = (string) $I->grabDataFromResponseByJsonPath('$.data.id')[0];

        $I->sendPOST('jadwal', [
            'karyawan_id' => $idKaryawan,
            'poli_id' => $this->buatPoli($I),
            'sesi' => [
                ['hari' => 1, 'jam_mulai' => '08:00', 'jam_selesai' => '12:00', 'kuota' => 10],
            ],
        ]);

        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['message' => 'Jadwal praktek hanya untuk karyawan berjabatan Dokter']);
    }

    public function buatJadwalSesiBanyak(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'admin', 'admin123');
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

        $I->sendPOST('jadwal', [
            'karyawan_id' => $idKaryawan,
            'poli_id' => $this->buatPoli($I),
            'sesi' => [
                ['hari' => 1, 'jam_mulai' => '08:00', 'jam_selesai' => '12:00', 'kuota' => 30],
                ['hari' => 3, 'jam_mulai' => '08:00', 'jam_selesai' => '12:00', 'kuota' => 30],
                ['hari' => 5, 'jam_mulai' => '13:00', 'jam_selesai' => '17:00', 'kuota' => 20],
            ],
        ]);

        $I->seeResponseCodeIs(201);
        $I->seeResponseContainsJson(['message' => 'Jadwal ditambahkan.']);
        $I->assertSame(3, (int) $I->grabNumRecords('jadwal_praktek', ['karyawan_id' => $idKaryawan]));
    }

    public function jadwalOverlapDitolak(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'admin', 'admin123');
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
        $idPoli = $this->buatPoli($I);

        $I->sendPOST('jadwal', [
            'karyawan_id' => $idKaryawan,
            'poli_id' => $idPoli,
            'sesi' => [
                ['hari' => 1, 'jam_mulai' => '08:00', 'jam_selesai' => '12:00', 'kuota' => 30],
            ],
        ]);
        $I->seeResponseCodeIs(201);

        $I->sendPOST('jadwal', [
            'karyawan_id' => $idKaryawan,
            'poli_id' => $idPoli,
            'sesi' => [
                ['hari' => 1, 'jam_mulai' => '10:00', 'jam_selesai' => '14:00', 'kuota' => 20],
            ],
        ]);

        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['message' => 'Jadwal bertabrakan dengan jadwal lain dokter ini']);
    }

    public function sesiDuplikatDalamFormDitolak(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'admin', 'admin123');
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

        $I->sendPOST('jadwal', [
            'karyawan_id' => $idKaryawan,
            'poli_id' => $this->buatPoli($I),
            'sesi' => [
                ['hari' => 1, 'jam_mulai' => '08:00', 'jam_selesai' => '12:00', 'kuota' => 30],
                ['hari' => 1, 'jam_mulai' => '08:00', 'jam_selesai' => '12:00', 'kuota' => 30],
            ],
        ]);

        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['message' => 'Ada sesi dengan hari dan jam mulai yang sama']);
    }

    public function buatObat(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'admin', 'admin123');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->haveHttpHeader('Content-Type', 'application/json');

        $idKategori = (string) $I->grabFromDatabase('kategori', 'id', ['kode_kategori' => 'KTG-0001']);
        $idGolongan = (string) $I->grabFromDatabase('golongan_obat', 'id', ['kode_golongan_obat' => 'GLG-0002']);
        $idSatuan = (string) $I->grabFromDatabase('satuan', 'id', ['kode_satuan' => 'STN-0001']);

        $I->sendPOST('obat', [
            'nama_obat' => 'Paracetamol Uji',
            'kategori_id' => $idKategori,
            'golongan_obat_id' => $idGolongan,
            'satuan_id' => $idSatuan,
            'harga_beli' => 800,
            'harga_jual' => 1500,
            'stok_minimum' => 10,
        ]);
        $I->seeResponseCodeIs(201);
        $I->seeResponseContainsJson(['message' => 'Obat ditambahkan.']);
        $I->assertStringStartsWith('OBT-', (string) $I->grabDataFromResponseByJsonPath('$.data.kode_obat')[0]);
    }

    public function ubahAkunKaryawan(ApiTester $I): void
    {
        FixtureUser::buat($I, 'uji_akun', 'rahasia123', 'kasir');
        $token = FixtureUser::token($I, 'admin', 'admin123');
        $idJabatanDokter = (string) $I->grabFromDatabase('jabatan', 'id', ['kode_jabatan' => 'dokter']);
        $idSpesialisUmum = (string) $I->grabFromDatabase('spesialis', 'id', ['kode_spesialis' => 'umum']);
        $idUser = (string) $I->grabFromDatabase('user', 'id', ['username' => 'uji_akun']);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('karyawan', [
            'nama_karyawan' => 'Uji Dokter',
            'jenis_kelamin' => 'L',
            'jabatan_id' => $idJabatanDokter,
            'spesialis_id' => $idSpesialisUmum,
        ]);
        $I->seeResponseCodeIs(201);
        $id = (string) $I->grabDataFromResponseByJsonPath('$.data.id')[0];

        $I->sendPUT('karyawan/' . $id . '/akun', ['user_id' => $idUser]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['message' => 'Akun karyawan diubah.', 'data' => ['user' => ['username' => 'uji_akun']]]);

        $I->sendPOST('karyawan', [
            'nama_karyawan' => 'Uji Dokter Dua',
            'jenis_kelamin' => 'L',
            'jabatan_id' => $idJabatanDokter,
            'spesialis_id' => $idSpesialisUmum,
        ]);
        $idDua = (string) $I->grabDataFromResponseByJsonPath('$.data.id')[0];

        $I->sendPUT('karyawan/' . $idDua . '/akun', ['user_id' => $idUser]);
        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['message' => 'Akun user sudah dipakai karyawan lain']);

        $I->sendPUT('karyawan/' . $id . '/akun', ['user_id' => null]);
        $I->seeResponseCodeIs(200);
        $I->assertNull($I->grabDataFromResponseByJsonPath('$.data.user')[0] ?? null);
    }

    public function logAktivitasTercatat(ApiTester $I): void
    {
        $token = FixtureUser::token($I, 'admin', 'admin123');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('pasien', ['nama' => self::PASIEN, 'jenis_kelamin' => 'L']);
        $id = (string) $I->grabDataFromResponseByJsonPath('$.data.id')[0];

        $aksi = (string) $I->grabFromDatabase('log_aktivitas', 'aksi', [
            'tabel' => 'pasien',
            'record_id' => $id,
        ]);
        $I->assertSame('simpan', $aksi);
    }

    public function masterDitolakUntukKasir(ApiTester $I): void
    {
        FixtureUser::buat($I, FixtureUser::KASIR, FixtureUser::PASSWORD_KASIR, 'kasir');
        $token = FixtureUser::token($I, FixtureUser::KASIR, FixtureUser::PASSWORD_KASIR);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $I->sendGET('pasien');

        $I->seeResponseCodeIs(403);
        $I->seeResponseContainsJson(['message' => 'Anda tidak punya akses untuk aksi ini']);
    }

    private function buatPoli(ApiTester $I): string
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('poli', [
            'kode' => 'UJIPOLI',
            'nama' => 'Poli Uji',
            'biaya_daftar' => 50000,
            'initial' => 'UJP',
        ]);
        $I->seeResponseCodeIs(201);

        return (string) $I->grabDataFromResponseByJsonPath('$.data.id')[0];
    }
}


