<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

class Kunjungan extends MasterModel
{
    public const STATUS_MENUNGGU = 'menunggu';
    public const STATUS_DIPERIKSA = 'diperiksa';
    public const STATUS_MENUNGGU_OBAT = 'menunggu_obat';
    public const STATUS_MENUNGGU_BAYAR = 'menunggu_bayar';
    public const STATUS_SELESAI = 'selesai';
    public const STATUS_BATAL = 'batal';

    public static function tableName(): string
    {
        return '{{%kunjungan}}';
    }

    public function getPasien(): ActiveQuery
    {
        return $this->hasOne(Pasien::class, ['id' => 'pasien_id']);
    }

    public function getDokter(): ActiveQuery
    {
        return $this->hasOne(Karyawan::class, ['id' => 'dokter_id']);
    }

    public function getPoli(): ActiveQuery
    {
        return $this->hasOne(Poli::class, ['id' => 'poli_id']);
    }

    public function getJadwal(): ActiveQuery
    {
        return $this->hasOne(JadwalPraktek::class, ['id' => 'jadwal_id']);
    }
}
