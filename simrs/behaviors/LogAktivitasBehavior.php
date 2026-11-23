<?php

declare(strict_types=1);

namespace app\behaviors;

use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\web\Request;

class LogAktivitasBehavior extends Behavior
{
    /** @var ActiveRecord */
    public $owner;

    private ?array $lama = null;

    public function events(): array
    {
        return [
            ActiveRecord::EVENT_BEFORE_UPDATE => 'catatLama',
            ActiveRecord::EVENT_AFTER_INSERT => 'logSimpan',
            ActiveRecord::EVENT_AFTER_UPDATE => 'logUbah',
            ActiveRecord::EVENT_AFTER_DELETE => 'logHapus',
        ];
    }

    public function catatLama(): void
    {
        $this->lama = $this->owner->getOldAttributes();
    }

    public function logSimpan(): void
    {
        $this->kirim('simpan', null, $this->owner->getAttributes());
    }

    public function logUbah(): void
    {
        $this->kirim('ubah', $this->lama, $this->owner->getAttributes());
    }

    public function logHapus(): void
    {
        $this->kirim('hapus', $this->owner->getAttributes(), null);
    }

    private function kirim(string $aksi, ?array $lama, ?array $baru): void
    {
        Yii::$app->db->createCommand(
            'SELECT fn_simpan_log(:tabel, :id, :aksi, CAST(:lama AS jsonb), CAST(:baru AS jsonb), :ip, :pelaku)',
            [
                ':tabel' => $this->namaTabel(),
                ':id' => (string) $this->owner->getAttribute('id'),
                ':aksi' => $aksi,
                ':lama' => $lama === null ? null : json_encode($lama, JSON_UNESCAPED_UNICODE),
                ':baru' => $baru === null ? null : json_encode($baru, JSON_UNESCAPED_UNICODE),
                ':ip' => $this->ip(),
                ':pelaku' => $this->pelaku(),
            ],
        )->execute();
    }

    private function namaTabel(): string
    {
        return str_replace(['{{%', '}}'], '', $this->owner->tableName());
    }

    private function ip(): ?string
    {
        if (!Yii::$app->has('request') || !Yii::$app->request instanceof Request) {
            return null;
        }

        return Yii::$app->request->getUserIP();
    }

    private function pelaku(): ?string
    {
        if (!Yii::$app->has('user')) {
            return null;
        }

        $id = Yii::$app->user->getId();

        return $id === null ? null : (string) $id;
    }
}
