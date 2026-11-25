<?php

declare(strict_types=1);

namespace app\modules\api\controllers;

use app\models\JadwalPraktek;
use app\models\Kunjungan;
use app\models\Pasien;
use app\models\Poli;
use Yii;
use yii\db\IntegrityException;
use yii\web\NotFoundHttpException;

class KunjunganController extends ApiController
{
    protected function verbs(): array
    {
        return [
            'index' => ['GET'],
            'view' => ['GET'],
            'jadwal-tersedia' => ['GET'],
            'create' => ['POST'],
            'batal' => ['PUT'],
        ];
    }

    public function actionIndex(): array
    {
        $this->butuh(['pendaftaran.kunjungan', 'pemeriksaan.daftar', 'kasir.tagihan'], 'view');

        $request = Yii::$app->request;
        $tanggal = trim((string) $request->get('tanggal', ''));
        $poliId = trim((string) $request->get('poli_id', ''));
        $status = trim((string) $request->get('status', ''));
        $halaman = max(1, (int) $request->get('page', '1'));
        $perHalaman = min(50, max(1, (int) $request->get('per', '15')));

        $query = Kunjungan::find()->with(['pasien', 'dokter', 'poli']);

        if ($tanggal !== '') {
            $query->andWhere(['tgl_kunjungan' => $tanggal]);
        }

        if ($poliId !== '') {
            $query->andWhere(['poli_id' => $poliId]);
        }

        if ($status !== '') {
            $query->andWhere(['status' => $status]);
        }

        $total = (int) (clone $query)->count();
        $baris = $query
            ->orderBy(['tgl_kunjungan' => SORT_DESC, 'no_antrean' => SORT_ASC])
            ->offset(($halaman - 1) * $perHalaman)
            ->limit($perHalaman)
            ->all();

        return $this->sukses([
            'items' => array_map(fn (Kunjungan $k) => $this->ringkas($k), $baris),
            'total' => $total,
            'page' => $halaman,
            'per' => $perHalaman,
        ]);
    }

    public function actionView(string $id): array
    {
        $this->butuh(['pendaftaran.kunjungan', 'pemeriksaan.daftar'], 'view');

        return $this->sukses($this->ringkas($this->cari($id)));
    }

    public function actionJadwalTersedia(): array
    {
        $this->butuh('pendaftaran.kunjungan', 'view');

        $poliId = trim((string) Yii::$app->request->get('poli_id', ''));
        $tanggal = trim((string) Yii::$app->request->get('tanggal', ''));

        if ($poliId === '' || !$this->tanggalValid($tanggal)) {
            return $this->gagal('Poli dan tanggal wajib diisi dengan benar', 422);
        }

        $jadwals = JadwalPraktek::find()
            ->where([
                'poli_id' => $poliId,
                'hari' => $this->hariDariTanggal($tanggal),
                'is_active' => true,
            ])
            ->with('karyawan')
            ->orderBy(['jam_mulai' => SORT_ASC])
            ->all();

        $terpakai = [];

        if ($jadwals !== []) {
            $baris = Kunjungan::find()
                ->select(['jadwal_id', 'jumlah' => 'COUNT(*)'])
                ->where(['jadwal_id' => array_map(fn (JadwalPraktek $j) => (string) $j->id, $jadwals)])
                ->andWhere(['tgl_kunjungan' => $tanggal])
                ->andWhere(['!=', 'status', Kunjungan::STATUS_BATAL])
                ->groupBy('jadwal_id')
                ->asArray()
                ->all();

            foreach ($baris as $b) {
                $terpakai[(string) $b['jadwal_id']] = (int) $b['jumlah'];
            }
        }

        return $this->sukses([
            'tanggal' => $tanggal,
            'items' => array_map(function (JadwalPraktek $jadwal) use ($terpakai): array {
                $terisi = $terpakai[(string) $jadwal->id] ?? 0;

                return [
                    'id' => (string) $jadwal->id,
                    'dokter' => $jadwal->karyawan === null
                        ? null
                        : ['id' => (string) $jadwal->karyawan->id, 'name' => $jadwal->karyawan->nama_karyawan],
                    'jam_mulai' => $jadwal->jam_mulai,
                    'jam_selesai' => $jadwal->jam_selesai,
                    'kuota' => (int) $jadwal->kuota,
                    'terisi' => $terisi,
                    'sisa' => max(0, (int) $jadwal->kuota - $terisi),
                ];
            }, $jadwals),
        ]);
    }

