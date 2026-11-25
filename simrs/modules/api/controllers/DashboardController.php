<?php

declare(strict_types=1);

namespace app\modules\api\controllers;

use Yii;

class DashboardController extends ApiController
{
    protected function verbs(): array
    {
        return [
            'ringkasan' => ['GET'],
            'pendapatan' => ['GET'],
            'obat' => ['GET'],
        ];
    }

    private function periode(): array
    {
        $dari = trim((string) Yii::$app->request->get('dari', ''));
        $sampai = trim((string) Yii::$app->request->get('sampai', ''));

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dari) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $sampai)) {
            return [];
        }

        return [$dari, $sampai];
    }

    public function actionRingkasan(): array
    {
        $this->butuh('laporan.operasional', 'view');

        $periode = $this->periode();

        if ($periode === []) {
            return $this->gagal('Parameter dari dan sampai wajib format YYYY-MM-DD', 422);
        }

        [$dari, $sampai] = $periode;

        $perPoli = Yii::$app->db->createCommand(<<<'SQL'
            SELECT po.nama AS poli, COUNT(*) AS jumlah
            FROM kunjungan k
            JOIN poli po ON po.id = k.poli_id
            WHERE k.tgl_kunjungan BETWEEN :dari AND :sampai AND k.status <> 'batal'
            GROUP BY po.nama
            ORDER BY po.nama
        SQL, [':dari' => $dari, ':sampai' => $sampai])->queryAll();

        $tindakan = (int) Yii::$app->db->createCommand(<<<'SQL'
            SELECT COUNT(*) FROM pemeriksaan_tindakan pt
            JOIN kunjungan k ON k.id = pt.kunjungan_id
            WHERE k.tgl_kunjungan BETWEEN :dari AND :sampai AND k.status <> 'batal'
        SQL, [':dari' => $dari, ':sampai' => $sampai])->queryScalar();

        $status = Yii::$app->db->createCommand(<<<'SQL'
            SELECT t.status, COUNT(*) AS jumlah
            FROM tagihan t
            JOIN kunjungan k ON k.id = t.kunjungan_id
            WHERE k.tgl_kunjungan BETWEEN :dari AND :sampai
            GROUP BY t.status
        SQL, [':dari' => $dari, ':sampai' => $sampai])->queryAll();

        $total = (int) Yii::$app->db->createCommand(<<<'SQL'
            SELECT COUNT(*) FROM kunjungan
            WHERE tgl_kunjungan BETWEEN :dari AND :sampai AND status <> 'batal'
        SQL, [':dari' => $dari, ':sampai' => $sampai])->queryScalar();

        return $this->sukses([
            'periode' => ['dari' => $dari, 'sampai' => $sampai],
            'kunjungan_total' => $total,
            'kunjungan_per_poli' => $perPoli,
            'tindakan_total' => $tindakan,
            'status_pembayaran' => $status,
        ]);
    }

    public function actionPendapatan(): array
    {
        $this->butuh('laporan.operasional', 'view');

        $periode = $this->periode();

        if ($periode === []) {
            return $this->gagal('Parameter dari dan sampai wajib format YYYY-MM-DD', 422);
        }

        [$dari, $sampai] = $periode;

        $perMetode = Yii::$app->db->createCommand(<<<'SQL'
            SELECT p.metode, COUNT(*) AS jumlah, COALESCE(SUM(p.nominal), 0) AS nominal
            FROM pembayaran p
            JOIN tagihan t ON t.id = p.tagihan_id
            JOIN kunjungan k ON k.id = t.kunjungan_id
            WHERE k.tgl_kunjungan BETWEEN :dari AND :sampai
            GROUP BY p.metode
        SQL, [':dari' => $dari, ':sampai' => $sampai])->queryAll();

        $perHari = Yii::$app->db->createCommand(<<<'SQL'
            SELECT k.tgl_kunjungan AS tanggal, COALESCE(SUM(p.nominal), 0) AS nominal
            FROM pembayaran p
            JOIN tagihan t ON t.id = p.tagihan_id
            JOIN kunjungan k ON k.id = t.kunjungan_id
            WHERE k.tgl_kunjungan BETWEEN :dari AND :sampai
            GROUP BY k.tgl_kunjungan
            ORDER BY k.tgl_kunjungan
        SQL, [':dari' => $dari, ':sampai' => $sampai])->queryAll();

        $total = (float) Yii::$app->db->createCommand(<<<'SQL'
            SELECT COALESCE(SUM(p.nominal), 0)
            FROM pembayaran p
            JOIN tagihan t ON t.id = p.tagihan_id
            JOIN kunjungan k ON k.id = t.kunjungan_id
            WHERE k.tgl_kunjungan BETWEEN :dari AND :sampai
        SQL, [':dari' => $dari, ':sampai' => $sampai])->queryScalar();

        return $this->sukses([
            'periode' => ['dari' => $dari, 'sampai' => $sampai],
            'total' => $total,
            'per_metode' => $perMetode,
            'per_hari' => $perHari,
        ]);
    }

    public function actionObat(): array
    {
        $this->butuh('laporan.operasional', 'view');

        $periode = $this->periode();

        if ($periode === []) {
            return $this->gagal('Parameter dari dan sampai wajib format YYYY-MM-DD', 422);
        }

        [$dari, $sampai] = $periode;

        $baris = Yii::$app->db->createCommand(<<<'SQL'
            SELECT o.nama_obat, COALESCE(SUM(ro.jumlah), 0) AS jumlah
            FROM resep_obat ro
            JOIN obat o ON o.id = ro.obat_id
            JOIN resep r ON r.id = ro.resep_id
            JOIN kunjungan k ON k.id = r.kunjungan_id
            WHERE k.tgl_kunjungan BETWEEN :dari AND :sampai
            GROUP BY o.nama_obat
            ORDER BY 2 DESC
        SQL, [':dari' => $dari, ':sampai' => $sampai])->queryAll();

        return $this->sukses([
            'periode' => ['dari' => $dari, 'sampai' => $sampai],
            'items' => $baris,
        ]);
    }
}
