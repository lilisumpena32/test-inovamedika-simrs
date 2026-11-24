<?php

declare(strict_types=1);

namespace app\modules\api\controllers;

use app\models\Tindakan;
use Yii;
use yii\web\NotFoundHttpException;

class TindakanController extends ApiController
{
    protected function verbs(): array
    {
        return [
            'index' => ['GET'],
            'list' => ['GET'],
            'view' => ['GET'],
            'create' => ['POST'],
            'update' => ['PUT'],
            'status' => ['PUT'],
        ];
    }

    public function actionIndex(): array
    {
        $this->butuh('master.tindakan', 'view');

        $request = Yii::$app->request;
        $q = trim((string) $request->get('q', ''));
        $aktif = (string) $request->get('is_active', '');

        $query = Tindakan::find();

        if ($q !== '') {
            $query->andWhere(['or', ['ilike', 'nama', $q], ['ilike', 'kode_tindakan', $q]]);
        }

        if ($aktif === 'true' || $aktif === 'false') {
            $query->andWhere(['is_active' => $aktif === 'true']);
        }

        $baris = $query->orderBy('nama')->all();

        return $this->sukses(['items' => array_map(fn (Tindakan $t) => $this->ringkas($t), $baris)]);
    }

    public function actionList(): array
    {
        $this->butuh('pemeriksaan.daftar', 'view');

        $baris = Tindakan::find()->where(['is_active' => true])->orderBy('nama')->all();

        return $this->sukses(['items' => array_map(fn (Tindakan $t) => $this->ringkas($t), $baris)]);
    }

    public function actionView(string $id): array
    {
        $this->butuh('master.tindakan', 'view');

        return $this->sukses($this->ringkas($this->cari($id)));
    }

    public function actionCreate(): array
    {
        $this->butuh('master.tindakan', 'create');

        $body = Yii::$app->request->getBodyParams();

        $tindakan = new Tindakan();
        $tindakan->nama = trim((string) ($body['nama'] ?? ''));
        $tindakan->tarif = (float) ($body['tarif'] ?? 0);
        $tindakan->is_active = true;

        $galat = $this->validasi($tindakan);
        if ($galat !== null) {
            return $this->gagal($galat, 422);
        }

        $nomor = (int) Yii::$app->db->createCommand(
            "SELECT COALESCE(MAX(SUBSTRING(kode_tindakan FROM 5)::int), 0) FROM tindakan WHERE kode_tindakan LIKE 'TDN-%'",
        )->queryScalar();
        $tindakan->kode_tindakan = 'TDN-' . str_pad((string) ($nomor + 1), 4, '0', STR_PAD_LEFT);

        $tindakan->save(false);

        return $this->sukses($this->ringkas($tindakan), 201, 'Tindakan ditambahkan.');
    }

    public function actionUpdate(string $id): array
    {
        $this->butuh('master.tindakan', 'update');

        $tindakan = $this->cari($id);
        $body = Yii::$app->request->getBodyParams();

        $tindakan->nama = trim((string) ($body['nama'] ?? ''));
        $tindakan->tarif = (float) ($body['tarif'] ?? 0);

        $galat = $this->validasi($tindakan, $tindakan->id);
        if ($galat !== null) {
            return $this->gagal($galat, 422);
        }

        $tindakan->save(false);

        return $this->sukses($this->ringkas($tindakan), 200, 'Tindakan diubah.');
    }

    public function actionStatus(string $id): array
    {
        $this->butuh('master.tindakan', 'update');

        $tindakan = $this->cari($id);
        $aktif = filter_var(
            Yii::$app->request->getBodyParams()['is_active'] ?? null,
            FILTER_VALIDATE_BOOL,
            FILTER_NULL_ON_FAILURE,
        );

        if ($aktif === null) {
            return $this->gagal('Status tidak valid', 422);
        }

        $tindakan->is_active = $aktif;
        $tindakan->save(false);

        return $this->sukses($this->ringkas($tindakan), 200, 'Status tindakan diubah.');
    }

    private function cari(string $id): Tindakan
    {
        $tindakan = Tindakan::findOne($id);

        if ($tindakan === null) {
            throw new NotFoundHttpException('Tindakan tidak ditemukan');
        }

        return $tindakan;
    }

    private function ringkas(Tindakan $t): array
    {
        return [
            'id' => (string) $t->id,
            'kode_tindakan' => $t->kode_tindakan,
            'nama' => $t->nama,
            'tarif' => (float) $t->tarif,
            'is_active' => (bool) $t->is_active,
        ];
    }

    private function validasi(Tindakan $tindakan, ?string $idLain = null): ?string
    {
        if ($tindakan->nama === '' || mb_strlen($tindakan->nama) > 64) {
            return 'Nama tindakan wajib diisi maksimal 64 karakter';
        }

        if ($tindakan->tarif < 0) {
            return 'Tarif tidak boleh minus';
        }

        return null;
    }
}
