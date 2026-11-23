<?php

declare(strict_types=1);

namespace app\modules\api\controllers;

use app\models\Kategori;
use app\models\GolonganObat;
use app\models\Obat;
use app\models\Satuan;
use Yii;
use yii\web\NotFoundHttpException;

class ObatController extends ApiController
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
        $this->butuh('farmasi.obat', 'view');

        $request = Yii::$app->request;
        $q = trim((string) $request->get('q', ''));
        $aktif = (string) $request->get('is_active', '');

        $query = Obat::find()->with(['kategori', 'golonganObat', 'satuan']);

        if ($q !== '') {
            $query->andWhere(['or', ['like', 'nama_obat', $q], ['like', 'kode_obat', $q]]);
        }

        if ($aktif === 'true' || $aktif === 'false') {
            $query->andWhere(['is_active' => $aktif === 'true']);
        }

        $total = (int) (clone $query)->count();
        $halaman = max(1, (int) $request->get('page', '1'));
        $perHalaman = min(50, max(1, (int) $request->get('per', '15')));
        $baris = $query
            ->orderBy(['nama_obat' => SORT_ASC])
            ->offset(($halaman - 1) * $perHalaman)
            ->limit($perHalaman)
            ->all();

        return $this->sukses([
            'items' => array_map(fn (Obat $o) => $this->ringkas($o), $baris),
            'total' => $total,
            'page' => $halaman,
            'per' => $perHalaman,
        ]);
    }

    public function actionView(string $id): array
    {
        $this->butuh('farmasi.obat', 'view');

        return $this->sukses($this->ringkas($this->cari($id)));
    }

    public function actionCreate(): array
    {
        $this->butuh('farmasi.obat', 'create');

        $body = Yii::$app->request->getBodyParams();

        $obat = new Obat();
        $obat->nama_obat = trim((string) ($body['nama_obat'] ?? ''));
        $obat->kategori_id = trim((string) ($body['kategori_id'] ?? ''));
        $obat->golongan_obat_id = trim((string) ($body['golongan_obat_id'] ?? ''));
        $obat->satuan_id = trim((string) ($body['satuan_id'] ?? ''));
        $obat->harga_beli = (float) ($body['harga_beli'] ?? 0);
        $obat->harga_jual = (float) ($body['harga_jual'] ?? 0);
        $obat->stok_minimum = (int) ($body['stok_minimum'] ?? 0);
        $obat->is_active = true;

        $galat = $this->validasi($obat);
        if ($galat !== null) {
            return $this->gagal($galat, 422);
        }

        $transaksi = Yii::$app->db->beginTransaction();
        try {
            $nomor = (int) Yii::$app->db->createCommand(
                "SELECT COALESCE(MAX(SUBSTRING(kode_obat FROM 5)::int), 0) + 1 FROM obat WHERE kode_obat LIKE 'OBT-%'",
            )->queryScalar();
            $obat->kode_obat = 'OBT-' . str_pad((string) $nomor, 4, '0', STR_PAD_LEFT);

            if (!$obat->save(false)) {
                throw new \RuntimeException('gagal menyimpan obat');
            }

            $transaksi->commit();
        } catch (\Throwable $e) {
            $transaksi->rollBack();

            throw $e;
        }

        return $this->sukses($this->ringkas($obat), 201, 'Obat ditambahkan.');
    }

    public function actionUpdate(string $id): array
    {
        $this->butuh('farmasi.obat', 'update');

        $obat = $this->cari($id);
        $body = Yii::$app->request->getBodyParams();

        $obat->nama_obat = trim((string) ($body['nama_obat'] ?? ''));
        $obat->kategori_id = trim((string) ($body['kategori_id'] ?? ''));
        $obat->golongan_obat_id = trim((string) ($body['golongan_obat_id'] ?? ''));
        $obat->satuan_id = trim((string) ($body['satuan_id'] ?? ''));
        $obat->harga_beli = (float) ($body['harga_beli'] ?? 0);
        $obat->harga_jual = (float) ($body['harga_jual'] ?? 0);
        $obat->stok_minimum = (int) ($body['stok_minimum'] ?? 0);

        $galat = $this->validasi($obat);
        if ($galat !== null) {
            return $this->gagal($galat, 422);
        }

        $obat->save(false);

        return $this->sukses($this->ringkas($obat), 200, 'Obat diubah.');
    }

    public function actionStatus(string $id): array
    {
        $this->butuh('farmasi.obat', 'update');

        $obat = $this->cari($id);
        $aktif = filter_var(
            Yii::$app->request->getBodyParams()['is_active'] ?? null,
            FILTER_VALIDATE_BOOL,
            FILTER_NULL_ON_FAILURE,
        );

        if ($aktif === null) {
            return $this->gagal('Status tidak valid', 422);
        }

        $obat->is_active = $aktif;
        $obat->save(false);

        return $this->sukses($this->ringkas($obat), 200, 'Status obat diubah.');
    }

    private function cari(string $id): Obat
    {
        $obat = Obat::find()->where(['id' => $id])->one();

        if ($obat === null) {
            throw new NotFoundHttpException('Obat tidak ditemukan');
        }

        return $obat;
    }

    private function ringkas(Obat $o): array
    {
        return [
            'id' => (string) $o->id,
            'kode_obat' => $o->kode_obat,
            'nama_obat' => $o->nama_obat,
            'kategori' => $o->kategori === null
                ? null
                : ['id' => (string) $o->kategori->id, 'kode' => $o->kategori->kode_kategori, 'name' => $o->kategori->nama_kategori],
            'golongan' => $o->golonganObat === null
                ? null
                : ['id' => (string) $o->golonganObat->id, 'kode' => $o->golonganObat->kode_golongan_obat, 'name' => $o->golonganObat->nama_golongan_obat],
            'satuan' => $o->satuan === null
                ? null
                : ['id' => (string) $o->satuan->id, 'kode' => $o->satuan->kode_satuan, 'name' => $o->satuan->nama_satuan],
            'harga_beli' => (float) $o->harga_beli,
            'harga_jual' => (float) $o->harga_jual,
            'stok_minimum' => (int) $o->stok_minimum,
            'is_active' => (bool) $o->is_active,
        ];
    }

    private function validasi(Obat $o): ?string
    {
        if ($o->nama_obat === '' || mb_strlen($o->nama_obat) > 120) {
            return 'Nama obat wajib diisi maksimal 120 karakter';
        }

        if (Kategori::findOne($o->kategori_id) === null) {
            return 'Kategori tidak ditemukan';
        }

        if (GolonganObat::findOne($o->golongan_obat_id) === null) {
            return 'Golongan obat tidak ditemukan';
        }

        if (Satuan::findOne($o->satuan_id) === null) {
            return 'Satuan tidak ditemukan';
        }

        if ($o->harga_beli < 0 || $o->harga_jual < 0 || $o->stok_minimum < 0) {
            return 'Harga dan stok minimum tidak boleh minus';
        }

        return null;
    }
}