    public function actionCreate(): array
    {
        $this->butuh('pendaftaran.kunjungan', 'create');

        $body = Yii::$app->request->getBodyParams();
        $key = trim((string) ($body['idempotency_key'] ?? ''));

        if ($key === '' || mb_strlen($key) > 64) {
            return $this->gagal('Idempotency key wajib diisi maksimal 64 karakter', 422);
        }

        $sudah = Kunjungan::find()->where(['idempotency_key' => $key])->one();

        if ($sudah !== null) {
            return $this->sukses($this->ringkas($sudah), 200, 'Kunjungan sudah terdaftar sebelumnya.');
        }

        $jadwal = JadwalPraktek::find()
            ->where(['id' => trim((string) ($body['jadwal_id'] ?? ''))])
            ->with(['karyawan', 'poli'])
            ->one();

        if ($jadwal === null) {
            return $this->gagal('Jadwal tidak ditemukan', 422);
        }

        if (!$jadwal->is_active) {
            return $this->gagal('Jadwal praktek tidak aktif', 422);
        }

        $dokterId = (string) $jadwal->karyawan_id;
        $poliId = (string) $jadwal->poli_id;

        $pasien = Pasien::find()
            ->where(['id' => trim((string) ($body['pasien_id'] ?? ''))])
            ->one();

        if ($pasien === null) {
            return $this->gagal('Pasien tidak ditemukan', 422);
        }

        $tanggal = trim((string) ($body['tgl_kunjungan'] ?? ''));

        if (!$this->tanggalValid($tanggal)) {
            return $this->gagal('Tanggal kunjungan tidak valid', 422);
        }

        $hari = $this->hariDariTanggal($tanggal);

        if ($hari !== (int) $jadwal->hari) {
            return $this->gagal('Tanggal kunjungan bukan hari praktek jadwal ini', 422);
        }

        $poli = Poli::findOne($poliId);

        if ($poli === null || !$poli->is_active) {
            return $this->gagal('Poli tidak ditemukan atau tidak aktif', 422);
        }

        $kunjungan = new Kunjungan([
            'pasien_id' => (string) $pasien->id,
            'dokter_id' => $dokterId,
            'poli_id' => $poliId,
            'jadwal_id' => (string) $jadwal->id,
            'tgl_kunjungan' => $tanggal,
            'status' => Kunjungan::STATUS_MENUNGGU,
            'biaya_daftar' => (float) $poli->biaya_daftar,
            'idempotency_key' => $key,
        ]);

        $transaksi = Yii::$app->db->beginTransaction();

        try {
            Yii::$app->db->createCommand(
                'SELECT id FROM jadwal_praktek WHERE id = :id FOR UPDATE',
                [':id' => (string) $jadwal->id],
            )->queryScalar();

            $duel = Kunjungan::find()
                ->where([
                    'pasien_id' => $kunjungan->pasien_id,
                    'jadwal_id' => $kunjungan->jadwal_id,
                    'tgl_kunjungan' => $tanggal,
                ])
                ->andWhere(['!=', 'status', Kunjungan::STATUS_BATAL])
                ->exists();

            if ($duel) {
                $transaksi->rollBack();

                return $this->gagal('Pasien ini sudah terdaftar pada jadwal dan tanggal yang sama', 422);
            }

            $terisi = (int) Kunjungan::find()
                ->where(['jadwal_id' => $kunjungan->jadwal_id, 'tgl_kunjungan' => $tanggal])
                ->andWhere(['!=', 'status', Kunjungan::STATUS_BATAL])
                ->count();

            if ($terisi >= (int) $jadwal->kuota) {
                $transaksi->rollBack();

                return $this->gagal('Kuota jadwal praktek sudah penuh', 422);
            }

            $kunjungan->no_antrean = (int) Yii::$app->db->createCommand(
                'INSERT INTO counter_antrean (poli_id, tanggal, nomor_terakhir)
                 VALUES (:poli, :tanggal, 1)
                 ON CONFLICT (poli_id, tanggal)
                 DO UPDATE SET nomor_terakhir = counter_antrean.nomor_terakhir + 1,
                               update_date = (now() AT TIME ZONE \'Asia/Jakarta\')
                 RETURNING nomor_terakhir',
                [':poli' => $poliId, ':tanggal' => $tanggal],
            )->queryScalar();

            $noKunjungan = (int) Yii::$app->db->createCommand('SELECT nextval(\'seq_kunjungan\')')->queryScalar();
            $kunjungan->no_kunjungan = sprintf(
                'KJ-%s-%04d',
                str_replace('-', '', $tanggal),
                $noKunjungan,
            );

            if (!$kunjungan->save(false)) {
                throw new \RuntimeException('gagal menyimpan kunjungan');
            }

            $transaksi->commit();
        } catch (IntegrityException $e) {
            $transaksi->rollBack();

            // Dua request dengan idempotency key yang sama bisa lolos
            // pemeriksaan awal bersamaan; kembalikan yang sudah masuk.
            $sudah = Kunjungan::find()->where(['idempotency_key' => $key])->one();

            if ($sudah !== null) {
                return $this->sukses($this->ringkas($sudah), 200, 'Kunjungan sudah terdaftar sebelumnya.');
            }

            $duel = Kunjungan::find()
                ->where([
                    'pasien_id' => $kunjungan->pasien_id,
                    'jadwal_id' => $kunjungan->jadwal_id,
                    'tgl_kunjungan' => $kunjungan->tgl_kunjungan,
                ])
                ->andWhere(['!=', 'status', Kunjungan::STATUS_BATAL])
                ->exists();

            if ($duel) {
                return $this->gagal('Pasien ini sudah terdaftar pada jadwal dan tanggal yang sama', 422);
            }

            throw $e;
        } catch (\Throwable $e) {
            $transaksi->rollBack();

            throw $e;
        }

        return $this->sukses($this->ringkas($kunjungan), 201, 'Kunjungan ditambahkan.');
    }

    public function actionBatal(string $id): array
    {
        $this->butuh('pendaftaran.kunjungan', 'update');

        $kunjungan = $this->cari($id);

        if ($kunjungan->status !== Kunjungan::STATUS_MENUNGGU) {
            return $this->gagal('Hanya kunjungan berstatus menunggu yang bisa dibatalkan', 422);
        }

        Yii::$app->db->createCommand(
            'SELECT fn_ubah_status_kunjungan(:id, :status)',
            [':id' => (string) $kunjungan->id, ':status' => Kunjungan::STATUS_BATAL],
        )->execute();

        return $this->sukses($this->ringkas($this->cari($id)), 200, 'Kunjungan dibatalkan.');
    }

    private function cari(string $id): Kunjungan
    {
        $kunjungan = Kunjungan::cariLengkap($id);

        if ($kunjungan === null) {
            throw new NotFoundHttpException('Kunjungan tidak ditemukan');
        }

        return $kunjungan;
    }

    private function ringkas(Kunjungan $k): array
    {
        return Kunjungan::ringkas($k);
    }

    private function tanggalValid(string $tanggal): bool
    {
        $pecah = explode('-', $tanggal);

        return count($pecah) === 3 && checkdate((int) $pecah[1], (int) $pecah[2], (int) $pecah[0]);
    }

    private function hariDariTanggal(string $tanggal): int
    {
        $pecah = explode('-', $tanggal);

        return (int) date('N', mktime(0, 0, 0, (int) $pecah[1], (int) $pecah[2], (int) $pecah[0]));
    }
}
