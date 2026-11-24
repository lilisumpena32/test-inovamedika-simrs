<?php

declare(strict_types=1);

namespace app\modules\api\controllers;

use app\models\Poli;
use Yii;
use yii\web\NotFoundHttpException;

class PoliController extends ApiController
{
    protected function verbs(): array
    {
        return [
            'index' => ['GET'],
            'view' => ['GET'],
            'create' => ['POST'],
            'update' => ['PUT'],
            'status' => ['PUT'],
        ];
    }

    public function actionIndex(): array
    {
        $this->butuh('master.poli', 'view');

        $request = Yii::$app->request;
        $q = trim((string) $request->get('q', ''));
        $aktif = (string) $request->get('is_active', '');

        $query = Poli::find();

        if ($q !== '') {
            $query->andWhere(['or', ['ilike', 'nama', $q], ['ilike', 'kode', $q]]);
        }

        if ($aktif === 'true' || $aktif === 'false') {
            $query->andWhere(['is_active' => $aktif === 'true']);
        }

        $baris = $query->orderBy(['nama' => SORT_ASC])->all();

        return $this->sukses(['items' => array_map(fn (Poli $poli) => $this->ringkas($poli), $baris)]);
    }

    public function actionView(string $id): array
    {
        $this->butuh('master.poli', 'view');

        return $this->sukses($this->ringkas($this->cari($id)));
    }

    public function actionCreate(): array
    {
        $this->butuh('master.poli', 'create');

        $body = Yii::$app->request->getBodyParams();

        $poli = new Poli();
        $poli->kode = strtoupper(trim((string) ($body['kode'] ?? '')));
        $poli->nama = trim((string) ($body['nama'] ?? ''));
        $poli->biaya_daftar = (float) ($body['biaya_daftar'] ?? 0);
        $poli->initial = strtoupper(trim((string) ($body['initial'] ?? '')));
        $poli->is_active = true;

        $galat = $this->validasi($poli);
        if ($galat !== null) {
            return $this->gagal($galat, 422);
        }

        $poli->save(false);

        return $this->sukses($this->ringkas($poli), 201, 'Poli ditambahkan.');
    }

    public function actionUpdate(string $id): array
    {
        $this->butuh('master.poli', 'update');

        $poli = $this->cari($id);
        $body = Yii::$app->request->getBodyParams();

        $poli->nama = trim((string) ($body['nama'] ?? ''));
        $poli->biaya_daftar = (float) ($body['biaya_daftar'] ?? 0);
        $poli->initial = strtoupper(trim((string) ($body['initial'] ?? '')));

        $galat = $this->validasi($poli, $poli->id);
        if ($galat !== null) {
            return $this->gagal($galat, 422);
        }

        $poli->save(false);

        return $this->sukses($this->ringkas($poli), 200, 'Poli diubah.');
    }

    public function actionStatus(string $id): array
    {
        $this->butuh('master.poli', 'update');

        $poli = $this->cari($id);
        $aktif = filter_var(
            Yii::$app->request->getBodyParams()['is_active'] ?? null,
            FILTER_VALIDATE_BOOL,
            FILTER_NULL_ON_FAILURE,
        );

        if ($aktif === null) {
            return $this->gagal('Status tidak valid', 422);
        }

        $poli->is_active = $aktif;
        $poli->save(false);

        return $this->sukses($this->ringkas($poli), 200, 'Status poli diubah.');
    }

    private function cari(string $id): Poli
    {
        $poli = Poli::find()->where(['id' => $id])->one();

        if ($poli === null) {
            throw new NotFoundHttpException('Poli tidak ditemukan');
        }

        return $poli;
    }

    private function ringkas(Poli $poli): array
    {
        return [
            'id' => (string) $poli->id,
            'kode' => $poli->kode,
            'nama' => $poli->nama,
            'biaya_daftar' => (float) $poli->biaya_daftar,
            'initial' => $poli->initial,
            'is_active' => (bool) $poli->is_active,
        ];
    }

    private function validasi(Poli $poli, ?string $idLain = null): ?string
    {
        if (!preg_match('/^[A-Z0-9_]{3,16}$/', $poli->kode)) {
            return 'Kode poli 3 sampai 16 karakter huruf besar, angka, atau garis bawah';
        }

        $kodeSudah = Poli::find()->where(['kode' => $poli->kode]);

        if ($idLain !== null) {
            $kodeSudah->andWhere(['!=', 'id', $idLain]);
        }

        if ($kodeSudah->exists()) {
            return 'Kode poli sudah dipakai';
        }

        if ($poli->nama === '' || mb_strlen($poli->nama) > 64) {
            return 'Nama poli wajib diisi maksimal 64 karakter';
        }

        if ($poli->biaya_daftar < 0) {
            return 'Biaya daftar tidak boleh minus';
        }

        if (!preg_match('/^[A-Z]{3}$/', $poli->initial)) {
            return 'Initial antrean wajib 3 huruf';
        }

        $sudah = Poli::find()->where(['initial' => $poli->initial]);

        if ($idLain !== null) {
            $sudah->andWhere(['!=', 'id', $idLain]);
        }

        if ($sudah->exists()) {
            return 'Initial antrean sudah dipakai poli lain';
        }

        return null;
    }
}
