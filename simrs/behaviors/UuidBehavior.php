<?php

declare(strict_types=1);

namespace app\behaviors;

use app\helpers\Uuid;
use yii\base\Behavior;
use yii\db\ActiveRecord;

class UuidBehavior extends Behavior
{
    /** @var ActiveRecord */
    public $owner;

    public function events(): array
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => 'isiId',
        ];
    }

    public function isiId(): void
    {
        if ($this->owner->hasAttribute('id') && !$this->owner->getAttribute('id')) {
            $this->owner->setAttribute('id', Uuid::v4());
        }
    }
}
