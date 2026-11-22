<?php

declare(strict_types=1);

namespace app\models;

class Role extends BaseModel
{
    public static function tableName(): string
    {
        return '{{%role}}';
    }
}
