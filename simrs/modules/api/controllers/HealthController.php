<?php

namespace app\modules\api\controllers;

use Yii;
use yii\rest\Controller;

class HealthController extends Controller
{
    public function actionIndex(): array
    {
        return [
            'status' => 'OK',
            'versi' => '1',
            'waktu_database' => Yii::$app->db->createCommand("SELECT to_char(now(), 'YYYY-MM-DD HH:MI:SS')")->queryScalar(),
            'waktu_aplikasi' => date("Y-m-d H:i:s"),
        ];
    }
}
