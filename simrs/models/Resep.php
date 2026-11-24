<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

class Resep extends MasterModel
{
    public const STATUS_MENUNGGU = 'menunggu';
    public const STATUS_SELESAI = 'selesai';
    public const STATUS_BATAL = 'batal';

    public static function tableName(): string
    {
        return '{{%resep}}';
    }

    public function getKunjungan(): ActiveQuery
    {
        return $this->hasOne(Kunjungan::class, ['id' => 'kunjungan_id']);
    }

    public function getItems(): ActiveQuery
    {
        return $this->hasMany(ResepObat::class, ['resep_id' => 'id']);
    }
}
