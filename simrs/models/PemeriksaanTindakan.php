<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

class PemeriksaanTindakan extends MasterModel
{
    public static function tableName(): string
    {
        return '{{%pemeriksaan_tindakan}}';
    }

    public function getTindakan(): ActiveQuery
    {
        return $this->hasOne(Tindakan::class, ['id' => 'tindakan_id']);
    }

    public function getKunjungan(): ActiveQuery
    {
        return $this->hasOne(Kunjungan::class, ['id' => 'kunjungan_id']);
    }
}
