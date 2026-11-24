<?php

declare(strict_types=1);

namespace app\modules\api\controllers;

use app\models\Kunjungan;
use app\models\Poli;
use Yii;

class AntreanController extends ApiController
{
    protected function verbs(): array
    {
        return ['index' => ['GET']];
    }

    public function actionIndex(): array
    {
        $this->butuh('pendaftaran.kunjungan', 'view');

        $tanggal = trim((string) Yii::$app->request->get('tanggal', date('Y-m-d')));
        $poliId = trim((string) Yii::$app->request->get('poli_id', ''));

        if ($tanggal !== '' && !$this->tanggalValid($tanggal)) {
            return $this->gagal('Tanggal tidak valid', 422);
        }

        $query = Kunjungan::find()
            ->where(['tgl_kunjungan' => $tanggal])
            ->andWhere(['!=', 'status', Kunjungan::STATUS_BATAL])
            ->with(['pasien', 'dokter', 'poli']);

        if ($poliId !== '') {
            $query->andWhere(['poli_id' => $poliId]);
        }

        $baris = $query->orderBy(['no_antrean' => SORT_ASC])->all();

        return $this->sukses([
            'tanggal' => $tanggal,
            'items' => array_map(fn (Kunjungan $k) => [
                'id' => (string) $k->id,
                'no_antrean' => (int) $k->no_antrean,
                'kode_antrean' => ($k->poli === null ? '' : (string) $k->poli->initial)
                    . '-'
                    . str_pad((string) $k->no_antrean, 3, '0', STR_PAD_LEFT),
                'no_kunjungan' => $k->no_kunjungan,
                'pasien' => $k->pasien === null
                    ? null
                    : ['no_rm' => $k->pasien->no_rm, 'nama' => $k->pasien->nama],
                'dokter' => $k->dokter === null ? null : $k->dokter->nama_karyawan,
                'status' => $k->status,
            ], $baris),
        ]);
    }

    private function tanggalValid(string $tanggal): bool
    {
        $pecah = explode('-', $tanggal);

        return count($pecah) === 3 && checkdate((int) $pecah[1], (int) $pecah[2], (int) $pecah[0]);
    }
}
