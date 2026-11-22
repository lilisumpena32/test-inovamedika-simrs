<?php

use yii\db\Migration;

class m261122_000002_create_user extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%user}}', [
            'id' => "varchar(50) NOT NULL PRIMARY KEY DEFAULT gen_random_uuid()::text",
            'username' => $this->string(64)->notNull(),
            'password_hash' => $this->string(255)->notNull(),
            'full_name' => $this->string(120)->notNull(),
            'email' => $this->string(128),
            'role_id' => $this->string(50)->notNull(),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'is_delete' => $this->boolean()->notNull()->defaultValue(false),
            'create_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'update_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'create_by' => $this->string(50),
            'update_by' => $this->string(50),
        ]);

        $this->addForeignKey('fk_user_role', '{{%user}}', 'role_id', '{{%role}}', 'id');
        $this->addForeignKey('fk_user_create_by', '{{%user}}', 'create_by', '{{%user}}', 'id', 'SET NULL');
        $this->addForeignKey('fk_user_update_by', '{{%user}}', 'update_by', '{{%user}}', 'id', 'SET NULL');

        $this->execute('ALTER TABLE "user" ADD CONSTRAINT ck_user_hapus_nonaktif CHECK (NOT is_delete OR NOT is_active)');
        $this->execute('CREATE UNIQUE INDEX uq_user_username ON "user" (username) WHERE is_delete = false');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%user}}');
    }
}
