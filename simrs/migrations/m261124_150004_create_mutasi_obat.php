<?php

use yii\db\Migration;

class m261124_150004_create_mutasi_obat extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%mutasi_obat}}', [
            'id' => "varchar(50) NOT NULL PRIMARY KEY DEFAULT gen_random_uuid()::text",
            'obat_id' => $this->string(50)->notNull(),
            'stok_obat_id' => $this->string(50),
            'jenis' => $this->string(8)->notNull(),
            'jumlah' => $this->integer()->notNull(),
            'referensi' => $this->string(64),
            'create_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'update_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'create_by' => $this->string(50),
            'update_by' => $this->string(50),
        ]);

        $this->addForeignKey('fk_mo_obat', '{{%mutasi_obat}}', 'obat_id', '{{%obat}}', 'id');
        $this->addForeignKey('fk_mo_stok', '{{%mutasi_obat}}', 'stok_obat_id', '{{%stok_obat}}', 'id', 'SET NULL');
        $this->addForeignKey('fk_mo_create_by', '{{%mutasi_obat}}', 'create_by', '{{%user}}', 'id', 'SET NULL');
        $this->addForeignKey('fk_mo_update_by', '{{%mutasi_obat}}', 'update_by', '{{%user}}', 'id', 'SET NULL');
        $this->addCheck('ck_mutasi_jenis', '{{%mutasi_obat}}', "jenis IN ('masuk', 'keluar')");
        $this->addCheck('ck_mutasi_jumlah', '{{%mutasi_obat}}', 'jumlah > 0');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%mutasi_obat}}');
    }
}
