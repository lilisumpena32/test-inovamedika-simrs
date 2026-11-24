<?php

declare(strict_types=1);

namespace app\modules\api\controllers;

use app\models\JenisLab;
use Yii;
use yii\web\NotFoundHttpException;

class JenisLabController extends ApiController
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
        $this->butuh('master.jenis-lab', 'view');

        $request = Yii::$app->request;
        $q = trim((string) $request->get('q', ''));
        $aktif = (string) $request->get('is_active', '');

        $query = JenisLab::find();

        if ($q !== '') {
            $query->andWhere(['or', ['ilike', 'nama', $q], ['ilike', 'kode_jenis_lab', $q]]);
        }

        if ($aktif === 'true' || $aktif === 'false') {
            $query->andWhere(['is_active' => $aktif === 'true']);
        }

        $baris = $query->orderBy('nama')->all();

        return $this->sukses(['items' => array_map(fn (JenisLab $j) => $this->ringkas($j), $baris)]);
    }

    public function actionList(): array
    {
        $this->butuh('pemeriksaan.daftar', 'view');

        $baris = JenisLab::find()->where(['is_active' => true])->orderBy('nama')->all();

        return $this->sukses(['items' => array_map(fn (JenisLab $j) => $this->ringkas($j), $baris)]);
    }

    public function actionView(string $id): array
    {
        $this->butuh('master.jenis-lab', 'view');

        return $this->sukses($this->ringkas($this->cari($id)));
    }

    public function actionCreate(): array
    {
        $this->butuh('master.jenis-lab', 'create');

        $body = Yii::$app->request->getBodyParams();

        $jenisLab = new JenisLab();
        $jenisLab->nama = trim((string) ($body['nama'] ?? ''));
        $jenisLab->tarif = (float) ($body['tarif'] ?? 0);
        $jenisLab->is_active = true;

        $galat = $this->validasi($jenisLab);
        if ($galat !== null) {
            return $this->gagal($galat, 422);
        }

        $nomor = (int) Yii::$app->db->createCommand(
            "SELECT COALESCE(MAX(SUBSTRING(kode_jenis_lab FROM 4)::int), 0) FROM jenis_lab WHERE kode_jenis_lab LIKE 'LB-%'",
        )->queryScalar();
        $jenisLab->kode_jenis_lab = 'LB-' . str_pad((string) ($nomor + 1), 4, '0', STR_PAD_LEFT);

        $jenisLab->save(false);

        return $this->sukses($this->ringkas($jenisLab), 201, 'Jenis lab ditambahkan.');
    }

    public function actionUpdate(string $id): array
    {
        $this->butuh('master.jenis-lab', 'update');

        $jenisLab = $this->cari($id);
        $body = Yii::$app->request->getBodyParams();

        $jenisLab->nama = trim((string) ($body['nama'] ?? ''));
        $jenisLab->tarif = (float) ($body['tarif'] ?? 0);

        $galat = $this->validasi($jenisLab);
        if ($galat !== null) {
            return $this->gagal($galat, 422);
        }

        $jenisLab->save(false);

        return $this->sukses($this->ringkas($jenisLab), 200, 'Jenis lab diubah.');
    }

    public function actionStatus(string $id): array
    {
        $this->butuh('master.jenis-lab', 'update');

        $jenisLab = $this->cari($id);
        $aktif = filter_var(
            Yii::$app->request->getBodyParams()['is_active'] ?? null,
            FILTER_VALIDATE_BOOL,
            FILTER_NULL_ON_FAILURE,
        );

        if ($aktif === null) {
            return $this->gagal('Status tidak valid', 422);
        }

        $jenisLab->is_active = $aktif;
        $jenisLab->save(false);

        return $this->sukses($this->ringkas($jenisLab), 200, 'Status jenis lab diubah.');
    }

    private function cari(string $id): JenisLab
    {
        $jenisLab = JenisLab::findOne($id);

        if ($jenisLab === null) {
            throw new NotFoundHttpException('Jenis lab tidak ditemukan');
        }

        return $jenisLab;
    }

    private function ringkas(JenisLab $j): array
    {
        return [
            'id' => (string) $j->id,
            'kode_jenis_lab' => $j->kode_jenis_lab,
            'nama' => $j->nama,
            'tarif' => (float) $j->tarif,
            'is_active' => (bool) $j->is_active,
        ];
    }

    private function validasi(JenisLab $jenisLab): ?string
    {
        if ($jenisLab->nama === '' || mb_strlen($jenisLab->nama) > 64) {
            return 'Nama jenis lab wajib diisi maksimal 64 karakter';
        }

        if ($jenisLab->tarif < 0) {
            return 'Tarif tidak boleh minus';
        }

        return null;
    }
}
