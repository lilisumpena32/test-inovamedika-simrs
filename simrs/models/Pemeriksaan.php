<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

class Pemeriksaan extends MasterModel
{
    public static function tableName(): string
    {
        return '{{%pemeriksaan}}';
    }

    public function getKunjungan(): ActiveQuery
    {
        return $this->hasOne(Kunjungan::class, ['id' => 'kunjungan_id']);
    }
}
