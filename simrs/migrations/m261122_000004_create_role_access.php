<?php

use yii\db\Migration;

class m261122_000004_create_role_access extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%role_access}}', [
            'role_id' => $this->string(50)->notNull(),
            'menu_id' => $this->string(50)->notNull(),
            'can_view' => $this->boolean()->notNull()->defaultValue(false),
            'can_create' => $this->boolean()->notNull()->defaultValue(false),
            'can_update' => $this->boolean()->notNull()->defaultValue(false),
            'can_delete' => $this->boolean()->notNull()->defaultValue(false),
            'can_auth' => $this->boolean()->notNull()->defaultValue(false),
            'create_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'update_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'create_by' => $this->string(50),
            'update_by' => $this->string(50),
        ]);

        $this->addPrimaryKey('pk_role_access', '{{%role_access}}', ['role_id', 'menu_id']);
        $this->addForeignKey('fk_role_access_role', '{{%role_access}}', 'role_id', '{{%role}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_role_access_menu', '{{%role_access}}', 'menu_id', '{{%menu}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_role_access_create_by', '{{%role_access}}', 'create_by', '{{%user}}', 'id', 'SET NULL');
        $this->addForeignKey('fk_role_access_update_by', '{{%role_access}}', 'update_by', '{{%user}}', 'id', 'SET NULL');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%role_access}}');
    }
}
