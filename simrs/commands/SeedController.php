<?php

declare(strict_types=1);

namespace app\commands;

use app\models\GolonganObat;
use app\models\Jabatan;
use app\models\Kategori;
use app\models\Karyawan;
use app\models\Obat;
use app\models\Pasien;
use app\models\Poli;
use app\models\Role;
use app\models\Satuan;
use app\models\Spesialis;
use app\models\User;
use app\models\UserAccess;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

class SeedController extends Controller
{
    private const PASSWORD_ADMIN = 'admin123';
    private const PASSWORD_DEMO = 'password123';

    public function actionAdmin(): int
    {
        $role = Role::findOne(['role_code' => 'admin']);
        if ($role === null) {
            $this->stderr("Role admin belum ada. Jalankan `yii rbac/init` lebih dulu.\n");

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $user = User::findOne(['username' => 'admin']);
        $baru = $user === null;

        if ($baru) {
            $user = new User(['username' => 'admin']);
            $user->password_hash = Yii::$app->security->generatePasswordHash(self::PASSWORD_ADMIN);
        }

        $user->full_name = 'Administrator';
        $user->email = 'admin@simrs.local';
        $user->role_id = $role->id;
        $user->is_active = true;
        $user->is_delete = false;

        if (!$user->save(false)) {
            $this->stderr("Gagal menyimpan user admin.\n");

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $aksesBaru = UserAccess::sinkron($user);

        $this->stdout($baru ? "Akun admin dibuat.\n" : "Akun admin sudah ada, password tidak diubah.\n");
        $this->stdout('Username: admin, password: ' . self::PASSWORD_ADMIN . "\n");
        $this->stdout("user_access baru: {$aksesBaru}\n");

        return ExitCode::OK;
    }

    private const POLI_AWAL = [
        ['Umum', 30000],
        ['Gigi', 45000],
        ['Anak', 40000],
        ['Kandungan', 60000],
        ['THT', 50000],
    ];

    private const USER_AWAL = [
        ['sari', 'Sari Dewi', 'pendaftaran'],
        ['budi', 'Budi Santoso', 'kasir'],
        ['maya', 'Maya Putri', 'farmasi'],
        ['andi', 'dr. Andi Pratama', 'dokter'],
        ['lina', 'Lina Maharani', 'laboratorium'],
    ];

    private const KARYAWAN_AWAL = [
        ['dr. Andi Pratama', 'L', '1990-03-15', 'dokter', 'umum', 'andi', '2018-01-01'],
        ['dr. Sinta Maharani', 'P', '1992-07-21', 'dokter', 'anak', null, '2020-02-01'],
        ['Rina Wulandari', 'P', '1994-11-02', 'perawat', null, null, '2019-06-01'],
        ['Maya Putri', 'P', '1993-02-17', 'apoteker', null, 'maya', '2019-08-01'],
        ['Budi Santoso', 'L', '1991-09-09', 'kasir', null, 'budi', '2021-01-01'],
        ['Sari Dewi', 'P', '1996-04-25', 'pendaftaran', null, 'sari', '2022-03-01'],
    ];

    private const SATUAN_AWAL = [
        'Tablet',
        'Kapsul',
        'Strip',
        'Box',
        'Botol',
        'Tube',
        'Sachet',
        'Ampul',
        'Vial',
        'Bungkus',
    ];

    private const KATEGORI_AWAL = [
        'Tablet',
        'Kaplet',
        'Kapsul',
        'Puyer',
        'Sirup',
        'Salep',
        'Krim',
        'Gel',
        'Tetes',
        'Injeksi',
        'Supositoria',
    ];

    private const GOLONGAN_AWAL = [
        'Obat Bebas',
        'Obat Bebas Terbatas',
        'Obat Keras',
        'Obat Wajib Apotek',
        'Psikotropika',
        'Narkotika',
    ];

    private const OBAT_AWAL = [
        ['Paracetamol 500 mg', 'KTG-0001', 'GLG-0001', 'STN-0001', 800, 1500, 100],
        ['Ibuprofen 400 mg', 'KTG-0001', 'GLG-0002', 'STN-0001', 1500, 2500, 50],
        ['Mefenamat Asam 500 mg', 'KTG-0002', 'GLG-0002', 'STN-0002', 1800, 3000, 50],
        ['Cetirizine 10 mg', 'KTG-0001', 'GLG-0002', 'STN-0001', 1500, 2500, 100],
        ['Amoxicillin 500 mg', 'KTG-0003', 'GLG-0003', 'STN-0002', 1200, 2000, 200],
        ['Cefadroxil 500 mg', 'KTG-0003', 'GLG-0003', 'STN-0002', 5500, 7500, 100],
        ['Omeprazole 20 mg', 'KTG-0003', 'GLG-0003', 'STN-0002', 2200, 3500, 50],
        ['Metformin 500 mg', 'KTG-0001', 'GLG-0003', 'STN-0001', 900, 1500, 100],
        ['Amlodipine 5 mg', 'KTG-0001', 'GLG-0003', 'STN-0001', 900, 1500, 50],
        ['Antasida DOEN', 'KTG-0005', 'GLG-0001', 'STN-0005', 9500, 15000, 20],
    ];

    private const PASIEN_AWAL = [
        ['Agus Setiawan', 'L', '1980-01-12', '3201012345670001', '081200000001', 'Jl. Merdeka No. 1'],
        ['Bunga Lestari', 'P', '1992-06-08', '3201012345670002', '081200000002', 'Jl. Kenanga No. 7'],
        ['Candra Wijaya', 'L', '1975-11-30', '3201012345670003', '081200000003', 'Jl. Anggrek No. 15'],
        ['Dewi Anggraini', 'P', '1998-03-22', '3201012345670004', '081200000004', 'Jl. Melati No. 3'],
        ['Eko Prasetyo', 'L', '2001-08-14', '3201012345670005', '081200000005', 'Jl. Mawar No. 9'],
        ['Fitri Handayani', 'P', '1986-12-05', '3201012345670006', '081200000006', 'Jl. Cemara No. 21'],
    ];

    public function actionMaster(): int
    {
        if (Role::findOne(['role_code' => 'admin']) === null
            || Jabatan::findOne(['kode_jabatan' => 'dokter']) === null
        ) {
            $this->stderr("Data dasar belum ada. Jalankan `yii rbac/init` dan `yii seed/admin` lebih dulu.\n");

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $transaksi = Yii::$app->db->beginTransaction();

        try {
            $this->isiPoli();
            $this->isiUser();
            $this->isiKaryawan();
            $this->isiReferensiObat();
            $this->isiObat();
            $this->isiPasien();

            $transaksi->commit();        } catch (\Throwable $e) {
            $transaksi->rollBack();
            $this->stderr("Gagal: {$e->getMessage()}\n");

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout("Data master selesai. Akun demo memakai password: " . self::PASSWORD_DEMO . "\n");

        return ExitCode::OK;
    }

    private function isiPoli(): void
    {
        foreach (self::POLI_AWAL as $i => [$nama, $biaya]) {
            $kode = 'POL-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT);

            if (Poli::find()->where(['kode' => $kode])->exists()) {
                continue;
            }

            $poli = new Poli(['kode' => $kode, 'nama' => $nama, 'biaya_daftar' => $biaya]);

            if (!$poli->save(false)) {
                throw new \RuntimeException("gagal menyimpan poli {$kode}");
            }
        }
    }

    private function isiUser(): void
    {
        foreach (self::USER_AWAL as [$username, $nama, $kodeRole]) {
            if (User::find()->where(['username' => $username])->exists()) {
                continue;
            }

            $role = Role::findOne(['role_code' => $kodeRole]);

            if ($role === null) {
                throw new \RuntimeException("role {$kodeRole} tidak ditemukan");
            }

            $user = new User(['username' => $username]);
            $user->password_hash = Yii::$app->security->generatePasswordHash(self::PASSWORD_DEMO);
            $user->full_name = $nama;
            $user->email = $username . '@simrs.local';
            $user->role_id = (string) $role->id;
            $user->is_active = true;
            $user->is_delete = false;

            if (!$user->save(false)) {
                throw new \RuntimeException("gagal menyimpan user {$username}");
            }

            UserAccess::sinkron($user);
        }
    }

    private function isiKaryawan(): void
    {
        $nomor = (int) Yii::$app->db->createCommand(
            "SELECT COALESCE(MAX(SUBSTRING(kode_karyawan FROM 5)::int), 0) FROM karyawan WHERE kode_karyawan LIKE 'KRY-%'",
        )->queryScalar();

        foreach (self::KARYAWAN_AWAL as [$nama, $jk, $lahir, $kodeJabatan, $kodeSpesialis, $username, $gabung]) {
            if (Karyawan::find()->where(['nama_karyawan' => $nama])->exists()) {
                continue;
            }

            $jabatan = Jabatan::findOne(['kode_jabatan' => $kodeJabatan]);
            $spesialis = $kodeSpesialis === null ? null : Spesialis::findOne(['kode_spesialis' => $kodeSpesialis]);
            $user = $username === null ? null : User::findOne(['username' => $username]);

            if ($jabatan === null || $spesialis === null && $kodeSpesialis !== null) {
                throw new \RuntimeException("data jabatan/spesialis belum lengkap untuk {$nama}");
            }

            $nomor++;
            $karyawan = new Karyawan([
                'kode_karyawan' => 'KRY-' . str_pad((string) $nomor, 4, '0', STR_PAD_LEFT),
                'nama_karyawan' => $nama,
                'jenis_kelamin' => $jk,
                'tanggal_lahir' => $lahir,
                'jabatan_id' => (string) $jabatan->id,
                'spesialis_id' => $spesialis === null ? null : (string) $spesialis->id,
                'user_id' => $user === null ? null : (string) $user->id,
                'tanggal_bergabung' => $gabung,
            ]);

            if (!$karyawan->save(false)) {
                throw new \RuntimeException("gagal menyimpan karyawan {$nama}");
            }
        }
    }

    private function isiReferensiObat(): void
    {
        $tabel = [
            'satuan' => ['kode_satuan', 'nama_satuan', Satuan::class, self::SATUAN_AWAL],
            'kategori' => ['kode_kategori', 'nama_kategori', Kategori::class, self::KATEGORI_AWAL],
            'golongan_obat' => ['kode_golongan_obat', 'nama_golongan_obat', GolonganObat::class, self::GOLONGAN_AWAL],
        ];

        foreach ($tabel as [$kolomKode, $kolomNama, $kelas, $daftar]) {
            foreach ($daftar as $i => $nama) {
                $kode = $kelas === Satuan::class
                    ? 'STN-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT)
                    : ($kelas === Kategori::class
                        ? 'KTG-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT)
                        : 'GLG-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT));

                if ($kelas::find()->where([$kolomKode => $kode])->exists()) {
                    continue;
                }

                $baris = new $kelas([$kolomKode => $kode, $kolomNama => $nama]);

                if (!$baris->save(false)) {
                    throw new \RuntimeException("gagal menyimpan {$kolomKode} {$kode}");
                }
            }
        }
    }

    private function isiObat(): void
    {
        $nomorObat = (int) Yii::$app->db->createCommand(
            "SELECT COALESCE(MAX(SUBSTRING(kode_obat FROM 5)::int), 0) FROM obat WHERE kode_obat LIKE 'OBT-%'",
        )->queryScalar();

        foreach (self::OBAT_AWAL as [$nama, $kodeKategori, $kodeGolongan, $kodeSatuan, $beli, $jual, $minimum]) {
            $kode = 'OBT-' . str_pad((string) (++$nomorObat), 4, '0', STR_PAD_LEFT);

            if (Obat::find()->where(['kode_obat' => $kode])->exists()) {
                continue;
            }

            $obat = new Obat([
                'kode_obat' => $kode,
                'nama_obat' => $nama,
                'kategori_id' => (string) Kategori::findOne(['kode_kategori' => $kodeKategori])->id,
                'golongan_obat_id' => (string) GolonganObat::findOne(['kode_golongan_obat' => $kodeGolongan])->id,
                'satuan_id' => (string) Satuan::findOne(['kode_satuan' => $kodeSatuan])->id,
                'harga_beli' => $beli,
                'harga_jual' => $jual,
                'stok_minimum' => $minimum,
            ]);

            if (!$obat->save(false)) {
                throw new \RuntimeException("gagal menyimpan obat {$nama}");
            }
        }
    }

    private function isiPasien(): void
    {
        foreach (self::PASIEN_AWAL as [$nama, $jk, $lahir, $nik, $hp, $alamat]) {
            if (Pasien::find()->where(['nik' => $nik])->exists()) {
                continue;
            }

            $noRm = 'RM' . str_pad(
                (string) Yii::$app->db->createCommand('SELECT nextval(\'seq_no_rm\')')->queryScalar(),
                9,
                '0',
                STR_PAD_LEFT,
            );

            $pasien = new Pasien([
                'no_rm' => $noRm,
                'nama' => $nama,
                'jenis_kelamin' => $jk,
                'tanggal_lahir' => $lahir,
                'nik' => $nik,
                'no_hp' => $hp,
                'alamat' => $alamat,
            ]);

            if (!$pasien->save(false)) {
                throw new \RuntimeException("gagal menyimpan pasien {$nama}");
            }
        }
    }
}
