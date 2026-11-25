<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

class OutboxLab extends MasterModel
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_APPLIED = 'applied';
    public const STATUS_FAILED = 'failed';

    public static function tableName(): string
    {
        return '{{%outbox_lab}}';
    }

    public function getOrderLab(): ActiveQuery
    {
        return $this->hasOne(OrderLab::class, ['id' => 'order_lab_id']);
    }
}
