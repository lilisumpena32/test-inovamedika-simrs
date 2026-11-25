<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

class TagihanItem extends MasterModel
{
    public static function tableName(): string
    {
        return '{{%tagihan_item}}';
    }

    public function getTagihan(): ActiveQuery
    {
        return $this->hasOne(Tagihan::class, ['id' => 'tagihan_id']);
    }
}
