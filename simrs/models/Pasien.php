<?php

declare(strict_types=1);

namespace app\models;

class Pasien extends MasterModel
{
    public static function tableName(): string
    {
        return '{{%pasien}}';
    }
}
