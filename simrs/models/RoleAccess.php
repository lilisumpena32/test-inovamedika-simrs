<?php

declare(strict_types=1);

namespace app\models;

class RoleAccess extends BaseModel
{
    public static function tableName(): string
    {
        return '{{%role_access}}';
    }
}
