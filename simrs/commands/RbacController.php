<?php

declare(strict_types=1);

namespace app\commands;

use app\models\Menu;
use app\models\Role;
use app\models\RoleAccess;
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
        ['kode' => 'pengaturan', 'parent' => null, 'label' => 'Pengaturan', 'path' => null, 'icon' => 'settings', 'sorter' => 90],
        ['kode' => 'pengaturan.user', 'parent' => 'pengaturan', 'label' => 'User', 'path' => '/setting/user', 'icon' => 'users', 'sorter' => 91],
        ['kode' => 'pengaturan.role', 'parent' => 'pengaturan', 'label' => 'Role', 'path' => '/setting/role', 'icon' => 'shield', 'sorter' => 92],
    ];

    private const MATRIX = [
        'admin' => [
            'dashboard' => ['view'],
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
