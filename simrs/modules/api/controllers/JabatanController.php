<?php

declare(strict_types=1);

namespace app\modules\api\controllers;

use app\models\Jabatan;
use Yii;

class JabatanController extends ApiController
{
    protected function verbs(): array
    {
        return ['index' => ['GET']];
    }

    public function actionIndex(): array
    {
        $this->butuh('master.karyawan', 'view');

        $baris = Jabatan::find()
            ->where(['is_active' => true])
            ->orderBy(['nama_jabatan' => SORT_ASC])
            ->all();

        return $this->sukses(['items' => array_map(
            fn (Jabatan $j) => [
                'id' => (string) $j->id,
                'kode' => $j->kode_jabatan,
                'name' => $j->nama_jabatan,
                'is_dokter' => (bool) $j->is_dokter,
            ],
            $baris,
        )]);
    }
}
