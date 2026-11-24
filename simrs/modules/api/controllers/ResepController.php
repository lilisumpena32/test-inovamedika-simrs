<?php

declare(strict_types=1);

namespace app\modules\api\controllers;

use app\models\Kunjungan;
use app\models\MutasiObat;
use app\models\Obat;
use app\models\Resep;
use app\models\ResepObat;
use app\models\StokObat;
use Yii;
use yii\db\Transaction;
use yii\web\NotFoundHttpException;

class ResepController extends ApiController
{
    protected function verbs(): array
    {
        return [
            'buat' => ['POST'],
            'ubah' => ['PUT'],
            'daftar' => ['GET'],
            'kunjungan' => ['GET'],
            'lihat' => ['GET'],
            'serah' => ['PUT'],
        ];
    }

    public function actionKunjungan(string $id): array
    {
        $this->butuh('pemeriksaan.daftar', 'view');

        $resep = Resep::find()->where(['kunjungan_id' => $id])->with(['kunjungan.pasien', 'kunjungan.poli', 'kunjungan.dokter', 'items.obat'])->one();

        return $this->sukses($resep === null ? null : $this->ringkas($resep));
    }

    public function actionBuat(string $id): array
    {
        $this->butuh('pemeriksaan.daftar', 'update');

        $kunjungan = Kunjungan::cariLengkap($id);

        if ($kunjungan === null) {
            throw new NotFoundHttpException('Kunjungan tidak ditemukan');
        }

        if (Resep::find()->where(['kunjungan_id' => $kunjungan->id])->exists()) {
            return $this->gagal('Resep sudah dibuat untuk kunjungan ini', 422);
        }

        if ($kunjungan->status !== Kunjungan::STATUS_DIPERIKSA) {
            return $this->gagal('Resep hanya dibuat untuk kunjungan berstatus diperiksa', 422);
        }

        $items = $this->itemsDariBody(Yii::$app->request->getBodyParams());
        if ($items === null) {
            return $this->gagal('Item resep tidak valid', 422);
        }

        $galat = $this->galatStok($items);
        if ($galat !== null) {
            return $this->gagal($galat, 422);
        }

        $transaksi = Yii::$app->db->beginTransaction();

        try {
            $resep = new Resep(['kunjungan_id' => (string) $kunjungan->id, 'status' => Resep::STATUS_MENUNGGU]);

            if (!$resep->save(false)) {
                throw new \RuntimeException('gagal menyimpan resep');
            }

            $this->simpanItems($resep, $items);

            Yii::$app->db->createCommand(
                'SELECT fn_ubah_status_kunjungan(:id, :status)',
                [':id' => (string) $kunjungan->id, ':status' => Kunjungan::STATUS_MENUNGGU_OBAT],
            )->execute();

            $transaksi->commit();
        } catch (\Throwable $e) {
            $transaksi->rollBack();

            throw $e;
        }

        return $this->sukses($this->ringkas($this->cari($resep->id)), 201, 'Resep dibuat.');
    }

