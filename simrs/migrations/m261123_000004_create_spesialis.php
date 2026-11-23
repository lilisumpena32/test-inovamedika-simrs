<?php

use yii\db\Migration;

class m261123_000004_create_spesialis extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%spesialis}}', [
            'id' => "varchar(50) NOT NULL PRIMARY KEY DEFAULT gen_random_uuid()::text",
            'kode_spesialis' => $this->string(16)->notNull()->unique(),
            'nama_spesialis' => $this->string(64)->notNull(),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'create_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'update_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'create_by' => $this->string(50),
            'update_by' => $this->string(50),
        ]);

        $this->addForeignKey('fk_spesialis_create_by', '{{%spesialis}}', 'create_by', '{{%user}}', 'id', 'SET NULL');
        $this->addForeignKey('fk_spesialis_update_by', '{{%spesialis}}', 'update_by', '{{%user}}', 'id', 'SET NULL');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%spesialis}}');
    }
}
