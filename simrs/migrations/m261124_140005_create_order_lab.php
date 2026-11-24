<?php

use yii\db\Migration;

class m261124_140005_create_order_lab extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%order_lab}}', [
            'id' => "varchar(50) NOT NULL PRIMARY KEY DEFAULT gen_random_uuid()::text",
            'kunjungan_id' => $this->string(50)->notNull(),
            'jenis_lab_id' => $this->string(50)->notNull(),
            'tarif' => $this->decimal(12, 2)->notNull(),
            'status' => $this->string(16)->notNull()->defaultValue('diminta'),
            'hasil' => $this->text(),
            'diinput_by' => $this->string(50),
            'waktu_hasil' => $this->timestamp(0),
            'create_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'update_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'create_by' => $this->string(50),
            'update_by' => $this->string(50),
        ]);

        $this->addForeignKey('fk_ol_kunjungan', '{{%order_lab}}', 'kunjungan_id', '{{%kunjungan}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_ol_jenis_lab', '{{%order_lab}}', 'jenis_lab_id', '{{%jenis_lab}}', 'id');
        $this->addForeignKey('fk_ol_diinput_by', '{{%order_lab}}', 'diinput_by', '{{%user}}', 'id', 'SET NULL');
        $this->addForeignKey('fk_ol_create_by', '{{%order_lab}}', 'create_by', '{{%user}}', 'id', 'SET NULL');
        $this->addForeignKey('fk_ol_update_by', '{{%order_lab}}', 'update_by', '{{%user}}', 'id', 'SET NULL');
        $this->addCheck('ck_order_lab_status', '{{%order_lab}}', "status IN ('diminta', 'selesai', 'batal')");
        $this->execute("CREATE UNIQUE INDEX uq_order_lab ON {{%order_lab}} (kunjungan_id, jenis_lab_id) WHERE (status <> 'batal')");
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%order_lab}}');
    }
}
