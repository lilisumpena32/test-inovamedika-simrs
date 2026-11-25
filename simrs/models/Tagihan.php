<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

class Tagihan extends MasterModel
{
    public const STATUS_BELUM = 'belum';
    public const STATUS_SEBAGIAN = 'sebagian';
    public const STATUS_LUNAS = 'lunas';

    public static function tableName(): string
    {
        return '{{%tagihan}}';
    }

    public function getKunjungan(): ActiveQuery
    {
        return $this->hasOne(Kunjungan::class, ['id' => 'kunjungan_id']);
    }

    public function getItems(): ActiveQuery
    {
        return $this->hasMany(TagihanItem::class, ['tagihan_id' => 'id']);
    }

    public function getPembayaran(): ActiveQuery
    {
        return $this->hasMany(Pembayaran::class, ['tagihan_id' => 'id']);
    }
}
