<?php

declare(strict_types=1);

namespace app\models;

use Yii;
use yii\db\ActiveQuery;
use yii\web\IdentityInterface;

class User extends BaseModel implements IdentityInterface
{
    public static function tableName(): string
    {
        return '{{%user}}';
    }

    public static function findIdentity($id): ?static
    {
        return static::find()
            ->where(['id' => $id, 'is_delete' => false])
            ->one();
    }

    public static function findIdentityByAccessToken($token, $type = null): ?static
    {
        return null;
    }

    public function getId(): string
    {
        return (string) $this->id;
    }

    public function getAuthKey(): ?string
    {
        return null;
    }

    public function validateAuthKey($authKey): bool
    {
        return false;
    }

    public function validatePassword(string $password): bool
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    public function getRole(): ActiveQuery
    {
        return $this->hasOne(Role::class, ['id' => 'role_id']);
    }
}
