<?php

use yii\db\Migration;

class m261124_140004_create_pemeriksaan_tindakan extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%pemeriksaan_tindakan}}', [
            'id' => "varchar(50) NOT NULL PRIMARY KEY DEFAULT gen_random_uuid()::text",
            'kunjungan_id' => $this->string(50)->notNull(),
            'tindakan_id' => $this->string(50)->notNull(),
            'tarif' => $this->decimal(12, 2)->notNull(),
            'create_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'update_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'create_by' => $this->string(50),
            'update_by' => $this->string(50),
        ]);

        $this->addForeignKey('fk_pt_kunjungan', '{{%pemeriksaan_tindakan}}', 'kunjungan_id', '{{%kunjungan}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_pt_tindakan', '{{%pemeriksaan_tindakan}}', 'tindakan_id', '{{%tindakan}}', 'id');
        $this->addForeignKey('fk_pt_create_by', '{{%pemeriksaan_tindakan}}', 'create_by', '{{%user}}', 'id', 'SET NULL');
        $this->addForeignKey('fk_pt_update_by', '{{%pemeriksaan_tindakan}}', 'update_by', '{{%user}}', 'id', 'SET NULL');
        $this->createIndex('uq_pemeriksaan_tindakan', '{{%pemeriksaan_tindakan}}', ['kunjungan_id', 'tindakan_id'], true);
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%pemeriksaan_tindakan}}');
    }
}
