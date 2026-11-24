<?php

declare(strict_types=1);

namespace app\modules\api\controllers;

use app\models\Jabatan;
use app\models\Karyawan;
use app\models\Spesialis;
use app\models\User;
use Yii;
use yii\web\NotFoundHttpException;

class KaryawanController extends ApiController
{
    protected function verbs(): array
    {
        return [
            'index' => ['GET'],
            'view' => ['GET'],
            'create' => ['POST'],
            'update' => ['PUT'],
            'akun' => ['PUT'],
            'status' => ['PUT'],
        ];
    }

    public function actionIndex(): array
    {
        $this->butuh('master.karyawan', 'view');

        $request = Yii::$app->request;
        $q = trim((string) $request->get('q', ''));
        $kodeJabatan = trim((string) $request->get('jabatan', ''));
        $aktif = (string) $request->get('is_active', '');

        $query = Karyawan::find()->with(['jabatan', 'spesialis', 'user']);

        if ($q !== '') {
            $query->andWhere(['or', ['ilike', 'nama_karyawan', $q], ['ilike', 'kode_karyawan', $q]]);
        }

        if ($kodeJabatan !== '') {
            $query->andWhere([
                'jabatan_id' => Jabatan::find()->select('id')->where(['kode_jabatan' => $kodeJabatan]),
            ]);
        }

        if ($aktif === 'true' || $aktif === 'false') {
            $query->andWhere(['{{%karyawan}}.is_active' => $aktif === 'true']);
        }

        $total = (int) (clone $query)->count();
        $halaman = max(1, (int) $request->get('page', '1'));
        $perHalaman = min(50, max(1, (int) $request->get('per', '15')));
        $baris = $query
            ->orderBy(['nama_karyawan' => SORT_ASC])
            ->offset(($halaman - 1) * $perHalaman)
            ->limit($perHalaman)
            ->all();

        return $this->sukses([
            'items' => array_map(fn (Karyawan $k) => $this->ringkas($k), $baris),
            'total' => $total,
            'page' => $halaman,
            'per' => $perHalaman,
        ]);
    }

    public function actionView(string $id): array
    {
        $this->butuh('master.karyawan', 'view');

        return $this->sukses($this->ringkas($this->cari($id)));
    }

    public function actionCreate(): array
    {
        $this->butuh('master.karyawan', 'create');

        $body = Yii::$app->request->getBodyParams();

        $karyawan = new Karyawan();
        $karyawan->nama_karyawan = trim((string) ($body['nama_karyawan'] ?? ''));
        $karyawan->jenis_kelamin = (string) ($body['jenis_kelamin'] ?? '');
        $karyawan->tanggal_lahir = $this->teks($body, 'tanggal_lahir', 10);
        $karyawan->jabatan_id = trim((string) ($body['jabatan_id'] ?? ''));
        $karyawan->spesialis_id = $this->teks($body, 'spesialis_id', 50);
        $karyawan->no_hp = $this->teks($body, 'no_hp', 20);
        $karyawan->user_id = $this->teks($body, 'user_id', 50);
        $karyawan->tanggal_bergabung = $this->teks($body, 'tanggal_bergabung', 10);
        $karyawan->is_active = true;

        $galat = $this->validasi($karyawan);
        if ($galat !== null) {
            return $this->gagal($galat, 422);
        }

        $transaksi = Yii::$app->db->beginTransaction();
        try {
            $nomor = (int) Yii::$app->db->createCommand(
                "SELECT COALESCE(MAX(SUBSTRING(kode_karyawan FROM 5)::int), 0) + 1 FROM karyawan WHERE kode_karyawan LIKE 'KRY-%'",
            )->queryScalar();
            $karyawan->kode_karyawan = 'KRY-' . str_pad((string) $nomor, 4, '0', STR_PAD_LEFT);

            if (!$karyawan->save(false)) {
                throw new \RuntimeException('gagal menyimpan karyawan');
            }

            $transaksi->commit();
        } catch (\Throwable $e) {
            $transaksi->rollBack();

            throw $e;
        }

        return $this->sukses($this->ringkas($karyawan), 201, 'Karyawan ditambahkan.');
    }

    public function actionUpdate(string $id): array
    {
        $this->butuh('master.karyawan', 'update');

        $karyawan = $this->cari($id);
        $body = Yii::$app->request->getBodyParams();

        $karyawan->nama_karyawan = trim((string) ($body['nama_karyawan'] ?? ''));
        $karyawan->jenis_kelamin = (string) ($body['jenis_kelamin'] ?? '');
        $karyawan->tanggal_lahir = $this->teks($body, 'tanggal_lahir', 10);
        $karyawan->jabatan_id = trim((string) ($body['jabatan_id'] ?? ''));
        $karyawan->spesialis_id = $this->teks($body, 'spesialis_id', 50);
        $karyawan->no_hp = $this->teks($body, 'no_hp', 20);
        $karyawan->tanggal_bergabung = $this->teks($body, 'tanggal_bergabung', 10);

        $galat = $this->validasi($karyawan, $karyawan->id);
        if ($galat !== null) {
            return $this->gagal($galat, 422);
        }

        $karyawan->save(false);

        return $this->sukses($this->ringkas($karyawan), 200, 'Karyawan diubah.');
    }

