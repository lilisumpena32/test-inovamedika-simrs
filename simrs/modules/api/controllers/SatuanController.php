<?php

declare(strict_types=1);

namespace app\modules\api\controllers;

use app\models\Satuan;
use Yii;

class SatuanController extends ApiController
{
    protected function verbs(): array
    {
        return ['index' => ['GET']];
    }

    public function actionIndex(): array
    {
        $this->butuh('farmasi.obat', 'view');

        $baris = Satuan::find()
            ->where(['is_active' => true])
            ->orderBy(['nama_satuan' => SORT_ASC])
            ->all();

        return $this->sukses(['items' => array_map(
            fn (Satuan $s) => ['id' => (string) $s->id, 'kode' => $s->kode_satuan, 'name' => $s->nama_satuan],
            $baris,
        )]);
    }
}
