<?php

use yii\db\Migration;

class m261124_170001_create_outbox_lab extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%outbox_lab}}', [
            'id' => "varchar(50) NOT NULL PRIMARY KEY DEFAULT gen_random_uuid()::text",
            'order_lab_id' => $this->string(50)->notNull(),
            'payload' => $this->json()->notNull(),
            'status' => $this->string(16)->notNull()->defaultValue('pending'),
            'attempts' => $this->integer()->notNull()->defaultValue(0),
            'hasil' => $this->text(),
            'last_error' => $this->text(),
            'create_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'update_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'create_by' => $this->string(50),
            'update_by' => $this->string(50),
        ]);

        $this->addForeignKey('fk_outbox_order', '{{%outbox_lab}}', 'order_lab_id', '{{%order_lab}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_outbox_create_by', '{{%outbox_lab}}', 'create_by', '{{%user}}', 'id', 'SET NULL');
        $this->addForeignKey('fk_outbox_update_by', '{{%outbox_lab}}', 'update_by', '{{%user}}', 'id', 'SET NULL');
        $this->addCheck('ck_outbox_status', '{{%outbox_lab}}', "status IN ('pending', 'sent', 'applied', 'failed')");

        $this->createIndex('ix_outbox_status', '{{%outbox_lab}}', 'status');

        // Worker Node memakai koneksi simrs_svc: baca order + tulis outbox.
        $this->execute('GRANT SELECT ON {{%order_lab}}, {{%jenis_lab}}, {{%pasien}}, {{%poli}}, {{%kunjungan}} TO simrs_svc');
        $this->execute('GRANT SELECT ON {{%pemeriksaan_tindakan}}, {{%tagihan}}, {{%pembayaran}}, {{%resep_obat}}, {{%resep}}, {{%obat}} TO simrs_svc');
        $this->execute('GRANT SELECT, INSERT, UPDATE ON {{%outbox_lab}} TO simrs_svc');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%outbox_lab}}');
    }
}
