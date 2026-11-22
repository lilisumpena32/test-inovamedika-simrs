<?php

use yii\db\Migration;

class m261122_000003_create_menu extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%menu}}', [
            'id' => "varchar(50) NOT NULL PRIMARY KEY DEFAULT gen_random_uuid()::text",
            'kode' => $this->string(64)->notNull()->unique(),
            'parent_id' => $this->string(50),
            'label' => $this->string(64)->notNull(),
            'path' => $this->string(128),
            'icon' => $this->string(64),
            'sorter' => $this->integer()->notNull()->defaultValue(0),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'create_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'update_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'create_by' => $this->string(50),
            'update_by' => $this->string(50),
        ]);

        $this->addForeignKey('fk_menu_parent', '{{%menu}}', 'parent_id', '{{%menu}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_menu_create_by', '{{%menu}}', 'create_by', '{{%user}}', 'id', 'SET NULL');
        $this->addForeignKey('fk_menu_update_by', '{{%menu}}', 'update_by', '{{%user}}', 'id', 'SET NULL');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%menu}}');
    }
}
