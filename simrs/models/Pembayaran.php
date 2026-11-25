<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

class Pembayaran extends MasterModel
{
    public const METODE_TUNAI = 'tunai';
    public const METODE_NON_TUNAI = 'non_tunai';

    public static function tableName(): string
    {
        return '{{%pembayaran}}';
    }

    public function getTagihan(): ActiveQuery
    {
        return $this->hasOne(Tagihan::class, ['id' => 'tagihan_id']);
    }
}
