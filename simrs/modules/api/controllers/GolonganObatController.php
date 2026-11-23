<?php

declare(strict_types=1);

namespace app\modules\api\controllers;

use app\models\GolonganObat;
use Yii;

class GolonganObatController extends ApiController
{
    protected function verbs(): array
    {
        return ['index' => ['GET']];
    }

    public function actionIndex(): array
    {
        $this->butuh('farmasi.obat', 'view');

        $baris = GolonganObat::find()
            ->where(['is_active' => true])
            ->orderBy(['nama_golongan_obat' => SORT_ASC])
            ->all();

        return $this->sukses(['items' => array_map(
            fn (GolonganObat $g) => [
                'id' => (string) $g->id,
                'kode' => $g->kode_golongan_obat,
                'name' => $g->nama_golongan_obat,
            ],
            $baris,
        )]);
    }
}
