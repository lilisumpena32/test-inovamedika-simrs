<?php

declare(strict_types=1);

namespace app\modules\api\controllers;

use app\models\Kunjungan;
use app\models\OrderLab;
use app\models\PemeriksaanTindakan;
use app\models\Pembayaran;
use app\models\Resep;
use app\models\ResepObat;
use app\models\Tagihan;
use app\models\TagihanItem;
use Yii;
use yii\db\Transaction;
use yii\web\NotFoundHttpException;

class TagihanController extends ApiController
{
    protected function verbs(): array
    {
        return [
            'index' => ['GET'],
            'view' => ['GET'],
            'create' => ['POST'],
            'bayar' => ['PUT'],
        ];
    }

    public function actionIndex(): array
    {
        $this->butuh('kasir.tagihan', 'view');

        $request = Yii::$app->request;
        $tanggal = trim((string) $request->get('tanggal', ''));
        $status = trim((string) $request->get('status', ''));

        $query = Tagihan::find()->with(['kunjungan.pasien', 'kunjungan.poli', 'kunjungan.dokter']);

        if ($tanggal !== '') {
            $query->joinWith('kunjungan k', false)->andWhere(['k.tgl_kunjungan' => $tanggal]);
        }

        if ($status !== '') {
            $query->andWhere(['{{%tagihan}}.status' => $status]);
        }

        $baris = $query->orderBy(['{{%tagihan}}.create_date' => SORT_ASC])->all();

        return $this->sukses(['items' => array_map(fn (Tagihan $t) => $this->ringkas($t), $baris)]);
    }

    public function actionView(string $id): array
    {
        $this->butuh('kasir.tagihan', 'view');

        return $this->sukses($this->ringkas($this->cari($id)));
    }

    public function actionCreate(): array
    {
        $this->butuh('kasir.tagihan', 'create');

        $body = Yii::$app->request->getBodyParams();
        $kunjunganId = trim((string) ($body['kunjungan_id'] ?? ''));

        $kunjungan = Kunjungan::cariLengkap($kunjunganId);

        if ($kunjungan === null) {
            throw new NotFoundHttpException('Kunjungan tidak ditemukan');
        }

        if ($kunjungan->status !== Kunjungan::STATUS_MENUNGGU_BAYAR) {
            return $this->gagal('Tagihan hanya dibuat untuk kunjungan berstatus menunggu_bayar', 422);
        }

        $sudah = Tagihan::find()->where(['kunjungan_id' => $kunjungan->id])->one();

        if ($sudah !== null) {
            return $this->sukses($this->ringkas($sudah), 200, 'Tagihan sudah ada untuk kunjungan ini.');
        }

        $items = $this->rancangItem($kunjungan);

        if ($items === []) {
            return $this->gagal('Kunjungan ini belum punya komponen biaya', 422);
        }

        $transaksi = Yii::$app->db->beginTransaction();

        try {
            $urut = (int) Yii::$app->db->createCommand('SELECT nextval(\'seq_tagihan\')')->queryScalar();
            $noTagihan = sprintf(
                'INV-%s-%04d',
                str_replace('-', '', $kunjungan->tgl_kunjungan),
                $urut,
            );

            $tagihan = new Tagihan([
                'no_tagihan' => $noTagihan,
                'kunjungan_id' => (string) $kunjungan->id,
                'status' => Tagihan::STATUS_BELUM,
                'total' => array_sum(array_map(fn (array $i) => $i['harga'] * $i['jumlah'], $items)),
            ]);

            if (!$tagihan->save(false)) {
                throw new \RuntimeException('gagal menyimpan tagihan');
            }

            foreach ($items as $item) {
                $detail = new TagihanItem([
                    'tagihan_id' => (string) $tagihan->id,
                    'jenis' => $item['jenis'],
                    'referensi' => $item['referensi'],
                    'uraian' => $item['uraian'],
                    'jumlah' => $item['jumlah'],
                    'harga' => $item['harga'],
                ]);

                if (!$detail->save(false)) {
                    throw new \RuntimeException('gagal menyimpan item tagihan');
                }
            }

            $transaksi->commit();
        } catch (\Throwable $e) {
            $transaksi->rollBack();

            throw $e;
        }

        return $this->sukses($this->ringkas($this->cari($tagihan->id)), 201, 'Tagihan dibuat.');
    }

