<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

class JadwalPraktek extends MasterModel
{
    public static function tableName(): string
    {
        return '{{%jadwal_praktek}}';
    }

    public function getKaryawan(): ActiveQuery
    {
        return $this->hasOne(Karyawan::class, ['id' => 'karyawan_id']);
    }

    public function getPoli(): ActiveQuery
    {
        return $this->hasOne(Poli::class, ['id' => 'poli_id']);
    }
}
