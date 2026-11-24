<?php

declare(strict_types=1);

namespace app\models;

class Tindakan extends MasterModel
{
    public static function tableName(): string
    {
        return '{{%tindakan}}';
    }
}
