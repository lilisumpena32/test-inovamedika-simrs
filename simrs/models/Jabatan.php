<?php

declare(strict_types=1);

namespace app\models;

class Jabatan extends MasterModel
{
    public static function tableName(): string
    {
        return '{{%jabatan}}';
    }
}
