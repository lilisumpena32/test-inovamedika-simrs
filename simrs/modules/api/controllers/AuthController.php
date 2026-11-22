<?php

declare(strict_types=1);

namespace app\modules\api\controllers;

use app\components\Jwt;
use app\models\User;
use Yii;

class AuthController extends ApiController
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['jwtAuth']['except'] = ['login'];

        return $behaviors;
    }

    protected function verbs(): array
    {
        return [
            'login' => ['POST'],
            'me' => ['GET'],
        ];
    }

    public function actionLogin(): array
    {
        $body = Yii::$app->request->bodyParams;
        $username = trim((string) ($body['username'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        if ($username === '' || $password === '') {
            return $this->gagal('Username dan password wajib diisi', 422);
        }

        $user = User::find()
            ->where(['username' => $username, 'is_delete' => false])
            ->one();

        if ($user === null || !$user->validatePassword($password)) {
            return $this->gagal('Username atau password salah', 401);
        }

        if (!$user->is_active) {
            return $this->gagal('Akun tidak aktif', 403);
        }

        $role = $user->role;
        if ($role === null || !$role->is_active) {
            return $this->gagal('Role tidak aktif', 403);
        }

        Yii::$app->user->setIdentity($user);

        return $this->sukses([
            'token' => Jwt::terbitkan($user),
            'user' => $this->profil($user),
            'menus' => Yii::$app->access->menu(),
        ]);
    }

    public function actionMe(): array
    {
        $user = Yii::$app->user->identity;

        if (!$user instanceof User) {
            return $this->gagal('Token tidak valid', 401);
        }

        return $this->sukses([
            'user' => $this->profil($user),
            'menus' => Yii::$app->access->menu(),
        ]);
    }

    private function profil(User $user): array
    {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'role' => [
                'code' => $user->role->role_code,
                'name' => $user->role->role_name,
            ],
        ];
    }
}
