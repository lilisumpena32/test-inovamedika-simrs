<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

class OrderLab extends MasterModel
{
    public const STATUS_DIMINTA = 'diminta';
    public const STATUS_SELESAI = 'selesai';
    public const STATUS_BATAL = 'batal';

    public static function tableName(): string
    {
        return '{{%order_lab}}';
    }

    public function getJenisLab(): ActiveQuery
    {
        return $this->hasOne(JenisLab::class, ['id' => 'jenis_lab_id']);
    }

    public function getKunjungan(): ActiveQuery
    {
        return $this->hasOne(Kunjungan::class, ['id' => 'kunjungan_id']);
    }
}
