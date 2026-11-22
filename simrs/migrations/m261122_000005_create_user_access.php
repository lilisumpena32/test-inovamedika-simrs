<?php

use yii\db\Migration;

class m261122_000005_create_user_access extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%user_access}}', [
            'id' => "varchar(50) NOT NULL PRIMARY KEY DEFAULT gen_random_uuid()::text",
            'user_id' => $this->string(50)->notNull(),
            'menu_id' => $this->string(50)->notNull(),
            'create_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'update_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'create_by' => $this->string(50),
            'update_by' => $this->string(50),
        ]);

        $this->createIndex('uq_user_access', '{{%user_access}}', ['user_id', 'menu_id'], true);
        $this->addForeignKey('fk_user_access_user', '{{%user_access}}', 'user_id', '{{%user}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_user_access_menu', '{{%user_access}}', 'menu_id', '{{%menu}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_user_access_create_by', '{{%user_access}}', 'create_by', '{{%user}}', 'id', 'SET NULL');
        $this->addForeignKey('fk_user_access_update_by', '{{%user_access}}', 'update_by', '{{%user}}', 'id', 'SET NULL');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%user_access}}');
    }
}
