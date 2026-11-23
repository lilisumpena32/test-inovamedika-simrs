<?php

declare(strict_types=1);

namespace app\modules\api\controllers;

use app\models\JadwalPraktek;
use app\models\Karyawan;
use app\models\Poli;
use Yii;
use yii\web\NotFoundHttpException;

class JadwalController extends ApiController
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
        $this->butuh('master.jadwal', 'view');

        $request = Yii::$app->request;
        $karyawanId = trim((string) $request->get('karyawan_id', ''));
        $poliId = trim((string) $request->get('poli_id', ''));

        $query = JadwalPraktek::find()->with(['karyawan', 'poli']);

        if ($karyawanId !== '') {
            $query->andWhere(['karyawan_id' => $karyawanId]);
        }

        if ($poliId !== '') {
            $query->andWhere(['poli_id' => $poliId]);
        }

        $baris = $query->orderBy(['hari' => SORT_ASC, 'jam_mulai' => SORT_ASC])->all();

        return $this->sukses(['items' => array_map(fn (JadwalPraktek $j) => $this->ringkas($j), $baris)]);
    }

    public function actionView(string $id): array
    {
        $this->butuh('master.jadwal', 'view');

        return $this->sukses($this->ringkas($this->cari($id)));
    }

    public function actionCreate(): array
    {
        $this->butuh('master.jadwal', 'create');

        $body = Yii::$app->request->getBodyParams();
        $karyawanId = trim((string) ($body['karyawan_id'] ?? ''));
        $poliId = trim((string) ($body['poli_id'] ?? ''));
        $sesi = $body['sesi'] ?? null;

        $karyawan = Karyawan::find()->where(['id' => $karyawanId])->with('jabatan')->one();
        if ($karyawan === null || !$karyawan->is_active) {
            return $this->gagal('Karyawan tidak ditemukan atau tidak aktif', 422);
        }

        if ($karyawan->jabatan === null || !$karyawan->jabatan->is_dokter) {
            return $this->gagal('Jadwal praktek hanya untuk karyawan berjabatan Dokter', 422);
        }

        $poli = Poli::find()->where(['id' => $poliId])->one();
        if ($poli === null || !$poli->is_active) {
            return $this->gagal('Poli tidak ditemukan atau tidak aktif', 422);
        }

        if (!is_array($sesi) || $sesi === []) {
            return $this->gagal('Minimal satu sesi jadwal', 422);
        }

        $daftar = [];
        foreach ($sesi as $i => $baris) {
            $urut = $i + 1;
            $hari = (int) ($baris['hari'] ?? 0);
            $mulai = (string) ($baris['jam_mulai'] ?? '');
            $selesai = (string) ($baris['jam_selesai'] ?? '');
            $kuota = (int) ($baris['kuota'] ?? 0);

            if ($hari < 1 || $hari > 7) {
                return $this->gagal("Sesi {$urut}: hari harus 1 (Senin) sampai 7 (Minggu)", 422);
            }

            if (!preg_match('/^\d{2}:\d{2}$/', $mulai) || !preg_match('/^\d{2}:\d{2}$/', $selesai)) {
                return $this->gagal("Sesi {$urut}: format jam harus HH:MM", 422);
            }

            if (strtotime($mulai) >= strtotime($selesai)) {
                return $this->gagal("Sesi {$urut}: jam mulai harus lebih awal dari jam selesai", 422);
            }

            if ($kuota < 1) {
                return $this->gagal("Sesi {$urut}: kuota minimal 1", 422);
            }

            foreach ($daftar as $ada) {
                if ($ada['hari'] === $hari && $ada['jam_mulai'] === $mulai) {
                    return $this->gagal('Ada sesi dengan hari dan jam mulai yang sama', 422);
                }

                if ($ada['hari'] === $hari && $this->jamOverlap($ada['jam_mulai'], $ada['jam_selesai'], $mulai, $selesai)) {
                    return $this->gagal('Ada sesi yang bertabrakan rentang jamnya', 422);
                }
            }

            $daftar[] = ['hari' => $hari, 'jam_mulai' => $mulai, 'jam_selesai' => $selesai, 'kuota' => $kuota];
        }

        foreach ($daftar as $baru) {
            foreach (JadwalPraktek::find()->where(['karyawan_id' => $karyawanId, 'hari' => $baru['hari']])->all() as $lama) {
                if ($this->jamOverlap((string) $lama->jam_mulai, (string) $lama->jam_selesai, $baru['jam_mulai'], $baru['jam_selesai'])) {
                    return $this->gagal('Jadwal bertabrakan dengan jadwal lain dokter ini', 422);
                }
            }
        }

        $hasil = [];
        $transaksi = Yii::$app->db->beginTransaction();
        try {
            foreach ($daftar as $b) {
                $jadwal = new JadwalPraktek([
                    'karyawan_id' => $karyawanId,
                    'poli_id' => $poliId,
                    'hari' => $b['hari'],
                    'jam_mulai' => $b['jam_mulai'],
                    'jam_selesai' => $b['jam_selesai'],
                    'kuota' => $b['kuota'],
                ]);
                $jadwal->is_active = true;

                if (!$jadwal->save(false)) {
                    throw new \RuntimeException('gagal menyimpan jadwal');
                }

                $hasil[] = $this->ringkas($jadwal);
            }

            $transaksi->commit();
        } catch (\Throwable $e) {
            $transaksi->rollBack();

            throw $e;
        }

        return $this->sukses(['items' => $hasil], 201, 'Jadwal ditambahkan.');
    }

    public function actionUpdate(string $id): array
    {
        $this->butuh('master.jadwal', 'update');

        $jadwal = $this->cari($id);
        $body = Yii::$app->request->getBodyParams();

        $jadwal->karyawan_id = trim((string) ($body['karyawan_id'] ?? ''));
        $jadwal->poli_id = trim((string) ($body['poli_id'] ?? ''));
        $jadwal->hari = (int) ($body['hari'] ?? 0);
        $jadwal->jam_mulai = (string) ($body['jam_mulai'] ?? '');
        $jadwal->jam_selesai = (string) ($body['jam_selesai'] ?? '');
        $jadwal->kuota = (int) ($body['kuota'] ?? 0);

        $galat = $this->validasi($jadwal, $jadwal->id);
        if ($galat !== null) {
            return $this->gagal($galat, 422);
        }

        $jadwal->save(false);

        return $this->sukses($this->ringkas($jadwal), 200, 'Jadwal diubah.');
    }

    public function actionStatus(string $id): array
    {
        $this->butuh('master.jadwal', 'update');

        $jadwal = $this->cari($id);
        $aktif = filter_var(
            Yii::$app->request->getBodyParams()['is_active'] ?? null,
            FILTER_VALIDATE_BOOL,
            FILTER_NULL_ON_FAILURE,
        );

        if ($aktif === null) {
            return $this->gagal('Status tidak valid', 422);
        }

        $jadwal->is_active = $aktif;
        $jadwal->save(false);

        return $this->sukses($this->ringkas($jadwal), 200, 'Status jadwal diubah.');
    }

    private function cari(string $id): JadwalPraktek
    {
        $jadwal = JadwalPraktek::find()->where(['id' => $id])->one();

        if ($jadwal === null) {
            throw new NotFoundHttpException('Jadwal tidak ditemukan');
        }

        return $jadwal;
    }

    private function ringkas(JadwalPraktek $j): array
    {
        return [
            'id' => (string) $j->id,
            'karyawan' => $j->karyawan === null
                ? null
                : ['id' => (string) $j->karyawan->id, 'kode' => $j->karyawan->kode_karyawan, 'name' => $j->karyawan->nama_karyawan],
            'poli' => $j->poli === null
                ? null
                : ['id' => (string) $j->poli->id, 'kode' => $j->poli->kode, 'name' => $j->poli->nama],
            'hari' => (int) $j->hari,
            'jam_mulai' => substr((string) $j->jam_mulai, 0, 5),
            'jam_selesai' => substr((string) $j->jam_selesai, 0, 5),
            'kuota' => (int) $j->kuota,
            'is_active' => (bool) $j->is_active,
        ];
    }

    private function validasi(JadwalPraktek $j, ?string $abaikanId = null): ?string
    {
        $karyawan = Karyawan::find()->where(['id' => $j->karyawan_id])->with('jabatan')->one();

        if ($karyawan === null || !$karyawan->is_active) {
            return 'Karyawan tidak ditemukan atau tidak aktif';
        }

        if ($karyawan->jabatan === null || !$karyawan->jabatan->is_dokter) {
            return 'Jadwal praktek hanya untuk karyawan berjabatan Dokter';
        }

        $poli = Poli::find()->where(['id' => $j->poli_id])->one();

        if ($poli === null || !$poli->is_active) {
            return 'Poli tidak ditemukan atau tidak aktif';
        }

        if ($j->hari < 1 || $j->hari > 7) {
            return 'Hari harus 1 (Senin) sampai 7 (Minggu)';
        }

        if (!preg_match('/^\d{2}:\d{2}$/', $j->jam_mulai) || !preg_match('/^\d{2}:\d{2}$/', $j->jam_selesai)) {
            return 'Format jam harus HH:MM';
        }

        if (strtotime($j->jam_mulai) >= strtotime($j->jam_selesai)) {
            return 'Jam mulai harus lebih awal dari jam selesai';
        }

        if ($j->kuota < 1) {
            return 'Kuota minimal 1';
        }

        $ada = JadwalPraktek::find()
            ->where([
                'karyawan_id' => $j->karyawan_id,
                'poli_id' => $j->poli_id,
                'hari' => $j->hari,
                'jam_mulai' => $j->jam_mulai,
            ])
            ->andFilterWhere(['!=', 'id', $abaikanId])
            ->exists();

        if ($ada) {
            return 'Jadwal dobel untuk dokter, poli, dan jam yang sama';
        }

        foreach (JadwalPraktek::find()
            ->where(['karyawan_id' => $j->karyawan_id, 'hari' => $j->hari])
            ->andFilterWhere(['!=', 'id', $abaikanId])
            ->all() as $lama
        ) {
            if ($this->jamOverlap((string) $lama->jam_mulai, (string) $lama->jam_selesai, $j->jam_mulai, $j->jam_selesai)) {
                return 'Jadwal bertabrakan dengan jadwal lain dokter ini';
            }
        }

        return null;
    }

    private function jamOverlap(string $mulaiA, string $selesaiA, string $mulaiB, string $selesaiB): bool
    {
        return strtotime($mulaiA) < strtotime($selesaiB) && strtotime($selesaiA) > strtotime($mulaiB);
    }
}

