<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

class ResepObat extends MasterModel
{
    public static function tableName(): string
    {
        return '{{%resep_obat}}';
    }

    public function getResep(): ActiveQuery
    {
        return $this->hasOne(Resep::class, ['id' => 'resep_id']);
    }

    public function getObat(): ActiveQuery
    {
        return $this->hasOne(Obat::class, ['id' => 'obat_id']);
    }
}
