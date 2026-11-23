<?php

declare(strict_types=1);

namespace app\modules\api\controllers;

use app\models\Menu;
use app\models\Role;
use app\models\RoleAccess;
use app\models\User;
use app\models\UserAccess;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\UnprocessableEntityHttpException;

class RoleController extends ApiController
{
    protected function verbs(): array
    {
        return [
            'index' => ['GET'],
            'create' => ['POST'],
            'update' => ['PUT'],
            'status' => ['PUT'],
            'access' => ['PUT'],
        ];
    }

    public function actionCreate(): array
    {
        $this->butuh('pengaturan.role', 'create');

        $body = Yii::$app->request->getBodyParams();
        $kode = trim((string) ($body['role_code'] ?? ''));
        $nama = trim((string) ($body['role_name'] ?? ''));

        if (!preg_match('/^[A-Za-z0-9_]{3,32}$/', $kode)) {
            return $this->gagal('Kode role 3 sampai 32 karakter huruf, angka, atau garis bawah', 422);
        }

        if (Role::find()->where(['role_code' => $kode])->exists()) {
            return $this->gagal('Kode role sudah dipakai', 422);
        }

        if ($nama === '' || mb_strlen($nama) > 64) {
            return $this->gagal('Nama role wajib diisi maksimal 64 karakter', 422);
        }

        $role = new Role(['role_code' => $kode, 'role_name' => $nama]);

        if (!$role->save(false)) {
            throw new \RuntimeException("gagal menyimpan role {$kode}");
        }

        return $this->sukses($this->ringkas($role), 201, 'Role ditambahkan.');
    }

    public function actionIndex(): array
    {
        $this->butuh('pengaturan.role', 'view');

        $menus = Menu::find()
            ->where(['is_active' => true])
            ->orderBy(['sorter' => SORT_ASC])
            ->all();

        $namaMenu = [];
        foreach ($menus as $menu) {
            $namaMenu[(string) $menu->id] = $menu->label;
        }

        $daftarMenu = [];
        foreach ($menus as $menu) {
            if ($menu->path === null) {
                continue;
            }

            $daftarMenu[] = [
                'id' => (string) $menu->id,
                'kode' => $menu->kode,
                'label' => $menu->label,
                'parent' => $menu->parent_id === null
                    ? null
                    : ($namaMenu[(string) $menu->parent_id] ?? null),
            ];
        }

        $items = [];
        $matrix = [];
        foreach (Role::find()->orderBy(['role_code' => SORT_ASC])->all() as $role) {
            $items[] = $this->ringkas($role);

            $perMenu = [];
            foreach (RoleAccess::find()->where(['role_id' => $role->id])->all() as $akses) {
                $perMenu[(string) $akses->menu_id] = [
                    'can_view' => (bool) $akses->can_view,
                    'can_create' => (bool) $akses->can_create,
                    'can_update' => (bool) $akses->can_update,
                    'can_delete' => (bool) $akses->can_delete,
                    'can_auth' => (bool) $akses->can_auth,
                ];
            }

            $matrix[(string) $role->id] = $perMenu;
        }

        return $this->sukses([
            'items' => $items,
            'menus' => $daftarMenu,
            'matrix' => $matrix,
        ]);
    }

    public function actionUpdate(string $id): array
    {
        $this->butuh('pengaturan.role', 'update');

        $role = $this->cari($id);
        $nama = trim((string) (Yii::$app->request->getBodyParams()['role_name'] ?? ''));

        if ($nama === '' || mb_strlen($nama) > 64) {
            return $this->gagal('Nama role wajib diisi maksimal 64 karakter', 422);
        }

        $role->role_name = $nama;
        $role->save(false);

        return $this->sukses($this->ringkas($role), 200, 'Role diubah.');
    }

    public function actionStatus(string $id): array
    {
        $this->butuh('pengaturan.role', 'update');

        $role = $this->cari($id);
        $aktif = filter_var(
            Yii::$app->request->getBodyParams()['is_active'] ?? null,
            FILTER_VALIDATE_BOOL,
            FILTER_NULL_ON_FAILURE,
        );

        if ($aktif === null) {
            return $this->gagal('Status tidak valid', 422);
        }

        if (!$aktif && $role->id === Yii::$app->user->identity->role_id) {
            return $this->gagal('Role yang sedang dipakai tidak bisa dinonaktifkan', 422);
        }

        $role->is_active = $aktif;
        $role->save(false);

        return $this->sukses($this->ringkas($role), 200, 'Status role diubah.');
    }

    public function actionAccess(string $id): array
    {
        $this->butuh('pengaturan.role', 'update');

        $role = $this->cari($id);
        $access = Yii::$app->request->getBodyParams()['access'] ?? null;

        if (!is_array($access)) {
            return $this->gagal('Data access tidak valid', 422);
        }

        $baris = [];
        foreach ($access as $menuId => $hak) {
            $menu = Menu::find()
                ->where(['id' => (string) $menuId, 'is_active' => true])
                ->one();

            if ($menu === null || $menu->path === null) {
                return $this->gagal('Menu tidak ditemukan', 422);
            }

            $baris[$menu->id] = [
                'can_view' => (bool) ($hak['can_view'] ?? false),
                'can_create' => (bool) ($hak['can_create'] ?? false),
                'can_update' => (bool) ($hak['can_update'] ?? false),
                'can_delete' => (bool) ($hak['can_delete'] ?? false),
                'can_auth' => (bool) ($hak['can_auth'] ?? false),
            ];
        }

        $transaksi = Yii::$app->db->beginTransaction();
        try {
            RoleAccess::deleteAll(['role_id' => $role->id]);

            foreach ($baris as $menuId => $hak) {
                if (!in_array(true, $hak, true)) {
                    continue;
                }

                $akses = new RoleAccess(['role_id' => $role->id, 'menu_id' => $menuId]);
                foreach ($hak as $aksi => $nilai) {
                    $akses->setAttribute($aksi, $nilai);
                }

                if (!$akses->save(false)) {
                    throw new \RuntimeException("gagal menyimpan akses {$role->role_code} -> {$menuId}");
                }
            }

            $pengguna = User::find()
                ->where(['role_id' => $role->id, 'is_delete' => false])
                ->all();
            foreach ($pengguna as $user) {
                UserAccess::sinkron($user);
            }

            $transaksi->commit();
        } catch (\Throwable $e) {
            $transaksi->rollBack();

            throw $e;
        }

        return $this->sukses($this->ringkas($role), 200, 'Access role diubah.');
    }

    private function cari(string $id): Role
    {
        $role = Role::findOne($id);

        if ($role === null) {
            throw new NotFoundHttpException('Role tidak ditemukan');
        }

        return $role;
    }

    private function ringkas(Role $role): array
    {
        return [
            'id' => (string) $role->id,
            'code' => $role->role_code,
            'name' => $role->role_name,
            'is_active' => (bool) $role->is_active,
            'user_count' => (int) User::find()
                ->where(['role_id' => $role->id, 'is_delete' => false])
                ->count(),
        ];
    }
}
