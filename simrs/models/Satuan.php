<?php

declare(strict_types=1);

namespace app\models;

class Satuan extends BaseModel
{
    public static function tableName(): string
    {
        return '{{%satuan}}';
    }
}
