<?php

declare(strict_types=1);

namespace app\modules\api\controllers;

use app\models\Kategori;
use Yii;

class KategoriController extends ApiController
{
    protected function verbs(): array
    {
        return ['index' => ['GET']];
    }

    public function actionIndex(): array
    {
        $this->butuh('farmasi.obat', 'view');

        $baris = Kategori::find()
            ->where(['is_active' => true])
            ->orderBy(['nama_kategori' => SORT_ASC])
            ->all();

        return $this->sukses(['items' => array_map(
            fn (Kategori $k) => ['id' => (string) $k->id, 'kode' => $k->kode_kategori, 'name' => $k->nama_kategori],
            $baris,
        )]);
    }
}
