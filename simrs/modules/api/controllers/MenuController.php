<?php

declare(strict_types=1);

namespace app\modules\api\controllers;

use Yii;

class MenuController extends ApiController
{
    protected function verbs(): array
    {
        return [
            'index' => ['GET'],
        ];
    }

    public function actionIndex(): array
    {
        return $this->sukses(Yii::$app->access->menu());
    }
}
