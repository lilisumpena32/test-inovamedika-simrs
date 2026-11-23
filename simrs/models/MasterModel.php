<?php

declare(strict_types=1);

namespace app\models;

use app\behaviors\LogAktivitasBehavior;

abstract class MasterModel extends BaseModel
{
    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), [LogAktivitasBehavior::class]);
    }
}
