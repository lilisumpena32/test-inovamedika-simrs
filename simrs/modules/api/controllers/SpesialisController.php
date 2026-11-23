<?php

declare(strict_types=1);

namespace app\modules\api\controllers;

use app\models\Spesialis;
use Yii;

class SpesialisController extends ApiController
{
    protected function verbs(): array
    {
        return ['index' => ['GET']];
    }

    public function actionIndex(): array
    {
        $this->butuh('master.karyawan', 'view');

        $baris = Spesialis::find()
            ->where(['is_active' => true])
            ->orderBy(['nama_spesialis' => SORT_ASC])
            ->all();

        return $this->sukses(['items' => array_map(
            fn (Spesialis $s) => ['id' => (string) $s->id, 'kode' => $s->kode_spesialis, 'name' => $s->nama_spesialis],
            $baris,
        )]);
    }
}
