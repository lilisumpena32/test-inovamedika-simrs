<?php

use yii\db\Migration;

class m261123_000003_create_jabatan extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%jabatan}}', [
            'id' => "varchar(50) NOT NULL PRIMARY KEY DEFAULT gen_random_uuid()::text",
            'kode_jabatan' => $this->string(16)->notNull()->unique(),
            'nama_jabatan' => $this->string(64)->notNull(),
            'is_dokter' => $this->boolean()->notNull()->defaultValue(false),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'create_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'update_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'create_by' => $this->string(50),
            'update_by' => $this->string(50),
        ]);

        $this->addForeignKey('fk_jabatan_create_by', '{{%jabatan}}', 'create_by', '{{%user}}', 'id', 'SET NULL');
        $this->addForeignKey('fk_jabatan_update_by', '{{%jabatan}}', 'update_by', '{{%user}}', 'id', 'SET NULL');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%jabatan}}');
    }
}
