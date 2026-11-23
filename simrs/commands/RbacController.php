<?php

declare(strict_types=1);

namespace app\commands;

use app\models\Jabatan;
use app\models\Menu;
use app\models\Role;
use app\models\RoleAccess;
use app\models\Spesialis;
use app\models\User;
use app\models\UserAccess;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Transaction;

class RbacController extends Controller
{
    private const ROLES = [
        'admin' => 'Administrator',
        'pendaftaran' => 'Pendaftaran',
        'dokter' => 'Dokter',
        'farmasi' => 'Farmasi',
        'laboratorium' => 'Laboratorium',
        'kasir' => 'Kasir',
    ];

    private const MENUS = [
        ['kode' => 'dashboard', 'parent' => null, 'label' => 'Dashboard', 'path' => '/', 'icon' => 'layout-dashboard', 'sorter' => 10],
        ['kode' => 'master', 'parent' => null, 'label' => 'Master', 'path' => null, 'icon' => 'folder', 'sorter' => 20],
        ['kode' => 'master.pasien', 'parent' => 'master', 'label' => 'Pasien', 'path' => '/master/pasien', 'icon' => 'user-round', 'sorter' => 21],
        ['kode' => 'master.poli', 'parent' => 'master', 'label' => 'Poli', 'path' => '/master/poli', 'icon' => 'map-pin', 'sorter' => 22],
        ['kode' => 'master.karyawan', 'parent' => 'master', 'label' => 'Karyawan', 'path' => '/master/karyawan', 'icon' => 'briefcase', 'sorter' => 23],
        ['kode' => 'master.jadwal', 'parent' => 'master', 'label' => 'Jadwal', 'path' => '/master/jadwal', 'icon' => 'calendar-days', 'sorter' => 24],
        ['kode' => 'farmasi', 'parent' => null, 'label' => 'Farmasi', 'path' => null, 'icon' => 'pill', 'sorter' => 30],
        ['kode' => 'farmasi.obat', 'parent' => 'farmasi', 'label' => 'Obat', 'path' => '/farmasi/obat', 'icon' => 'pill', 'sorter' => 31],
        ['kode' => 'pengaturan', 'parent' => null, 'label' => 'Pengaturan', 'path' => null, 'icon' => 'settings', 'sorter' => 90],
        ['kode' => 'pengaturan.user', 'parent' => 'pengaturan', 'label' => 'User', 'path' => '/setting/user', 'icon' => 'users', 'sorter' => 91],
        ['kode' => 'pengaturan.role', 'parent' => 'pengaturan', 'label' => 'Role', 'path' => '/setting/role', 'icon' => 'shield', 'sorter' => 92],
    ];

    private const MATRIX = [
        'admin' => [
            'dashboard' => ['view'],
            'master.pasien' => ['view', 'create', 'update'],
            'master.poli' => ['view', 'create', 'update'],
            'master.karyawan' => ['view', 'create', 'update'],
            'master.jadwal' => ['view', 'create', 'update'],
            'farmasi.obat' => ['view', 'create', 'update'],
            'pengaturan.user' => ['view', 'create', 'update', 'delete'],
            'pengaturan.role' => ['view', 'create', 'update'],
        ],
        'pendaftaran' => [
            'dashboard' => ['view'],
        ],
        'dokter' => [
            'dashboard' => ['view'],
        ],
        'farmasi' => [
            'dashboard' => ['view'],
        ],
        'laboratorium' => [
            'dashboard' => ['view'],
        ],
        'kasir' => [
            'dashboard' => ['view'],
        ],
    ];

    private const HAK = ['view', 'create', 'update', 'delete', 'auth'];

