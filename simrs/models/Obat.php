<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

class Obat extends MasterModel
{
    public static function tableName(): string
    {
        return '{{%obat}}';
    }

    public function getKategori(): ActiveQuery
    {
        return $this->hasOne(Kategori::class, ['id' => 'kategori_id']);
    }

    public function getGolonganObat(): ActiveQuery
    {
        return $this->hasOne(GolonganObat::class, ['id' => 'golongan_obat_id']);
    }

    public function getSatuan(): ActiveQuery
    {
        return $this->hasOne(Satuan::class, ['id' => 'satuan_id']);
    }
}
