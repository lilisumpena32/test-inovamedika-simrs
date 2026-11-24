<?php

declare(strict_types=1);

namespace app\commands;

use app\models\GolonganObat;
use app\models\Jabatan;
use app\models\JadwalPraktek;
use app\models\JenisLab;
use app\models\Kategori;
use app\models\Karyawan;
use app\models\Kunjungan;
use app\models\MutasiObat;
use app\models\Obat;
use app\models\OrderLab;
use app\models\Pasien;
use app\models\Pemeriksaan;
use app\models\PemeriksaanTindakan;
use app\models\Poli;
use app\models\Resep;
use app\models\ResepObat;
use app\models\Role;
use app\models\Satuan;
use app\models\Spesialis;
use app\models\StokObat;
use app\models\Tindakan;
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
        // [nama, biaya daftar, initial antrean]
        ['Umum', 30000, 'UMM'],
        ['Gigi', 45000, 'GGI'],
        ['Anak', 40000, 'ANK'],
        ['Kandungan', 60000, 'KDG'],
        ['THT', 50000, 'THT'],
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
        ['dr. Hendra Wijaya', 'L', '1988-05-09', 'dokter', 'gigi', null, '2017-04-01'],
        ['dr. Ratna Puspita', 'P', '1991-12-19', 'dokter', 'kandungan', null, '2019-09-01'],
        ['dr. Fajar Nugroho', 'L', '1993-08-30', 'dokter', 'tht', null, '2021-05-01'],
        ['Rina Wulandari', 'P', '1994-11-02', 'perawat', null, null, '2019-06-01'],
        ['Maya Putri', 'P', '1993-02-17', 'apoteker', null, 'maya', '2019-08-01'],
        ['Budi Santoso', 'L', '1991-09-09', 'kasir', null, 'budi', '2021-01-01'],
        ['Sari Dewi', 'P', '1996-04-25', 'pendaftaran', null, 'sari', '2022-03-01'],
    ];

    private const JADWAL_AWAL = [
        // [nama karyawan, kode poli, hari (1=Senin), jam mulai, jam selesai, kuota]
        ['dr. Andi Pratama', 'POL-0001', 1, '08:00', '12:00', 20],
        ['dr. Andi Pratama', 'POL-0001', 2, '08:00', '12:00', 20],
        ['dr. Andi Pratama', 'POL-0001', 3, '08:00', '12:00', 20],
        ['dr. Andi Pratama', 'POL-0001', 3, '13:00', '16:00', 15],
        ['dr. Andi Pratama', 'POL-0001', 4, '08:00', '12:00', 20],
        ['dr. Andi Pratama', 'POL-0001', 5, '08:00', '12:00', 20],
        ['dr. Hendra Wijaya', 'POL-0002', 2, '08:00', '11:00', 15],
        ['dr. Hendra Wijaya', 'POL-0002', 2, '13:00', '16:00', 12],
        ['dr. Hendra Wijaya', 'POL-0002', 4, '08:00', '11:00', 15],
        ['dr. Hendra Wijaya', 'POL-0002', 6, '08:00', '12:00', 10],
        ['dr. Sinta Maharani', 'POL-0003', 1, '08:00', '12:00', 15],
        ['dr. Sinta Maharani', 'POL-0003', 2, '13:00', '16:00', 12],
        ['dr. Sinta Maharani', 'POL-0003', 3, '08:00', '12:00', 15],
        ['dr. Sinta Maharani', 'POL-0003', 4, '13:00', '16:00', 12],
        ['dr. Sinta Maharani', 'POL-0003', 5, '08:00', '12:00', 15],
        ['dr. Ratna Puspita', 'POL-0004', 1, '13:00', '16:00', 10],
        ['dr. Ratna Puspita', 'POL-0004', 3, '13:00', '16:00', 10],
        ['dr. Ratna Puspita', 'POL-0004', 5, '08:00', '12:00', 10],
        ['dr. Fajar Nugroho', 'POL-0005', 2, '08:00', '12:00', 12],
        ['dr. Fajar Nugroho', 'POL-0005', 4, '08:00', '12:00', 12],
        ['dr. Fajar Nugroho', 'POL-0005', 6, '09:00', '13:00', 10],
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

    private const TINDAKAN_AWAL = [
        // [nama, tarif]
        ['Konsultasi poliklinik', 50000],
        ['Injeksi intramuskular', 15000],
        ['Perawatan luka', 30000],
        ['Nebulisasi', 40000],
        ['Pemasangan infus', 60000],
        ['Elektrokardiografi', 80000],
    ];

    private const JENIS_LAB_AWAL = [
        ['Darah lengkap', 90000],
        ['Urine rutin', 40000],
        ['Gula darah sewaktu', 35000],
        ['Fungsi hati', 120000],
    ];

    private const KUNJUNGAN_DEMO = [
        // [nama pasien, nama dokter, kode poli, status kunjungan]
        ['Agus Setiawan', 'dr. Andi Pratama', 'POL-0001', 'menunggu'],
        ['Bunga Lestari', 'dr. Sinta Maharani', 'POL-0003', 'menunggu'],
        ['Candra Wijaya', 'dr. Andi Pratama', 'POL-0001', 'diperiksa'],
        ['Dewi Anggraini', 'dr. Andi Pratama', 'POL-0001', 'menunggu_obat'],
        ['Eko Prasetyo', 'dr. Hendra Wijaya', 'POL-0002', 'menunggu_bayar'],
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
            $this->isiJadwal();
            $this->isiReferensiObat();
            $this->isiObat();
            $this->isiPasien();
            $this->isiTindakan();
            $this->isiJenisLab();
            $this->isiStok();
            $this->isiKunjunganDemo();

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
        foreach (self::POLI_AWAL as $i => [$nama, $biaya, $initial]) {
            $kode = 'POL-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT);

            $poli = Poli::findOne(['kode' => $kode]);

            if ($poli !== null) {
                // Poli lama dari sebelum ada kolom initial tetap diisi supaya mandatory terpenuhi.
                if ((string) $poli->initial === '') {
                    $poli->initial = $initial;
                    $poli->save(false);
                }

                continue;
            }

            $poli = new Poli([
                'kode' => $kode,
                'nama' => $nama,
                'biaya_daftar' => $biaya,
                'initial' => $initial,
            ]);

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

    private function isiJadwal(): void
    {
        foreach (self::JADWAL_AWAL as [$namaKaryawan, $kodePoli, $hari, $jamMulai, $jamSelesai, $kuota]) {
            $karyawan = Karyawan::findOne(['nama_karyawan' => $namaKaryawan]);
            $poli = Poli::findOne(['kode' => $kodePoli]);

            if ($karyawan === null || $poli === null) {
                throw new \RuntimeException("data jadwal belum lengkap untuk {$namaKaryawan} / {$kodePoli}");
            }

            $sudah = JadwalPraktek::find()
                ->where([
                    'karyawan_id' => $karyawan->id,
                    'poli_id' => $poli->id,
                    'hari' => $hari,
                    'jam_mulai' => $jamMulai,
                ])
                ->exists();

            if ($sudah) {
                continue;
            }

            $jadwal = new JadwalPraktek([
                'karyawan_id' => (string) $karyawan->id,
                'poli_id' => (string) $poli->id,
                'hari' => $hari,
                'jam_mulai' => $jamMulai,
                'jam_selesai' => $jamSelesai,
                'kuota' => $kuota,
            ]);
            $jadwal->is_active = true;

            if (!$jadwal->save(false)) {
                throw new \RuntimeException("gagal menyimpan jadwal {$namaKaryawan} / {$kodePoli} / hari {$hari}");
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
            if (Obat::find()->where(['nama_obat' => $nama])->exists()) {
                continue;
            }

            $nomorObat++;
            $kode = 'OBT-' . str_pad((string) $nomorObat, 4, '0', STR_PAD_LEFT);

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

    private function isiTindakan(): void
    {
        $nomor = (int) Yii::$app->db->createCommand(
            "SELECT COALESCE(MAX(SUBSTRING(kode_tindakan FROM 5)::int), 0) FROM tindakan WHERE kode_tindakan LIKE 'TDN-%'",
        )->queryScalar();

        foreach (self::TINDAKAN_AWAL as [$nama, $tarif]) {
            if (Tindakan::find()->where(['nama' => $nama])->exists()) {
                continue;
            }

            $nomor++;
            $tindakan = new Tindakan([
                'kode_tindakan' => 'TDN-' . str_pad((string) $nomor, 4, '0', STR_PAD_LEFT),
                'nama' => $nama,
                'tarif' => $tarif,
                'is_active' => true,
            ]);

            if (!$tindakan->save(false)) {
                throw new \RuntimeException("gagal menyimpan tindakan {$nama}");
            }
        }
    }

    private function isiJenisLab(): void
    {
        $nomor = (int) Yii::$app->db->createCommand(
            "SELECT COALESCE(MAX(SUBSTRING(kode_jenis_lab FROM 4)::int), 0) FROM jenis_lab WHERE kode_jenis_lab LIKE 'LB-%'",
        )->queryScalar();

        foreach (self::JENIS_LAB_AWAL as [$nama, $tarif]) {
            if (JenisLab::find()->where(['nama' => $nama])->exists()) {
                continue;
            }

            $nomor++;
            $jenisLab = new JenisLab([
                'kode_jenis_lab' => 'LB-' . str_pad((string) $nomor, 4, '0', STR_PAD_LEFT),
                'nama' => $nama,
                'tarif' => $tarif,
                'is_active' => true,
            ]);

            if (!$jenisLab->save(false)) {
                throw new \RuntimeException("gagal menyimpan jenis lab {$nama}");
            }
        }
    }

    private function isiStok(): void
    {
        $obats = Obat::find()->orderBy('kode_obat')->all();

        foreach ($obats as $obat) {
            if (StokObat::find()->where(['obat_id' => $obat->id])->exists()) {
                continue;
            }

            // Dua batch: yang kadaluarsanya lebih dekat jumlahnya lebih kecil,
            // dipakai untuk membuktikan alokasi FEFO saat penyerahan resep.
            $batch = [
                ['B-001', date('Y-m-d', strtotime('+6 months')), 30],
                ['B-002', date('Y-m-d', strtotime('+18 months')), 120],
            ];

            foreach ($batch as [$noBatch, $kadaluarsa, $jumlah]) {
                $stok = new StokObat([
                    'obat_id' => (string) $obat->id,
                    'no_batch' => $noBatch,
                    'kadaluarsa' => $kadaluarsa,
                    'jumlah' => $jumlah,
                ]);

                if (!$stok->save(false)) {
                    throw new \RuntimeException("gagal menyimpan stok batch {$noBatch} untuk {$obat->nama_obat}");
                }

                $mutasi = new MutasiObat([
                    'obat_id' => (string) $obat->id,
                    'stok_obat_id' => (string) $stok->id,
                    'jenis' => MutasiObat::JENIS_MASUK,
                    'jumlah' => $jumlah,
                    'referensi' => 'seed',
                ]);

                if (!$mutasi->save(false)) {
                    throw new \RuntimeException("gagal menyimpan mutasi masuk untuk {$obat->nama_obat}");
                }
            }
        }
    }

    private function isiKunjunganDemo(): void
    {
        $tanggal = date('Y-m-d');
        $hari = (int) date('N');

        foreach (self::KUNJUNGAN_DEMO as [$namaPasien, $namaDokter, $kodePoli, $status]) {
            $pasien = Pasien::findOne(['nama' => $namaPasien]);
            $dokter = Karyawan::findOne(['nama_karyawan' => $namaDokter]);
            $poli = Poli::findOne(['kode' => $kodePoli]);
            $jadwal = $dokter === null || $poli === null
                ? null
                : JadwalPraktek::find()
                    ->where([
                        'karyawan_id' => $dokter->id,
                        'poli_id' => $poli->id,
                        'hari' => $hari,
                        'is_active' => true,
                    ])
                    ->one();

            if ($pasien === null || $jadwal === null) {
                // Hari ini bukan hari praktek dokter ini (mis. Minggu) — entri dilewati.
                continue;
            }

            $kunci = 'seed-kunjungan-' . strtolower(str_replace(' ', '-', $namaPasien));

            if (Kunjungan::find()->where(['idempotency_key' => $kunci])->exists()) {
                continue;
            }

            $kunjungan = new Kunjungan([
                'pasien_id' => (string) $pasien->id,
                'dokter_id' => (string) $dokter->id,
                'poli_id' => (string) $poli->id,
                'jadwal_id' => (string) $jadwal->id,
                'tgl_kunjungan' => $tanggal,
                'status' => $status,
                'biaya_daftar' => (float) $poli->biaya_daftar,
                'idempotency_key' => $kunci,
            ]);

            $kunjungan->no_antrean = (int) Yii::$app->db->createCommand(
                'INSERT INTO counter_antrean (poli_id, tanggal, nomor_terakhir)
                 VALUES (:poli, :tanggal, 1)
                 ON CONFLICT (poli_id, tanggal)
                 DO UPDATE SET nomor_terakhir = counter_antrean.nomor_terakhir + 1,
                               update_date = (now() AT TIME ZONE \'Asia/Jakarta\')
                 RETURNING nomor_terakhir',
                [':poli' => (string) $poli->id, ':tanggal' => $tanggal],
            )->queryScalar();

            $urut = (int) Yii::$app->db->createCommand('SELECT nextval(\'seq_kunjungan\')')->queryScalar();
            $kunjungan->no_kunjungan = sprintf(
                'KJ-%s-%04d',
                str_replace('-', '', $tanggal),
                $urut,
            );

            if (!$kunjungan->save(false)) {
                throw new \RuntimeException("gagal menyimpan kunjungan demo {$namaPasien}");
            }

            $idKunjungan = (string) $kunjungan->id;

            if (in_array($status, ['diperiksa', 'menunggu_obat', 'menunggu_bayar'], true)) {
                $pemeriksaan = new Pemeriksaan([
                    'kunjungan_id' => $idKunjungan,
                    'keluhan' => 'Demam dan nyeri tubuh',
                    'anamnesis' => 'Demam sejak 3 hari, batuk tidak berdahak',
                    'tensi' => '120/80',
                    'suhu' => 37.8,
                    'nadi' => 92,
                    'pernapasan' => 20,
                    'hasil_fisik' => 'Kondisi umum cukup, tenggorokan kemerahan',
                    'diagnosis_utama' => 'Infeksi virus non spesifik',
                ]);

                if (!$pemeriksaan->save(false)) {
                    throw new \RuntimeException("gagal menyimpan pemeriksaan demo {$namaPasien}");
                }
            }

            if ($status === 'diperiksa') {
                $jenisLab = JenisLab::findOne(['nama' => 'Darah lengkap']);

                if ($jenisLab !== null) {
                    $order = new OrderLab([
                        'kunjungan_id' => $idKunjungan,
                        'jenis_lab_id' => (string) $jenisLab->id,
                        'tarif' => (float) $jenisLab->tarif,
                        'status' => OrderLab::STATUS_DIMINTA,
                    ]);

                    if (!$order->save(false)) {
                        throw new \RuntimeException("gagal menyimpan order lab demo {$namaPasien}");
                    }
                }
            }

            if ($status === 'menunggu_obat') {
                $obat = Obat::findOne(['nama_obat' => 'Paracetamol 500 mg']);

                if ($obat !== null) {
                    $resep = new Resep([
                        'kunjungan_id' => $idKunjungan,
                        'status' => Resep::STATUS_MENUNGGU,
                    ]);

                    if (!$resep->save(false)) {
                        throw new \RuntimeException("gagal menyimpan resep demo {$namaPasien}");
                    }

                    $item = new ResepObat([
                        'resep_id' => (string) $resep->id,
                        'obat_id' => (string) $obat->id,
                        'dosis' => '1 tablet',
                        'frekuensi' => '3x sehari',
                        'jumlah' => 9,
                    ]);

                    if (!$item->save(false)) {
                        throw new \RuntimeException("gagal menyimpan item resep demo {$namaPasien}");
                    }
                }
            }

            if ($status === 'menunggu_bayar') {
                $tindakan = Tindakan::findOne(['nama' => 'Perawatan luka']);

                if ($tindakan !== null) {
                    $detail = new PemeriksaanTindakan([
                        'kunjungan_id' => $idKunjungan,
                        'tindakan_id' => (string) $tindakan->id,
                        'tarif' => (float) $tindakan->tarif,
                    ]);

                    if (!$detail->save(false)) {
                        throw new \RuntimeException("gagal menyimpan tindakan demo {$namaPasien}");
                    }
                }
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
