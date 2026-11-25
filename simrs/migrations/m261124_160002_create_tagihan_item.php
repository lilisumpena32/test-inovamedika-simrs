<?php

use yii\db\Migration;

class m261124_160002_create_tagihan_item extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%tagihan_item}}', [
            'id' => "varchar(50) NOT NULL PRIMARY KEY DEFAULT gen_random_uuid()::text",
            'tagihan_id' => $this->string(50)->notNull(),
            'jenis' => $this->string(16)->notNull(),
            'referensi' => $this->string(50),
            'uraian' => $this->string(128)->notNull(),
            'jumlah' => $this->integer()->notNull()->defaultValue(1),
            'harga' => $this->decimal(12, 2)->notNull(),
            'create_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'update_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'create_by' => $this->string(50),
            'update_by' => $this->string(50),
        ]);

        $this->addForeignKey('fk_ti_tagihan', '{{%tagihan_item}}', 'tagihan_id', '{{%tagihan}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_ti_create_by', '{{%tagihan_item}}', 'create_by', '{{%user}}', 'id', 'SET NULL');
        $this->addForeignKey('fk_ti_update_by', '{{%tagihan_item}}', 'update_by', '{{%user}}', 'id', 'SET NULL');
        $this->addCheck('ck_ti_jenis', '{{%tagihan_item}}', "jenis IN ('daftar', 'tindakan', 'lab', 'obat')");
        $this->addCheck('ck_ti_jumlah', '{{%tagihan_item}}', 'jumlah > 0');
        $this->addCheck('ck_ti_harga', '{{%tagihan_item}}', 'harga >= 0');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%tagihan_item}}');
    }
}
