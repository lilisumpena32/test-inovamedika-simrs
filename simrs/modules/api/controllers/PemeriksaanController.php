<?php

declare(strict_types=1);

namespace app\modules\api\controllers;

use app\models\JenisLab;
use app\models\Kunjungan;
use app\models\OrderLab;
use app\models\OutboxLab;
use app\models\Pemeriksaan;
use app\models\PemeriksaanTindakan;
use app\models\Tindakan;
use Yii;
use yii\db\Transaction;
use yii\web\NotFoundHttpException;

class PemeriksaanController extends ApiController
{
    protected function verbs(): array
    {
        return [
            'lihat' => ['GET'],
            'simpan' => ['POST'],
            'tindakan' => ['GET'],
            'tambah-tindakan' => ['POST'],
            'hapus-tindakan' => ['DELETE'],
            'lab' => ['GET'],
            'tambah-lab' => ['POST'],
            'selesai' => ['PUT'],
        ];
    }

    public function actionSimpan(string $id): array
    {
        $this->butuh('pemeriksaan.daftar', 'update');

        $kunjungan = $this->cariKunjungan($id);

        if (!in_array($kunjungan->status, [Kunjungan::STATUS_MENUNGGU, Kunjungan::STATUS_DIPERIKSA], true)) {
            return $this->gagal('Kunjungan tidak sedang menunggu atau dalam pemeriksaan', 422);
        }

        $body = Yii::$app->request->getBodyParams();

        $pemeriksaan = Pemeriksaan::find()->where(['kunjungan_id' => $kunjungan->id])->one();

        if ($pemeriksaan === null) {
            $pemeriksaan = new Pemeriksaan(['kunjungan_id' => (string) $kunjungan->id]);
        }

        $pemeriksaan->keluhan = $this->teks($body, 'keluhan', 1000);
        $pemeriksaan->anamnesis = $this->teks($body, 'anamnesis', 2000);
        $pemeriksaan->tensi = $this->teks($body, 'tensi', 10);
        $suhu = $body['suhu'] ?? null;
        $pemeriksaan->suhu = $suhu === null || $suhu === '' ? null : (float) $suhu;        $pemeriksaan->nadi = $this->angka($body, 'nadi');
        $pemeriksaan->pernapasan = $this->angka($body, 'pernapasan');
        $pemeriksaan->tinggi_cm = $this->angka($body, 'tinggi_cm');
        $pemeriksaan->berat_kg = $this->angka($body, 'berat_kg');
        $pemeriksaan->hasil_fisik = $this->teks($body, 'hasil_fisik', 2000);
        $pemeriksaan->diagnosis_utama = trim((string) ($body['diagnosis_utama'] ?? ''));
        $pemeriksaan->diagnosis_sekunder = $this->teks($body, 'diagnosis_sekunder', 128);

        if ($pemeriksaan->diagnosis_utama === '' || mb_strlen($pemeriksaan->diagnosis_utama) > 128) {
            return $this->gagal('Diagnosis utama wajib diisi maksimal 128 karakter', 422);
        }

        $transaksi = Yii::$app->db->beginTransaction();

        try {
            if (!$pemeriksaan->save(false)) {
                throw new \RuntimeException('gagal menyimpan pemeriksaan');
            }

            if ($kunjungan->status === Kunjungan::STATUS_MENUNGGU) {
                Yii::$app->db->createCommand(
                    'SELECT fn_ubah_status_kunjungan(:id, :status)',
                    [':id' => (string) $kunjungan->id, ':status' => Kunjungan::STATUS_DIPERIKSA],
                )->execute();
            }

            $transaksi->commit();
        } catch (\Throwable $e) {
            $transaksi->rollBack();

            throw $e;
        }

        return $this->sukses($this->ringkas($pemeriksaan), 200, 'Pemeriksaan disimpan.');
    }

    public function actionLihat(string $id): array
    {
        $this->butuh('pemeriksaan.daftar', 'view');

        $pemeriksaan = Pemeriksaan::find()->where(['kunjungan_id' => $id])->one();

        return $this->sukses($pemeriksaan === null ? null : $this->ringkas($pemeriksaan));
    }

