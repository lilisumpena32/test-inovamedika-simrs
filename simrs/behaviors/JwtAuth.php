<?php

declare(strict_types=1);

namespace app\behaviors;

use app\components\Jwt;
use app\models\User;
use Yii;
use yii\base\ActionEvent;
use yii\base\Behavior;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\UnauthorizedHttpException;

class JwtAuth extends Behavior
{
    /** @var string[] action id yang tidak membutuhkan token */
    public array $except = [];

    /** @var Controller */
    public $owner;

    public function events(): array
    {
        return [
            Controller::EVENT_BEFORE_ACTION => 'autentikasi',
        ];
    }

    public function autentikasi(ActionEvent $event): void
    {
        if (in_array($event->action->id, $this->except, true)) {
            return;
        }

        $header = (string) Yii::$app->request->headers->get('Authorization', '');
        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $cocok)) {
            throw new UnauthorizedHttpException('Token tidak ada');
        }

        $klaim = Jwt::baca(trim($cocok[1]));
        if ($klaim === null || empty($klaim['sub'])) {
            throw new UnauthorizedHttpException('Token tidak valid atau kedaluwarsa');
        }

        $user = User::findIdentity($klaim['sub']);
        if ($user === null) {
            throw new UnauthorizedHttpException('Token tidak valid');
        }

        if (!$user->is_active) {
            throw new ForbiddenHttpException('Akun tidak aktif');
        }

        $role = $user->role;
        if ($role === null || !$role->is_active) {
            throw new ForbiddenHttpException('Role tidak aktif');
        }

        Yii::$app->user->setIdentity($user);
    }
}
