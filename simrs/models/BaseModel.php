<?php

declare(strict_types=1);

namespace app\models;

use app\behaviors\AuditBehavior;
use app\behaviors\UuidBehavior;
use yii\db\ActiveRecord;

abstract class BaseModel extends ActiveRecord
{
    public function behaviors(): array
    {
        return [
            UuidBehavior::class,
            AuditBehavior::class,
        ];
    }
}
