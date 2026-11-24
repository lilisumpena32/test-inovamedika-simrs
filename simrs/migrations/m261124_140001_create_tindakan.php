<?php

use yii\db\Migration;

class m261124_140001_create_tindakan extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%tindakan}}', [
            'id' => "varchar(50) NOT NULL PRIMARY KEY DEFAULT gen_random_uuid()::text",
            'kode_tindakan' => $this->string(16)->notNull()->unique(),
            'nama' => $this->string(64)->notNull(),
            'tarif' => $this->decimal(12, 2)->notNull(),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'create_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'update_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'create_by' => $this->string(50),
            'update_by' => $this->string(50),
        ]);
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%tindakan}}');
    }
}
