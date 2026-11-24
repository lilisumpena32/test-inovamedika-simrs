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

    public static function cariLengkap(string $id): ?Kunjungan
    {
        return static::find()->where(['id' => $id])->with(['pasien', 'dokter', 'poli'])->one();
    }

    public static function ringkas(Kunjungan $k): array
    {
        return [
            'id' => (string) $k->id,
            'no_kunjungan' => $k->no_kunjungan,
            'pasien' => $k->pasien === null
                ? null
                : [
                    'id' => (string) $k->pasien->id,
                    'no_rm' => $k->pasien->no_rm,
                    'nama' => $k->pasien->nama,
                ],
            'dokter' => $k->dokter === null
                ? null
                : ['id' => (string) $k->dokter->id, 'name' => $k->dokter->nama_karyawan],
            'poli' => $k->poli === null
                ? null
                : [
                    'id' => (string) $k->poli->id,
                    'name' => $k->poli->nama,
                    'initial' => $k->poli->initial,
                ],
            'jadwal_id' => (string) $k->jadwal_id,
            'tgl_kunjungan' => $k->tgl_kunjungan,
            'no_antrean' => (int) $k->no_antrean,
            'kode_antrean' => ($k->poli === null ? '' : (string) $k->poli->initial)
                . '-'
                . str_pad((string) $k->no_antrean, 3, '0', STR_PAD_LEFT),
            'status' => $k->status,
            'biaya_daftar' => (float) $k->biaya_daftar,
        ];
    }
}
