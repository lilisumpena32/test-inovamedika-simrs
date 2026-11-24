<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

class StokObat extends MasterModel
{
    public static function tableName(): string
    {
        return '{{%stok_obat}}';
    }

    public function getObat(): ActiveQuery
    {
        return $this->hasOne(Obat::class, ['id' => 'obat_id']);
    }
}
