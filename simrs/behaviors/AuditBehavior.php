<?php

declare(strict_types=1);

namespace app\behaviors;

use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;

class AuditBehavior extends Behavior
{
    /** @var ActiveRecord */
    public $owner;

    public function events(): array
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => 'saatBuat',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'saatUbah',
        ];
    }

    public function saatBuat(): void
    {
        $waktu = date('Y-m-d H:i:s');
        $this->owner->setAttribute('create_date', $waktu);
        $this->owner->setAttribute('update_date', $waktu);
        $this->owner->setAttribute('create_by', $this->pelaku());
        $this->owner->setAttribute('update_by', $this->pelaku());
    }

    public function saatUbah(): void
    {
        $this->owner->setAttribute('update_date', date('Y-m-d H:i:s'));
        $this->owner->setAttribute('update_by', $this->pelaku());
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
