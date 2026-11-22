<?php

declare(strict_types=1);

namespace app\modules\api\controllers;

use app\behaviors\JwtAuth;
use app\components\ApiResponse;
use Yii;
use yii\filters\ContentNegotiator;
use yii\filters\VerbFilter;
use yii\rest\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\Response;

class ApiController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors(): array
    {
        return [
            'contentNegotiator' => [
                'class' => ContentNegotiator::class,
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
            'verbFilter' => [
                'class' => VerbFilter::class,
                'actions' => $this->verbs(),
            ],
            'jwtAuth' => [
                'class' => JwtAuth::class,
            ],
        ];
    }

    public function runAction($id, $params = [])
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            return parent::runAction($id, $params);
        } catch (HttpException $e) {
            Yii::$app->response->statusCode = $e->statusCode;

            return ApiResponse::error($e->getMessage() ?: 'Permintaan ditolak', $e->statusCode);
        } catch (\Throwable $e) {
            Yii::error($e, __METHOD__);
            Yii::$app->response->statusCode = 500;

            return ApiResponse::error('Terjadi kesalahan pada backend', 500);
        }
    }

    protected function verbs(): array
    {
        return [];
    }

    protected function butuh(string $kodeMenu, string $aksi): void
    {
        if (!Yii::$app->access->can($kodeMenu, $aksi)) {
            throw new ForbiddenHttpException('Anda tidak punya akses untuk aksi ini');
        }
    }

    protected function sukses(mixed $data, int $kode = 200): array
    {
        Yii::$app->response->statusCode = $kode;

        return ApiResponse::ok($data, $kode);
    }

    protected function gagal(string $pesan, int $kode): array
    {
        Yii::$app->response->statusCode = $kode;

        return ApiResponse::error($pesan, $kode);
    }
}
