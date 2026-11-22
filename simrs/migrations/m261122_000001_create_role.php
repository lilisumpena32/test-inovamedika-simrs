<?php

use yii\db\Migration;

class m261122_000001_create_role extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%role}}', [
            'id' => "varchar(50) NOT NULL PRIMARY KEY DEFAULT gen_random_uuid()::text",
            'role_code' => $this->string(32)->notNull()->unique(),
            'role_name' => $this->string(64)->notNull(),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'create_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'update_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'create_by' => $this->string(50),
            'update_by' => $this->string(50),
        ]);
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%role}}');
    }
}
