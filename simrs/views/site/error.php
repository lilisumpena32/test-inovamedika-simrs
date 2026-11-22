<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $name */
/** @var string $message */
/** @var Exception $exception */

use yii\helpers\Html;
use yii\web\HttpException;

$statusCode = $exception instanceof HttpException ? $exception->statusCode : 500;
$pesan = match ($statusCode) {
    404 => 'Halaman tidak ditemukan.',
    405 => 'Metode HTTP tidak didukung.',
    500 => 'Terjadi kesalahan pada server.',
    default => $message,
};

$this->title = "{$statusCode} - SIMRS Inova Medika";
?>
<p style="margin:0; font-size:12px; letter-spacing:.14em; text-transform:uppercase; color:#64748b;">
    SIMRS Inova Medika
</p>
<h1 style="margin:8px 0 0; font-size:56px; font-weight:700;"><?= Html::encode((string) $statusCode) ?></h1>
<p style="margin:12px 0 0;"><?= Html::encode($pesan) ?></p>
<p style="margin:16px 0 0;">
    <a href="<?= Yii::$app->homeUrl ?>" style="color:#0d9488;">Kembali ke beranda</a>
</p>