    public function actionBayar(string $id): array
    {
        $this->butuh('kasir.tagihan', 'create');

        $body = Yii::$app->request->getBodyParams();

        $kunci = trim((string) ($body['idempotency_key'] ?? ''));

        if ($kunci === '' || mb_strlen($kunci) > 64) {
            return $this->gagal('Idempotency key wajib diisi maksimal 64 karakter', 422);
        }

        $sudah = Pembayaran::find()->where(['idempotency_key' => $kunci])->one();

        if ($sudah !== null) {
            return $this->sukses($this->ringkas($this->cari($sudah->tagihan_id)), 200, 'Pembayaran sudah tercatat sebelumnya.');
        }

        $metode = trim((string) ($body['metode'] ?? ''));

        if (!in_array($metode, [Pembayaran::METODE_TUNAI, Pembayaran::METODE_NON_TUNAI], true)) {
            return $this->gagal('Metode pembayaran hanya tunai atau non_tunai', 422);
        }

        $tagihan = $this->cari($id);
        $sisa = $this->sisa($tagihan);

        if ($sisa <= 0) {
            return $this->gagal('Tagihan ini sudah lunas', 422);
        }

        $nominal = (float) ($body['nominal'] ?? 0);

        if ($nominal <= 0) {
            return $this->gagal('Nominal pembayaran wajib lebih dari 0', 422);
        }

        if ($nominal > $sisa) {
            return $this->gagal('Nominal pembayaran melebihi sisa tagihan ' . number_format($sisa, 2, ',', '.'), 422);
        }

        $transaksi = Yii::$app->db->beginTransaction();

        try {
            $bayar = new Pembayaran([
                'tagihan_id' => (string) $tagihan->id,
                'idempotency_key' => $kunci,
                'metode' => $metode,
                'nominal' => $nominal,
            ]);

            if (!$bayar->save(false)) {
                throw new \RuntimeException('gagal menyimpan pembayaran');
            }

            $dibayar = $this->dibayar($tagihan->id);
            $tagihan->status = $dibayar >= (float) $tagihan->total ? Tagihan::STATUS_LUNAS : Tagihan::STATUS_SEBAGIAN;
            $tagihan->save(false);

            if ($tagihan->status === Tagihan::STATUS_LUNAS) {
                Yii::$app->db->createCommand(
                    'SELECT fn_ubah_status_kunjungan(:id, :status)',
                    [':id' => (string) $tagihan->kunjungan_id, ':status' => Kunjungan::STATUS_SELESAI],
                )->execute();
            }

            $transaksi->commit();
        } catch (\Throwable $e) {
            $transaksi->rollBack();

            throw $e;
        }

        return $this->sukses($this->ringkas($this->cari($tagihan->id)), 200, 'Pembayaran dicatat.');
    }

    private function rancangItem(Kunjungan $kunjungan): array
    {
        $idKunjungan = (string) $kunjungan->id;
        $items = [];

        $items[] = [
            'jenis' => 'daftar',
            'referensi' => $idKunjungan,
            'uraian' => 'Biaya pendaftaran',
            'jumlah' => 1,
            'harga' => (float) $kunjungan->biaya_daftar,
        ];

        foreach (PemeriksaanTindakan::find()->where(['kunjungan_id' => $idKunjungan])->with('tindakan')->all() as $detail) {
            $items[] = [
                'jenis' => 'tindakan',
                'referensi' => (string) $detail->id,
                'uraian' => 'Tindakan: ' . ($detail->tindakan === null ? '-' : $detail->tindakan->nama),
                'jumlah' => 1,
                'harga' => (float) $detail->tarif,
            ];
        }

        foreach (OrderLab::find()->where(['kunjungan_id' => $idKunjungan])->with('jenisLab')->all() as $order) {
            $items[] = [
                'jenis' => 'lab',
                'referensi' => (string) $order->id,
                'uraian' => 'Lab: ' . ($order->jenisLab === null ? '-' : $order->jenisLab->nama),
                'jumlah' => 1,
                'harga' => (float) $order->tarif,
            ];
        }

        $resep = Resep::find()->where(['kunjungan_id' => $idKunjungan])->one();

        if ($resep !== null) {
            foreach (ResepObat::find()->where(['resep_id' => $resep->id])->with('obat')->all() as $item) {
                $obat = $item->obat;

                $items[] = [
                    'jenis' => 'obat',
                    'referensi' => (string) $item->id,
                    'uraian' => 'Obat: ' . ($obat === null ? '-' : $obat->nama_obat),
                    'jumlah' => (int) $item->jumlah,
                    'harga' => $obat === null ? 0.0 : (float) $obat->harga_jual,
                ];
            }
        }

        return $items;
    }

    private function dibayar(string $tagihanId): float
    {
        return (float) Yii::$app->db->createCommand(
            'SELECT COALESCE(SUM(nominal), 0) FROM pembayaran WHERE tagihan_id = :tagihan',
            [':tagihan' => $tagihanId],
        )->queryScalar();
    }

    private function sisa(Tagihan $tagihan): float
    {
        return max(0, (float) $tagihan->total - $this->dibayar((string) $tagihan->id));
    }

    private function cari(string $id): Tagihan
    {
        $tagihan = Tagihan::find()->where(['id' => $id])->with(['kunjungan.pasien', 'kunjungan.poli', 'kunjungan.dokter', 'items', 'pembayaran'])->one();

        if ($tagihan === null) {
            throw new NotFoundHttpException('Tagihan tidak ditemukan');
        }

        return $tagihan;
    }

    private function ringkas(Tagihan $t): array
    {
        $kunjungan = $t->kunjungan;
        $dibayar = $this->dibayar((string) $t->id);

        return [
            'id' => (string) $t->id,
            'no_tagihan' => $t->no_tagihan,
            'status' => $t->status,
            'total' => (float) $t->total,
            'dibayar' => $dibayar,
            'sisa' => max(0, (float) $t->total - $dibayar),
            'kunjungan' => $kunjungan === null ? null : Kunjungan::ringkas($kunjungan),
            'items' => array_map(fn (TagihanItem $i) => [
                'id' => (string) $i->id,
                'jenis' => $i->jenis,
                'uraian' => $i->uraian,
                'jumlah' => (int) $i->jumlah,
                'harga' => (float) $i->harga,
            ], $t->items),
            'pembayaran' => array_map(fn (Pembayaran $p) => [
                'id' => (string) $p->id,
                'metode' => $p->metode,
                'nominal' => (float) $p->nominal,
                'waktu' => $p->waktu,
            ], $t->pembayaran),
        ];
    }
}
