<?php

use yii\db\Migration;

class m261123_000009_create_kategori extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%kategori}}', [
            'id' => "varchar(50) NOT NULL PRIMARY KEY DEFAULT gen_random_uuid()::text",
            'kode_kategori' => $this->string(16)->notNull()->unique(),
            'nama_kategori' => $this->string(64)->notNull(),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'create_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'update_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'create_by' => $this->string(50),
            'update_by' => $this->string(50),
        ]);

        $this->addForeignKey('fk_kategori_create_by', '{{%kategori}}', 'create_by', '{{%user}}', 'id', 'SET NULL');
        $this->addForeignKey('fk_kategori_update_by', '{{%kategori}}', 'update_by', '{{%user}}', 'id', 'SET NULL');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%kategori}}');
    }
}