    public function actionAkun(string $id): array
    {
        $this->butuh('master.karyawan', 'update');

        $karyawan = $this->cari($id);
        $userId = $this->teks(Yii::$app->request->getBodyParams(), 'user_id', 50);

        if ($userId !== null) {
            $user = User::find()->where(['id' => $userId, 'is_delete' => false])->one();

            if ($user === null) {
                return $this->gagal('Akun user tidak ditemukan', 422);
            }

            $dipakai = Karyawan::find()
                ->where(['user_id' => $userId])
                ->andFilterWhere(['!=', 'id', $karyawan->id])
                ->exists();

            if ($dipakai) {
                return $this->gagal('Akun user sudah dipakai karyawan lain', 422);
            }
        }

        $karyawan->user_id = $userId;
        $karyawan->save(false);

        return $this->sukses($this->ringkas($karyawan), 200, 'Akun karyawan diubah.');
    }

    public function actionStatus(string $id): array
    {        $this->butuh('master.karyawan', 'update');

        $karyawan = $this->cari($id);
        $aktif = filter_var(
            Yii::$app->request->getBodyParams()['is_active'] ?? null,
            FILTER_VALIDATE_BOOL,
            FILTER_NULL_ON_FAILURE,
        );

        if ($aktif === null) {
            return $this->gagal('Status tidak valid', 422);
        }

        $karyawan->is_active = $aktif;
        $karyawan->save(false);

        return $this->sukses($this->ringkas($karyawan), 200, 'Status karyawan diubah.');
    }

    private function cari(string $id): Karyawan
    {
        $karyawan = Karyawan::find()->where(['id' => $id])->one();

        if ($karyawan === null) {
            throw new NotFoundHttpException('Karyawan tidak ditemukan');
        }

        return $karyawan;
    }

    private function ringkas(Karyawan $k): array
    {
        return [
            'id' => (string) $k->id,
            'kode_karyawan' => $k->kode_karyawan,
            'nama_karyawan' => $k->nama_karyawan,
            'jenis_kelamin' => $k->jenis_kelamin,
            'tanggal_lahir' => $k->tanggal_lahir,
            'jabatan' => $k->jabatan === null
                ? null
                : ['id' => (string) $k->jabatan->id, 'kode' => $k->jabatan->kode_jabatan, 'name' => $k->jabatan->nama_jabatan],
            'spesialis' => $k->spesialis === null
                ? null
                : ['id' => (string) $k->spesialis->id, 'kode' => $k->spesialis->kode_spesialis, 'name' => $k->spesialis->nama_spesialis],
            'no_hp' => $k->no_hp,
            'user' => $k->user === null
                ? null
                : ['id' => (string) $k->user->id, 'username' => $k->user->username],
            'tanggal_bergabung' => $k->tanggal_bergabung,
            'is_active' => (bool) $k->is_active,
        ];
    }

    private function teks(array $body, string $kunci, int $maks): ?string
    {
        $nilai = trim((string) ($body[$kunci] ?? ''));

        return $nilai === '' ? null : mb_substr($nilai, 0, $maks);
    }

    private function validasi(Karyawan $k, ?string $abaikanId = null): ?string
    {
        if ($k->nama_karyawan === '' || mb_strlen($k->nama_karyawan) > 120) {
            return 'Nama karyawan wajib diisi maksimal 120 karakter';
        }

        if (!in_array($k->jenis_kelamin, ['L', 'P'], true)) {
            return 'Jenis kelamin harus L atau P';
        }

        if ($k->tanggal_lahir !== null && $this->tanggalSalah($k->tanggal_lahir)) {
            return 'Tanggal lahir tidak valid';
        }

        $jabatan = Jabatan::findOne($k->jabatan_id);
        if ($jabatan === null || !$jabatan->is_active) {
            return 'Jabatan tidak ditemukan atau tidak aktif';
        }

        $dokter = $jabatan->is_dokter;

        if ($dokter && $k->spesialis_id === null) {
            return 'Spesialis wajib dipilih untuk jabatan Dokter';
        }

        if (!$dokter) {
            $k->spesialis_id = null;
        } elseif ($k->spesialis_id !== null && Spesialis::findOne($k->spesialis_id) === null) {
            return 'Spesialis tidak ditemukan';
        }

        if ($k->user_id !== null) {
            $user = User::find()->where(['id' => $k->user_id, 'is_delete' => false])->one();

            if ($user === null) {
                return 'Akun user tidak ditemukan';
            }

            $dipakai = Karyawan::find()
                ->where(['user_id' => $k->user_id])
                ->andFilterWhere(['!=', 'id', $abaikanId])
                ->exists();

            if ($dipakai) {
                return 'Akun user sudah dipakai karyawan lain';
            }
        }

        if ($k->tanggal_bergabung !== null && $this->tanggalSalah($k->tanggal_bergabung)) {
            return 'Tanggal bergabung tidak valid';
        }

        return null;
    }

    private function tanggalSalah(string $tanggal): bool
    {
        $pecah = explode('-', $tanggal);

        return count($pecah) !== 3 || !checkdate((int) $pecah[1], (int) $pecah[2], (int) $pecah[0]);
    }
}

