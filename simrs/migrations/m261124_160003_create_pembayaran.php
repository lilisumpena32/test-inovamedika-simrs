<?php

use yii\db\Migration;

class m261124_160003_create_pembayaran extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%pembayaran}}', [
            'id' => "varchar(50) NOT NULL PRIMARY KEY DEFAULT gen_random_uuid()::text",
            'tagihan_id' => $this->string(50)->notNull(),
            'idempotency_key' => $this->string(64)->notNull()->unique(),
            'metode' => $this->string(16)->notNull(),
            'nominal' => $this->decimal(12, 2)->notNull(),
            'waktu' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'create_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'update_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'create_by' => $this->string(50),
            'update_by' => $this->string(50),
        ]);

        $this->addForeignKey('fk_pay_tagihan', '{{%pembayaran}}', 'tagihan_id', '{{%tagihan}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_pay_create_by', '{{%pembayaran}}', 'create_by', '{{%user}}', 'id', 'SET NULL');
        $this->addForeignKey('fk_pay_update_by', '{{%pembayaran}}', 'update_by', '{{%user}}', 'id', 'SET NULL');
        $this->addCheck('ck_pay_metode', '{{%pembayaran}}', "metode IN ('tunai', 'non_tunai')");
        $this->addCheck('ck_pay_nominal', '{{%pembayaran}}', 'nominal > 0');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%pembayaran}}');
    }
}
