<?php

declare(strict_types=1);

namespace app\models;

class JenisLab extends MasterModel
{
    public static function tableName(): string
    {
        return '{{%jenis_lab}}';
    }
}
