<?php

declare(strict_types=1);

namespace app\commands;

use app\models\Role;
use app\models\User;
use app\models\UserAccess;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

class SeedController extends Controller
{
    private const PASSWORD_ADMIN = 'admin123';

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
}
