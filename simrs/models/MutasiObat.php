<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

class MutasiObat extends MasterModel
{
    public const JENIS_MASUK = 'masuk';
    public const JENIS_KELUAR = 'keluar';

    public static function tableName(): string
    {
        return '{{%mutasi_obat}}';
    }

    public function getObat(): ActiveQuery
    {
        return $this->hasOne(Obat::class, ['id' => 'obat_id']);
    }

    public function getStokObat(): ActiveQuery
    {
        return $this->hasOne(StokObat::class, ['id' => 'stok_obat_id']);
    }
}
