<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

class Karyawan extends MasterModel
{
    public static function tableName(): string
    {
        return '{{%karyawan}}';
    }

    public function getJabatan(): ActiveQuery
    {
        return $this->hasOne(Jabatan::class, ['id' => 'jabatan_id']);
    }

    public function getSpesialis(): ActiveQuery
    {
        return $this->hasOne(Spesialis::class, ['id' => 'spesialis_id']);
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