    public function actionUbah(string $id): array
    {
        $this->butuh('pemeriksaan.daftar', 'update');

        $resep = Resep::find()->where(['kunjungan_id' => $id])->with(['kunjungan.pasien', 'kunjungan.poli', 'kunjungan.dokter', 'items.obat'])->one();

        if ($resep === null) {
            throw new NotFoundHttpException('Resep tidak ditemukan');
        }

        if ($resep->status !== Resep::STATUS_MENUNGGU) {
            return $this->gagal('Resep hanya bisa diubah sebelum obat diserahkan', 422);
        }

        $items = $this->itemsDariBody(Yii::$app->request->getBodyParams());
        if ($items === null) {
            return $this->gagal('Item resep tidak valid', 422);
        }

        $galat = $this->galatStok($items);
        if ($galat !== null) {
            return $this->gagal($galat, 422);
        }

        $transaksi = Yii::$app->db->beginTransaction();

        try {
            ResepObat::deleteAll(['resep_id' => $resep->id]);
            $this->simpanItems($resep, $items);
            $transaksi->commit();
        } catch (\Throwable $e) {
            $transaksi->rollBack();

            throw $e;
        }

        return $this->sukses($this->ringkas($this->cari($resep->id)), 200, 'Resep diubah.');
    }    public function actionDaftar(): array
    {
        $this->butuh('farmasi.resep', 'view');

        $request = Yii::$app->request;
        $tanggal = trim((string) $request->get('tanggal', ''));
        $status = trim((string) $request->get('status', ''));

        $query = Resep::find()->with(['kunjungan.pasien', 'kunjungan.poli', 'kunjungan.dokter', 'items.obat']);

        if ($tanggal !== '') {
            $query->joinWith('kunjungan k', false)->andWhere(['k.tgl_kunjungan' => $tanggal]);
        }

        if ($status !== '') {
            $query->andWhere(['{{%resep}}.status' => $status]);
        }

        $baris = $query->orderBy(['{{%resep}}.create_date' => SORT_ASC])->all();

        return $this->sukses([
            'items' => array_map(fn (Resep $r) => $this->ringkas($r), $baris),
        ]);
    }

    public function actionLihat(string $id): array
    {
        $this->butuh('farmasi.resep', 'view');

        return $this->sukses($this->ringkas($this->cari($id)));
    }

    public function actionSerah(string $id): array
    {
        $this->butuh('farmasi.resep', 'update');

        $resep = $this->cari($id);

        if ($resep->status !== Resep::STATUS_MENUNGGU) {
            return $this->gagal('Resep sudah diproses sebelumnya', 422);
        }

        $transaksi = Yii::$app->db->beginTransaction();

        try {
            $items = ResepObat::find()->where(['resep_id' => $resep->id])->with('obat')->all();

            if ($items === []) {
                return $this->gagal('Resep tidak punya item obat', 422);
            }

            // Kunci batch FEFO dulu, baru alokasi. Dua penyerahan bersamaan
            // menunggu di sini, sehingga stok tidak mungkin minus.
            $dipakai = [];

            foreach ($items as $item) {
                $batchs = Yii::$app->db->createCommand(
                    'SELECT id, obat_id, jumlah FROM stok_obat WHERE obat_id = :obat AND jumlah > 0 ORDER BY kadaluarsa FOR UPDATE',
                    [':obat' => (string) $item->obat_id],
                )->queryAll();

                $sisaButuh = (int) $item->jumlah;

                foreach ($batchs as $batch) {
                    if ($sisaButuh <= 0) {
                        break;
                    }

                    $ambil = min((int) $batch['jumlah'], $sisaButuh);

                    if ($ambil <= 0) {
                        continue;
                    }

                    $dipakai[] = [
                        'id' => (string) $batch['id'],
                        'obat_id' => (string) $batch['obat_id'],
                        'ambil' => $ambil,
                        'sisa' => (int) $batch['jumlah'] - $ambil,
                    ];
                    $sisaButuh -= $ambil;
                }

                if ($sisaButuh > 0) {
                    $transaksi->rollBack();

                    return $this->gagal('Stok obat ' . $item->obat->nama_obat . ' tidak cukup', 422);
                }
            }

            foreach ($dipakai as $pakai) {
                Yii::$app->db->createCommand()->update(
                    '{{%stok_obat}}',
                    [
                        'jumlah' => $pakai['sisa'],
                        'update_date' => date('Y-m-d H:i:s'),
                    ],
                    ['id' => $pakai['id']],
                )->execute();

                $mutasi = new MutasiObat([
                    'obat_id' => $pakai['obat_id'],
                    'stok_obat_id' => $pakai['id'],
                    'jenis' => MutasiObat::JENIS_KELUAR,
                    'jumlah' => $pakai['ambil'],
                    'referensi' => (string) $resep->id,
                ]);

                if (!$mutasi->save(false)) {
                    throw new \RuntimeException('gagal menyimpan mutasi keluar');
                }
            }

            $resep->status = Resep::STATUS_SELESAI;
            $resep->save(false);

            $kunjungan = Kunjungan::findOne($resep->kunjungan_id);

            if ($kunjungan !== null && $kunjungan->status === Kunjungan::STATUS_MENUNGGU_OBAT) {
                Yii::$app->db->createCommand(
                    'SELECT fn_ubah_status_kunjungan(:id, :status)',
                    [':id' => (string) $kunjungan->id, ':status' => Kunjungan::STATUS_MENUNGGU_BAYAR],
                )->execute();
            }

            $transaksi->commit();
        } catch (\Throwable $e) {
            $transaksi->rollBack();

            throw $e;
        }

        return $this->sukses($this->ringkas($this->cari($resep->id)), 200, 'Obat diserahkan.');
    }

