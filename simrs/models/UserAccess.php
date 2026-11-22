<?php

declare(strict_types=1);

namespace app\models;

use RuntimeException;

class UserAccess extends BaseModel
{
    public static function tableName(): string
    {
        return '{{%user_access}}';
    }

    public static function sinkron(User $user): int
    {
        $baru = 0;
        $menuRole = RoleAccess::find()
            ->select('menu_id')
            ->where(['role_id' => $user->role_id, 'can_view' => true])
            ->column();

        foreach ($menuRole as $menuId) {
            if (static::find()->where(['user_id' => $user->id, 'menu_id' => $menuId])->exists()) {
                continue;
            }

            $akses = new static([
                'user_id' => $user->id,
                'menu_id' => $menuId,
            ]);

            if (!$akses->save(false)) {
                throw new RuntimeException("gagal menyimpan user_access {$user->id} -> {$menuId}");
            }

            $baru++;
        }

        return $baru;
    }
}
