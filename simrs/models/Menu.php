<?php

declare(strict_types=1);

namespace app\models;

class Menu extends BaseModel
{
    public static function tableName(): string
    {
        return '{{%menu}}';
    }
}