    private function cari(string $id): Resep
    {
        $resep = Resep::find()->where(['id' => $id])->with(['kunjungan.pasien', 'kunjungan.poli', 'kunjungan.dokter', 'items.obat'])->one();

        if ($resep === null) {
            throw new NotFoundHttpException('Resep tidak ditemukan');
        }

        return $resep;
    }

    private function ringkas(Resep $r): array
    {
        $kunjungan = $r->kunjungan;

        return [
            'id' => (string) $r->id,
            'kunjungan_id' => (string) $r->kunjungan_id,
            'kunjungan' => $kunjungan === null
                ? null
                : Kunjungan::ringkas($kunjungan),
            'status' => $r->status,
            'items' => array_map(fn (ResepObat $item) => [
                'id' => (string) $item->id,
                'obat' => $item->obat === null
                    ? null
                    : ['id' => (string) $item->obat->id, 'kode' => $item->obat->kode_obat, 'nama' => $item->obat->nama_obat],
                'dosis' => $item->dosis,
                'frekuensi' => $item->frekuensi,
                'jumlah' => (int) $item->jumlah,
            ], $r->items),
        ];
    }

    private function itemsDariBody(array $body): ?array
    {
        $baris = $body['items'] ?? null;

        if (!is_array($baris) || $baris === []) {
            return null;
        }

        $items = [];

        foreach ($baris as $item) {
            if (!is_array($item)) {
                return null;
            }

            $obatId = trim((string) ($item['obat_id'] ?? ''));
            $jumlah = (int) ($item['jumlah'] ?? 0);

            if ($obatId === '' || $jumlah < 1) {
                return null;
            }

            $items[] = [
                'obat_id' => $obatId,
                'dosis' => isset($item['dosis']) && $item['dosis'] !== '' ? mb_substr(trim((string) $item['dosis']), 0, 50) : null,
                'frekuensi' => isset($item['frekuensi']) && $item['frekuensi'] !== '' ? mb_substr(trim((string) $item['frekuensi']), 0, 50) : null,
                'jumlah' => $jumlah,
            ];
        }

        return $items;
    }

    private function galatStok(array $items): ?string
    {
        foreach ($items as $item) {
            $obat = Obat::findOne($item['obat_id']);

            if ($obat === null || !$obat->is_active) {
                return 'Obat tidak ditemukan atau tidak aktif';
            }

            $tersedia = (int) Yii::$app->db->createCommand(
                'SELECT COALESCE(SUM(jumlah), 0) FROM stok_obat WHERE obat_id = :obat',
                [':obat' => (string) $obat->id],
            )->queryScalar();

            if ($tersedia < $item['jumlah']) {
                return 'Stok obat ' . $obat->nama_obat . ' tidak cukup';
            }
        }

        return null;
    }

    private function simpanItems(Resep $resep, array $items): void
    {
        foreach ($items as $item) {
            $detail = new ResepObat([
                'resep_id' => (string) $resep->id,
                'obat_id' => $item['obat_id'],
                'dosis' => $item['dosis'],
                'frekuensi' => $item['frekuensi'],
                'jumlah' => $item['jumlah'],
            ]);

            if (!$detail->save(false)) {
                throw new \RuntimeException('gagal menyimpan item resep');
            }
        }
    }
}
