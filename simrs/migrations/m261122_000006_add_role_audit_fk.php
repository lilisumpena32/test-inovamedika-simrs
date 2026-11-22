<?php

use yii\db\Migration;

class m261122_000006_add_role_audit_fk extends Migration
{
    public function safeUp(): void
    {
        $this->addForeignKey('fk_role_create_by', '{{%role}}', 'create_by', '{{%user}}', 'id', 'SET NULL');
        $this->addForeignKey('fk_role_update_by', '{{%role}}', 'update_by', '{{%user}}', 'id', 'SET NULL');
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk_role_create_by', '{{%role}}');
        $this->dropForeignKey('fk_role_update_by', '{{%role}}');
    }
}
