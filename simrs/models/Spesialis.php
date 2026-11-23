<?php

declare(strict_types=1);

namespace app\models;

class Spesialis extends MasterModel
{
    public static function tableName(): string
    {
        return '{{%spesialis}}';
    }
}
