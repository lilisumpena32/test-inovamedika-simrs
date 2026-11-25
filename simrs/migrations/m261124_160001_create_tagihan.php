<?php

use yii\db\Migration;

class m261124_160001_create_tagihan extends Migration
{
    public function safeUp(): void
    {
        $this->execute('CREATE SEQUENCE seq_tagihan START 1');

        $this->createTable('{{%tagihan}}', [
            'id' => "varchar(50) NOT NULL PRIMARY KEY DEFAULT gen_random_uuid()::text",
            'no_tagihan' => $this->string(20)->notNull()->unique(),
            'kunjungan_id' => $this->string(50)->notNull()->unique(),
            'status' => $this->string(16)->notNull()->defaultValue('belum'),
            'total' => $this->decimal(12, 2)->notNull()->defaultValue(0),
            'create_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'update_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'create_by' => $this->string(50),
            'update_by' => $this->string(50),
        ]);

        $this->addForeignKey('fk_tagihan_kunjungan', '{{%tagihan}}', 'kunjungan_id', '{{%kunjungan}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_tagihan_create_by', '{{%tagihan}}', 'create_by', '{{%user}}', 'id', 'SET NULL');
        $this->addForeignKey('fk_tagihan_update_by', '{{%tagihan}}', 'update_by', '{{%user}}', 'id', 'SET NULL');
        $this->addCheck('ck_tagihan_status', '{{%tagihan}}', "status IN ('belum', 'sebagian', 'lunas')");
        $this->addCheck('ck_tagihan_total', '{{%tagihan}}', 'total >= 0');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%tagihan}}');
        $this->execute('DROP SEQUENCE IF EXISTS seq_tagihan');
    }
}
