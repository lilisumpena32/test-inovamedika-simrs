<?php

declare(strict_types=1);

namespace app\modules\api\controllers;

use app\models\Karyawan;
use app\models\Role;
use app\models\User;
use app\models\UserAccess;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\UnprocessableEntityHttpException;

class UserController extends ApiController
{
    protected function verbs(): array
    {
        return [
            'index' => ['GET'],
            'view' => ['GET'],
            'create' => ['POST'],
            'update' => ['PUT'],
            'status' => ['PUT'],
            'delete' => ['DELETE'],
            'password' => ['PUT'],
        ];
    }

    public function actionIndex(): array
    {
        $this->butuh('pengaturan.user', 'view');

        $request = Yii::$app->request;
        $q = trim((string) $request->get('q', ''));
        $kodeRole = trim((string) $request->get('role', ''));
        $aktif = (string) $request->get('is_active', '');
        $tanpaKaryawan = (string) $request->get('tanpa_karyawan', '');
        $halaman = max(1, (int) $request->get('page', '1'));
        $perHalaman = min(50, max(1, (int) $request->get('per', '15')));

        $query = User::find()
            ->from(['u' => '{{%user}}'])
            ->innerJoin(['r' => '{{%role}}'], 'r.id = u.role_id')
            ->where(['u.is_delete' => false]);

        if ($tanpaKaryawan === '1' || $tanpaKaryawan === 'true') {
            $query->andWhere([
                'not in',
                'u.id',
                Karyawan::find()->select('user_id')->where(['not', ['user_id' => null]]),
            ]);
        }

        if ($q !== '') {
            $query->andWhere(['or', ['like', 'u.username', $q], ['like', 'u.full_name', $q]]);
        }

        if ($kodeRole !== '') {
            $query->andWhere(['r.role_code' => $kodeRole]);
        }

        if ($aktif === 'true' || $aktif === 'false') {
            $query->andWhere(['u.is_active' => $aktif === 'true']);
        }

        $total = (int) (clone $query)->count();
        $baris = $query
            ->with('role')
            ->orderBy(['u.username' => SORT_ASC])
            ->offset(($halaman - 1) * $perHalaman)
            ->limit($perHalaman)
            ->all();

        return $this->sukses([
            'items' => array_map(fn (User $user) => $this->ringkas($user), $baris),
            'total' => $total,
            'page' => $halaman,
            'per' => $perHalaman,
        ]);
    }

    public function actionView(string $id): array
    {
        $this->butuh('pengaturan.user', 'view');

        return $this->sukses($this->ringkas($this->cari($id)));
    }

    public function actionCreate(): array
    {
        $this->butuh('pengaturan.user', 'create');

        $body = Yii::$app->request->getBodyParams();

        $user = new User();
        $user->username = trim((string) ($body['username'] ?? ''));
        $user->full_name = trim((string) ($body['full_name'] ?? ''));
        $user->email = $this->email($body);
        $user->role_id = trim((string) ($body['role_id'] ?? ''));

        $galat = $this->validasi($user, (string) ($body['password'] ?? ''), true);
        if ($galat !== null) {
            return $this->gagal($galat, 422);
        }

        $transaksi = Yii::$app->db->beginTransaction();
        try {
            $user->password_hash = Yii::$app->security->generatePasswordHash((string) $body['password']);
            if (!$user->save(false)) {
                throw new \RuntimeException('gagal menyimpan user');
            }

            UserAccess::sinkron($user);
            $transaksi->commit();
        } catch (\Throwable $e) {
            $transaksi->rollBack();

            throw $e;
        }

        return $this->sukses($this->ringkas($user), 201, 'User ditambahkan.');
    }

    public function actionUpdate(string $id): array
    {
        $this->butuh('pengaturan.user', 'update');

        $user = $this->cari($id);
        $body = Yii::$app->request->getBodyParams();

        $user->full_name = trim((string) ($body['full_name'] ?? ''));
        $user->email = $this->email($body);
        $user->role_id = trim((string) ($body['role_id'] ?? ''));

        $galat = $this->validasi($user, '', false, $user->id);
        if ($galat !== null) {
            return $this->gagal($galat, 422);
        }

        $roleLama = (string) $user->getOldAttribute('role_id');

        $transaksi = Yii::$app->db->beginTransaction();
        try {
            if (!$user->save(false)) {
                throw new \RuntimeException('gagal menyimpan user');
            }

            if ($user->role_id !== $roleLama) {
                UserAccess::deleteAll(['user_id' => $user->id]);
                UserAccess::sinkron($user);
            }

            $transaksi->commit();
        } catch (\Throwable $e) {
            $transaksi->rollBack();

            throw $e;
        }

        return $this->sukses($this->ringkas($user), 200, 'User diubah.');
    }

