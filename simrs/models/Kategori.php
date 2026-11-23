<?php

declare(strict_types=1);

namespace app\models;

class Kategori extends BaseModel
{
    public static function tableName(): string
    {
        return '{{%kategori}}';
    }
}