    public function actionTindakan(string $id): array
    {
        $this->butuh('pemeriksaan.daftar', 'view');

        $baris = PemeriksaanTindakan::find()
            ->where(['kunjungan_id' => $id])
            ->with('tindakan')
            ->orderBy('create_date')
            ->all();

        return $this->sukses(['items' => array_map(fn (PemeriksaanTindakan $t) => $this->ringkasTindakan($t), $baris)]);
    }

    public function actionTambahTindakan(string $id): array
    {
        $this->butuh('pemeriksaan.daftar', 'update');

        $kunjungan = $this->cariKunjungan($id);
        $galat = $this->galatStatusTransaksi($kunjungan);
        if ($galat !== null) {
            return $this->gagal($galat, 422);
        }

        $tindakan = Tindakan::findOne(trim((string) (Yii::$app->request->getBodyParams()['tindakan_id'] ?? '')));

        if ($tindakan === null || !$tindakan->is_active) {
            return $this->gagal('Tindakan tidak ditemukan atau tidak aktif', 422);
        }

        if (PemeriksaanTindakan::find()
            ->where(['kunjungan_id' => $kunjungan->id, 'tindakan_id' => $tindakan->id])
            ->exists()) {
            return $this->gagal('Tindakan ini sudah ditambahkan pada kunjungan', 422);
        }

        $detail = new PemeriksaanTindakan([
            'kunjungan_id' => (string) $kunjungan->id,
            'tindakan_id' => (string) $tindakan->id,
            'tarif' => (float) $tindakan->tarif,
        ]);
        $detail->save(false);

        return $this->sukses($this->ringkasTindakan($detail), 201, 'Tindakan ditambahkan.');
    }

    public function actionHapusTindakan(string $id, string $detailId): array
    {
        $this->butuh('pemeriksaan.daftar', 'update');

        $kunjungan = $this->cariKunjungan($id);
        $galat = $this->galatStatusTransaksi($kunjungan);
        if ($galat !== null) {
            return $this->gagal($galat, 422);
        }

        $detail = PemeriksaanTindakan::find()
            ->where(['id' => $detailId, 'kunjungan_id' => $kunjungan->id])
            ->one();

        if ($detail === null) {
            throw new NotFoundHttpException('Tindakan tidak ditemukan pada kunjungan ini');
        }

        $detail->delete();

        return $this->sukses(null, 200, 'Tindakan dibatalkan.');
    }

    public function actionLab(string $id): array
    {
        $this->butuh('pemeriksaan.daftar', 'view');

        $baris = OrderLab::find()
            ->where(['kunjungan_id' => $id])
            ->with('jenisLab')
            ->orderBy('create_date')
            ->all();

        return $this->sukses(['items' => array_map(fn (OrderLab $o) => $this->ringkasLab($o), $baris)]);
    }

    public function actionTambahLab(string $id): array
    {
        $this->butuh('pemeriksaan.daftar', 'update');

        $kunjungan = $this->cariKunjungan($id);
        $galat = $this->galatStatusTransaksi($kunjungan);
        if ($galat !== null) {
            return $this->gagal($galat, 422);
        }

        $jenisLab = JenisLab::findOne(trim((string) (Yii::$app->request->getBodyParams()['jenis_lab_id'] ?? '')));

        if ($jenisLab === null || !$jenisLab->is_active) {
            return $this->gagal('Jenis lab tidak ditemukan atau tidak aktif', 422);
        }

        if (OrderLab::find()
            ->where(['kunjungan_id' => $kunjungan->id, 'jenis_lab_id' => $jenisLab->id])
            ->andWhere(['!=', 'status', OrderLab::STATUS_BATAL])
            ->exists()) {
            return $this->gagal('Pemeriksaan lab ini sudah diminta pada kunjungan', 422);
        }

        $order = new OrderLab([
            'kunjungan_id' => (string) $kunjungan->id,
            'jenis_lab_id' => (string) $jenisLab->id,
            'tarif' => (float) $jenisLab->tarif,
            'status' => OrderLab::STATUS_DIMINTA,
        ]);
        $order->save(false);

        $outbox = new OutboxLab([
            'order_lab_id' => (string) $order->id,
            'payload' => [
                'order_id' => (string) $order->id,
                'kode' => $kunjungan->no_kunjungan,
                'jenis' => $jenisLab->nama,
                'pasien' => $kunjungan->pasien === null ? '-' : $kunjungan->pasien->nama,
            ],
            'status' => OutboxLab::STATUS_PENDING,
        ]);
        $outbox->save(false);

        return $this->sukses($this->ringkasLab($order), 201, 'Order lab ditambahkan.');
    }

