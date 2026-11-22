<?php

declare(strict_types=1);

namespace app\tests\Support\Helper;

use Codeception\Module;
use Codeception\Module\Db;

class DbSimrs extends Module
{
    /**
     * Menjalankan DELETE/UPDATE mentah lewat koneksi modul Db.
     * Modul Db bawaan tidak menyediakan deleteFromDatabase.
     */
    public function hapusBaris(string $sql, array $parameter = []): void
    {
        /** @var Db $db */
        $db = $this->getModule('Db');
        $db->_getDbh()->prepare($sql)->execute($parameter);
    }
}
