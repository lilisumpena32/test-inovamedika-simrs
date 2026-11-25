<?php

declare(strict_types=1);

namespace app\modules\api\controllers;

use app\models\Kunjungan;
use app\models\OrderLab;
use app\models\OutboxLab;
use Yii;
use yii\web\NotFoundHttpException;

class LabController extends ApiController
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        $behaviors['jwtAuth']['except'] = ['pull-hasil'];

        return $behaviors;
    }

    protected function verbs(): array
    {
        return [
            'index' => ['GET'],
            'hasil' => ['PUT'],
            'pull-hasil' => ['POST'],
        ];
    }

    public function actionIndex(): array
    {
        $this->butuh('laboratorium.order', 'view');

        $request = Yii::$app->request;
        $tanggal = trim((string) $request->get('tanggal', ''));
        $status = trim((string) $request->get('status', ''));

        $query = OrderLab::find()
            ->with(['jenisLab', 'kunjungan.pasien', 'kunjungan.poli']);

        if ($tanggal !== '') {
            $query->joinWith('kunjungan k', false)->andWhere(['k.tgl_kunjungan' => $tanggal]);
        }

        if ($status !== '') {
            $query->andWhere(['{{%order_lab}}.status' => $status]);
        }

        $baris = $query->orderBy(['k.tgl_kunjungan' => SORT_DESC, '{{%order_lab}}.create_date' => SORT_ASC])->all();

        return $this->sukses([
            'items' => array_map(fn (OrderLab $o) => $this->ringkas($o), $baris),
        ]);
    }

    public function actionHasil(string $id): array
    {
        $this->butuh('laboratorium.order', 'update');

        $order = $this->cari($id);

        if ($order->status !== OrderLab::STATUS_DIMINTA) {
            return $this->gagal('Hanya order berstatus diminta yang bisa diisi hasilnya', 422);
        }

        $body = Yii::$app->request->getBodyParams();
        $hasil = trim((string) ($body['hasil'] ?? ''));

        if ($hasil === '' || mb_strlen($hasil) > 2000) {
            return $this->gagal('Hasil pemeriksaan wajib diisi maksimal 2000 karakter', 422);
        }

        $order->hasil = $hasil;
        $order->status = OrderLab::STATUS_SELESAI;
        $order->waktu_hasil = date('Y-m-d H:i:s');
        $order->save(false);

        return $this->sukses($this->ringkas($order), 200, 'Hasil lab disimpan.');
    }

    public function actionPullHasil(): array
    {
        $apiKey = (string) (Yii::$app->params['labApiKey'] ?? '');

        if ($apiKey === '' || !hash_equals($apiKey, (string) Yii::$app->request->headers->get('X-Api-Key', ''))) {
            return $this->gagal('API key tidak valid', 401);
        }

        $baris = OutboxLab::find()->where(['status' => OutboxLab::STATUS_SENT])->with('orderLab')->all();
        $diterapkan = 0;

        foreach ($baris as $outbox) {
            /** @var OutboxLab $outbox */
            $order = $outbox->orderLab;

            if ($order === null || $order->status !== OrderLab::STATUS_DIMINTA) {
                $outbox->status = OutboxLab::STATUS_APPLIED;
                $outbox->save(false);

                continue;
            }

            $order->hasil = $outbox->hasil;
            $order->status = OrderLab::STATUS_SELESAI;
            $order->waktu_hasil = date('Y-m-d H:i:s');
            $order->save(false);

            $outbox->status = OutboxLab::STATUS_APPLIED;
            $outbox->save(false);

            $diterapkan++;
        }

        return $this->sukses(['diterapkan' => $diterapkan]);
    }

    private function cari(string $id): OrderLab
    {
        $order = OrderLab::find()->where(['id' => $id])->with(['jenisLab', 'kunjungan.pasien'])->one();

        if ($order === null) {
            throw new NotFoundHttpException('Order lab tidak ditemukan');
        }

        return $order;
    }

    private function ringkas(OrderLab $o): array
    {
        $kunjungan = $o->kunjungan;

        return [
            'id' => (string) $o->id,
            'kunjungan_id' => (string) $o->kunjungan_id,
            'kunjungan' => $kunjungan === null
                ? null
                : [
                    'no_kunjungan' => $kunjungan->no_kunjungan,
                    'tgl_kunjungan' => $kunjungan->tgl_kunjungan,
                    'pasien' => $kunjungan->pasien === null
                        ? null
                        : ['no_rm' => $kunjungan->pasien->no_rm, 'nama' => $kunjungan->pasien->nama],
                    'poli' => $kunjungan->poli === null ? null : $kunjungan->poli->nama,
                ],
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
