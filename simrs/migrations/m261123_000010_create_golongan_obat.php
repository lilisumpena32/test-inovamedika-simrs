<?php

use yii\db\Migration;

class m261123_000010_create_golongan_obat extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%golongan_obat}}', [
            'id' => "varchar(50) NOT NULL PRIMARY KEY DEFAULT gen_random_uuid()::text",
            'kode_golongan_obat' => $this->string(16)->notNull()->unique(),
            'nama_golongan_obat' => $this->string(64)->notNull(),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'create_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'update_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'create_by' => $this->string(50),
            'update_by' => $this->string(50),
        ]);

        $this->addForeignKey('fk_golongan_obat_create_by', '{{%golongan_obat}}', 'create_by', '{{%user}}', 'id', 'SET NULL');
        $this->addForeignKey('fk_golongan_obat_update_by', '{{%golongan_obat}}', 'update_by', '{{%user}}', 'id', 'SET NULL');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%golongan_obat}}');
    }
}
