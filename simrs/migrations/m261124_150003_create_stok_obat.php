<?php

use yii\db\Migration;

class m261124_150003_create_stok_obat extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%stok_obat}}', [
            'id' => "varchar(50) NOT NULL PRIMARY KEY DEFAULT gen_random_uuid()::text",
            'obat_id' => $this->string(50)->notNull(),
            'no_batch' => $this->string(30)->notNull(),
            'kadaluarsa' => $this->date()->notNull(),
            'jumlah' => $this->integer()->notNull(),
            'create_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'update_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'create_by' => $this->string(50),
            'update_by' => $this->string(50),
        ]);

        $this->addForeignKey('fk_so_obat', '{{%stok_obat}}', 'obat_id', '{{%obat}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_so_create_by', '{{%stok_obat}}', 'create_by', '{{%user}}', 'id', 'SET NULL');
        $this->addForeignKey('fk_so_update_by', '{{%stok_obat}}', 'update_by', '{{%user}}', 'id', 'SET NULL');
        $this->createIndex('uq_stok_batch', '{{%stok_obat}}', ['obat_id', 'no_batch'], true);
        $this->addCheck('ck_stok_jumlah', '{{%stok_obat}}', 'jumlah >= 0');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%stok_obat}}');
    }
}
