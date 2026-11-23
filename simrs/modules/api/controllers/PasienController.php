<?php

declare(strict_types=1);

namespace app\modules\api\controllers;

use app\models\Pasien;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\UnprocessableEntityHttpException;

class PasienController extends ApiController
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
        $this->butuh('master.pasien', 'view');

        $request = Yii::$app->request;
        $q = trim((string) $request->get('q', ''));
        $aktif = (string) $request->get('is_active', '');
        $halaman = max(1, (int) $request->get('page', '1'));
        $perHalaman = min(50, max(1, (int) $request->get('per', '15')));

        $query = Pasien::find();

        if ($q !== '') {
            $query->andWhere(['or', ['like', 'nama', $q], ['like', 'no_rm', $q], ['like', 'nik', $q]]);
        }

        if ($aktif === 'true' || $aktif === 'false') {
            $query->andWhere(['is_active' => $aktif === 'true']);
        }

        $total = (int) (clone $query)->count();
        $baris = $query
            ->orderBy(['nama' => SORT_ASC])
            ->offset(($halaman - 1) * $perHalaman)
            ->limit($perHalaman)
            ->all();

        return $this->sukses([
            'items' => array_map(fn (Pasien $pasien) => $this->ringkas($pasien), $baris),
            'total' => $total,
            'page' => $halaman,
            'per' => $perHalaman,
        ]);
    }

    public function actionView(string $id): array
    {
        $this->butuh('master.pasien', 'view');

        return $this->sukses($this->ringkas($this->cari($id)));
    }

    public function actionCreate(): array
    {
        $this->butuh('master.pasien', 'create');

        $body = Yii::$app->request->getBodyParams();

        $pasien = new Pasien();
        $pasien->nama = trim((string) ($body['nama'] ?? ''));
        $pasien->nik = $this->teks($body, 'nik', 16);
        $pasien->tanggal_lahir = $this->teks($body, 'tanggal_lahir', 10);
        $pasien->jenis_kelamin = (string) ($body['jenis_kelamin'] ?? '');
        $pasien->no_hp = $this->teks($body, 'no_hp', 20);
        $pasien->alamat = trim((string) ($body['alamat'] ?? ''));
        $pasien->is_active = true;

        $galat = $this->validasi($pasien);
        if ($galat !== null) {
            return $this->gagal($galat, 422);
        }

        $pasien->no_rm = 'RM' . str_pad(
            (string) Yii::$app->db->createCommand('SELECT nextval(\'seq_no_rm\')')->queryScalar(),
            9,
            '0',
            STR_PAD_LEFT,
        );

        if (!$pasien->save(false)) {
            throw new \RuntimeException('gagal menyimpan pasien');
        }

        return $this->sukses($this->ringkas($pasien), 201, 'Pasien ditambahkan.');
    }

    public function actionUpdate(string $id): array
    {
        $this->butuh('master.pasien', 'update');

        $pasien = $this->cari($id);
        $body = Yii::$app->request->getBodyParams();

        $pasien->nama = trim((string) ($body['nama'] ?? ''));
        $pasien->nik = $this->teks($body, 'nik', 16);
        $pasien->tanggal_lahir = $this->teks($body, 'tanggal_lahir', 10);
        $pasien->jenis_kelamin = (string) ($body['jenis_kelamin'] ?? '');
        $pasien->no_hp = $this->teks($body, 'no_hp', 20);
        $pasien->alamat = trim((string) ($body['alamat'] ?? ''));

        $galat = $this->validasi($pasien, $pasien->id);
        if ($galat !== null) {
            return $this->gagal($galat, 422);
        }

        $pasien->save(false);

        return $this->sukses($this->ringkas($pasien), 200, 'Pasien diubah.');
    }

    public function actionStatus(string $id): array
    {
        $this->butuh('master.pasien', 'update');

        $pasien = $this->cari($id);
        $aktif = filter_var(
            Yii::$app->request->getBodyParams()['is_active'] ?? null,
            FILTER_VALIDATE_BOOL,
            FILTER_NULL_ON_FAILURE,
        );

        if ($aktif === null) {
            return $this->gagal('Status tidak valid', 422);
        }

        $pasien->is_active = $aktif;
        $pasien->save(false);

        return $this->sukses($this->ringkas($pasien), 200, 'Status pasien diubah.');
    }

    private function cari(string $id): Pasien
    {
        $pasien = Pasien::find()->where(['id' => $id])->one();

        if ($pasien === null) {
            throw new NotFoundHttpException('Pasien tidak ditemukan');
        }

        return $pasien;
    }

    private function ringkas(Pasien $pasien): array
    {
        return [
            'id' => (string) $pasien->id,
            'no_rm' => $pasien->no_rm,
            'nik' => $pasien->nik,
            'nama' => $pasien->nama,
            'tanggal_lahir' => $pasien->tanggal_lahir,
            'jenis_kelamin' => $pasien->jenis_kelamin,
            'no_hp' => $pasien->no_hp,
            'alamat' => $pasien->alamat,
            'is_active' => (bool) $pasien->is_active,
        ];
    }

    private function teks(array $body, string $kunci, int $maks): ?string
    {
        $nilai = trim((string) ($body[$kunci] ?? ''));

        return $nilai === '' ? null : mb_substr($nilai, 0, $maks);
    }

    private function validasi(Pasien $pasien, ?string $abaikanId = null): ?string
    {
        if ($pasien->nama === '' || mb_strlen($pasien->nama) > 120) {
            return 'Nama pasien wajib diisi maksimal 120 karakter';
        }

        if (!in_array($pasien->jenis_kelamin, ['L', 'P'], true)) {
            return 'Jenis kelamin harus L atau P';
        }

        if ($pasien->tanggal_lahir !== null
            && ($this->tanggalSalah($pasien->tanggal_lahir) || $pasien->tanggal_lahir > date('Y-m-d'))
        ) {
            return 'Tanggal lahir tidak valid';
        }

        if ($pasien->nik !== null) {
            if (!preg_match('/^[0-9]{16}$/', $pasien->nik)) {
                return 'NIK harus 16 digit angka';
            }

            $ada = Pasien::find()
                ->where(['nik' => $pasien->nik])
                ->andFilterWhere(['!=', 'id', $abaikanId])
                ->exists();

            if ($ada) {
                return 'NIK sudah dipakai pasien lain';
            }
        }

        return null;
    }

    private function tanggalSalah(string $tanggal): bool
    {
        $pecah = explode('-', $tanggal);

        return count($pecah) !== 3 || !checkdate((int) $pecah[1], (int) $pecah[2], (int) $pecah[0]);
    }
}

