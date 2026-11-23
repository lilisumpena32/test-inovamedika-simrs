<?php

use yii\db\Migration;

class m261123_000008_create_satuan extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%satuan}}', [
            'id' => "varchar(50) NOT NULL PRIMARY KEY DEFAULT gen_random_uuid()::text",
            'kode_satuan' => $this->string(16)->notNull()->unique(),
            'nama_satuan' => $this->string(64)->notNull(),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'create_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'update_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'create_by' => $this->string(50),
            'update_by' => $this->string(50),
        ]);

        $this->addForeignKey('fk_satuan_create_by', '{{%satuan}}', 'create_by', '{{%user}}', 'id', 'SET NULL');
        $this->addForeignKey('fk_satuan_update_by', '{{%satuan}}', 'update_by', '{{%user}}', 'id', 'SET NULL');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%satuan}}');
    }
}