    public function actionSelesai(string $id): array
    {
        $this->butuh('pemeriksaan.daftar', 'update');

        $kunjungan = $this->cariKunjungan($id);

        if ($kunjungan->status !== Kunjungan::STATUS_DIPERIKSA) {
            return $this->gagal('Hanya kunjungan berstatus diperiksa yang bisa ditutup', 422);
        }

        Yii::$app->db->createCommand(
            'SELECT fn_ubah_status_kunjungan(:id, :status)',
            [':id' => (string) $kunjungan->id, ':status' => Kunjungan::STATUS_MENUNGGU_BAYAR],
        )->execute();

        return $this->sukses(Kunjungan::ringkas($this->cariKunjungan($id)), 200, 'Pemeriksaan selesai, menunggu pembayaran.');
    }

    private function cariKunjungan(string $id): Kunjungan
    {
        $kunjungan = Kunjungan::cariLengkap($id);

        if ($kunjungan === null) {
            throw new NotFoundHttpException('Kunjungan tidak ditemukan');
        }

        return $kunjungan;
    }

    private function galatStatusTransaksi(Kunjungan $kunjungan): ?string
    {
        if (!in_array($kunjungan->status, [
            Kunjungan::STATUS_MENUNGGU,
            Kunjungan::STATUS_DIPERIKSA,
            Kunjungan::STATUS_MENUNGGU_OBAT,
        ], true)) {
            return 'Kunjungan tidak bisa diubah lagi karena statusnya ' . $kunjungan->status;
        }

        return null;
    }

    private function ringkas(Pemeriksaan $p): array
    {
        return [
            'id' => (string) $p->id,
            'kunjungan_id' => (string) $p->kunjungan_id,
            'keluhan' => $p->keluhan,
            'anamnesis' => $p->anamnesis,
            'tensi' => $p->tensi,
            'suhu' => $p->suhu === null ? null : (float) $p->suhu,
            'nadi' => $p->nadi === null ? null : (int) $p->nadi,
            'pernapasan' => $p->pernapasan === null ? null : (int) $p->pernapasan,
            'tinggi_cm' => $p->tinggi_cm === null ? null : (int) $p->tinggi_cm,
            'berat_kg' => $p->berat_kg === null ? null : (int) $p->berat_kg,
            'hasil_fisik' => $p->hasil_fisik,
            'diagnosis_utama' => $p->diagnosis_utama,
            'diagnosis_sekunder' => $p->diagnosis_sekunder,
        ];
    }

    private function ringkasTindakan(PemeriksaanTindakan $t): array
    {
        return [
            'id' => (string) $t->id,
            'kunjungan_id' => (string) $t->kunjungan_id,
            'tindakan' => $t->tindakan === null
                ? null
                : ['id' => (string) $t->tindakan->id, 'kode' => $t->tindakan->kode_tindakan, 'nama' => $t->tindakan->nama],
            'tarif' => (float) $t->tarif,
        ];
    }

    private function ringkasLab(OrderLab $o): array
    {
        return [
            'id' => (string) $o->id,
            'kunjungan_id' => (string) $o->kunjungan_id,
            'jenis_lab' => $o->jenisLab === null
                ? null
                : ['id' => (string) $o->jenisLab->id, 'kode' => $o->jenisLab->kode_jenis_lab, 'nama' => $o->jenisLab->nama],
            'tarif' => (float) $o->tarif,
            'status' => $o->status,
            'hasil' => $o->hasil,
            'waktu_hasil' => $o->waktu_hasil,
        ];
    }
}
