<?php

use yii\db\Migration;

class m261123_000002_create_poli extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%poli}}', [
            'id' => "varchar(50) NOT NULL PRIMARY KEY DEFAULT gen_random_uuid()::text",
            'kode' => $this->string(16)->notNull()->unique(),
            'nama' => $this->string(64)->notNull(),
            'biaya_daftar' => $this->decimal(12, 2)->notNull()->defaultValue(0),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'create_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'update_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'create_by' => $this->string(50),
            'update_by' => $this->string(50),
        ]);

        $this->addForeignKey('fk_poli_create_by', '{{%poli}}', 'create_by', '{{%user}}', 'id', 'SET NULL');
        $this->addForeignKey('fk_poli_update_by', '{{%poli}}', 'update_by', '{{%user}}', 'id', 'SET NULL');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%poli}}');
    }
}