    public function actionStatus(string $id): array
    {
        $this->butuh('pengaturan.user', 'update');

        $user = $this->cari($id);
        $aktif = filter_var(
            Yii::$app->request->getBodyParams()['is_active'] ?? null,
            FILTER_VALIDATE_BOOL,
            FILTER_NULL_ON_FAILURE,
        );

        if ($aktif === null) {
            return $this->gagal('Status tidak valid', 422);
        }

        if (!$aktif && $user->id === (string) Yii::$app->user->getId()) {
            return $this->gagal('Akun yang sedang login tidak bisa dinonaktifkan', 422);
        }

        $user->is_active = $aktif;
        $user->save(false);

        return $this->sukses($this->ringkas($user), 200, 'Status user diubah.');
    }

    public function actionDelete(string $id): array
    {
        $this->butuh('pengaturan.user', 'delete');

        $user = $this->cari($id);

        if ($user->id === (string) Yii::$app->user->getId()) {
            return $this->gagal('Akun yang sedang login tidak bisa dihapus', 422);
        }

        $user->is_delete = true;
        $user->is_active = false;
        $user->save(false);

        return $this->sukses(['id' => (string) $user->id], 200, 'User dihapus.');
    }

    public function actionPassword(string $id): array
    {
        $this->butuh('pengaturan.user', 'update');

        $user = $this->cari($id);
        $password = (string) (Yii::$app->request->getBodyParams()['password'] ?? '');

        if (strlen($password) < 8) {
            return $this->gagal('Password minimal 8 karakter', 422);
        }

        $user->password_hash = Yii::$app->security->generatePasswordHash($password);
        $user->save(false);

        return $this->sukses($this->ringkas($user), 200, 'Password diganti.');
    }

    private function cari(string $id): User
    {
        $user = User::find()
            ->where(['id' => $id, 'is_delete' => false])
            ->one();

        if ($user === null) {
            throw new NotFoundHttpException('User tidak ditemukan');
        }

        return $user;
    }

    private function ringkas(User $user): array
    {
        $role = $user->role;

        return [
            'id' => (string) $user->id,
            'username' => $user->username,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'is_active' => (bool) $user->is_active,
            'create_date' => $user->create_date,
            'role' => [
                'id' => (string) $role->id,
                'code' => $role->role_code,
                'name' => $role->role_name,
            ],
        ];
    }

    private function email(array $body): ?string
    {
        $email = trim((string) ($body['email'] ?? ''));

        return $email === '' ? null : $email;
    }

    private function validasi(User $user, string $password, bool $wajibPassword, ?string $abaikanId = null): ?string
    {
        if (!preg_match('/^[A-Za-z0-9_.]{3,64}$/', $user->username)) {
            return 'Username 3 sampai 64 karakter huruf, angka, titik, atau garis bawah';
        }

        $dipakai = User::find()
            ->where(['username' => $user->username, 'is_delete' => false])
            ->andFilterWhere(['!=', 'id', $abaikanId])
            ->exists();

        if ($dipakai) {
            return 'Username sudah dipakai';
        }

        if ($user->full_name === '' || mb_strlen($user->full_name) > 120) {
            return 'Nama lengkap wajib diisi maksimal 120 karakter';
        }

        if ($user->email !== null
            && (!filter_var($user->email, FILTER_VALIDATE_EMAIL) || mb_strlen($user->email) > 128)
        ) {
            return 'Email tidak valid';
        }

        $role = $user->role_id === '' ? null : Role::findOne($user->role_id);
        if ($role === null || !$role->is_active) {
            return 'Role tidak ditemukan atau tidak aktif';
        }

        if ($wajibPassword && strlen($password) < 8) {
            return 'Password minimal 8 karakter';
        }

        return null;
    }
}