    public function actionInit(): int
    {
        /** @var Transaction $transaksi */
        $transaksi = Yii::$app->db->beginTransaction();

        try {
            $role = $this->simpanRole();
            $menu = $this->simpanMenu();
            $this->simpanRoleAccess($role, $menu);
            $this->simpanJabatanSpesialis();
            $userAccessBaru = $this->sinkronUserAccess();

            $transaksi->commit();
        } catch (\Throwable $e) {
            $transaksi->rollBack();
            $this->stderr("Gagal: {$e->getMessage()}\n");

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout(
            'Role: ' . count($role) . ', menu: ' . count($menu) . ", user_access baru: {$userAccessBaru}\n"
        );

        return ExitCode::OK;
    }

    private const JABATAN_AWAL = [
        ['dokter', 'Dokter', true],
        ['perawat', 'Perawat', false],
        ['apoteker', 'Apoteker', false],
        ['kasir', 'Kasir', false],
        ['pendaftaran', 'Pendaftaran', false],
    ];

    private const SPESIALIS_AWAL = [
        'umum' => 'Umum',
        'anak' => 'Anak',
        'bedah' => 'Bedah',
        'saraf' => 'Saraf',
        'jantung' => 'Jantung',
    ];

    private function simpanJabatanSpesialis(): void
    {
        foreach (self::JABATAN_AWAL as [$kode, $nama, $isDokter]) {
            $jabatan = Jabatan::find()->where(['kode_jabatan' => $kode])->one();

            if ($jabatan !== null) {
                continue;
            }

            $jabatan = new Jabatan([
                'kode_jabatan' => $kode,
                'nama_jabatan' => $nama,
                'is_dokter' => $isDokter,
            ]);

            if (!$jabatan->save(false)) {
                throw new \RuntimeException("gagal menyimpan jabatan {$kode}");
            }
        }

        foreach (self::SPESIALIS_AWAL as $kode => $nama) {
            $spesialis = Spesialis::find()->where(['kode_spesialis' => $kode])->one();

            if ($spesialis !== null) {
                continue;
            }

            $spesialis = new Spesialis(['kode_spesialis' => $kode, 'nama_spesialis' => $nama]);

            if (!$spesialis->save(false)) {
                throw new \RuntimeException("gagal menyimpan spesialis {$kode}");
            }
        }
    }

    private function simpanRole(): array
    {
        $hasil = [];

        foreach (self::ROLES as $kode => $nama) {
            $role = Role::findOne(['role_code' => $kode]) ?? new Role(['role_code' => $kode]);
            $role->role_name = $nama;
            $role->is_active = true;

            if (!$role->save(false)) {
                throw new \RuntimeException("gagal menyimpan role {$kode}");
            }

            $hasil[$kode] = (string) $role->id;
        }

        return $hasil;
    }

    private function simpanMenu(): array
    {
        $hasil = [];

        foreach (self::MENUS as $item) {
            $menu = Menu::findOne(['kode' => $item['kode']]) ?? new Menu(['kode' => $item['kode']]);
            $menu->parent_id = $item['parent'] === null ? null : $hasil[$item['parent']];
            $menu->label = $item['label'];
            $menu->path = $item['path'];
            $menu->icon = $item['icon'];
            $menu->sorter = $item['sorter'];
            $menu->is_active = true;

            if (!$menu->save(false)) {
                throw new \RuntimeException("gagal menyimpan menu {$item['kode']}");
            }

            $hasil[$item['kode']] = (string) $menu->id;
        }

        return $hasil;
    }

    private function simpanRoleAccess(array $role, array $menu): void
    {
        foreach (self::MATRIX as $kodeRole => $daftar) {
            foreach ($daftar as $kodeMenu => $hak) {
                $akses = RoleAccess::find()
                    ->where(['role_id' => $role[$kodeRole], 'menu_id' => $menu[$kodeMenu]])
                    ->one();

                if ($akses !== null) {
                    continue;
                }

                $akses = new RoleAccess([
                    'role_id' => $role[$kodeRole],
                    'menu_id' => $menu[$kodeMenu],
                ]);

                foreach (self::HAK as $aksi) {
                    $akses->setAttribute('can_' . $aksi, in_array($aksi, $hak, true));
                }

                if (!$akses->save(false)) {
                    throw new \RuntimeException("gagal menyimpan akses {$kodeRole} -> {$kodeMenu}");
                }
            }
        }
    }

    private function sinkronUserAccess(): int
    {
        $baru = 0;

        foreach (User::find()->where(['is_delete' => false])->all() as $user) {
            $baru += UserAccess::sinkron($user);
        }

        return $baru;
    }
}
