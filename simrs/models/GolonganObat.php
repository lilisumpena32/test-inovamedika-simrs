<?php

declare(strict_types=1);

namespace app\models;

class GolonganObat extends BaseModel
{
    public static function tableName(): string
    {
        return '{{%golongan_obat}}';
    }
}
