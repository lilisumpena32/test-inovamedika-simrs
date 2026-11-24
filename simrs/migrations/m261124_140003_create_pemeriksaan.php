<?php

use yii\db\Migration;

class m261124_140003_create_pemeriksaan extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%pemeriksaan}}', [
            'id' => "varchar(50) NOT NULL PRIMARY KEY DEFAULT gen_random_uuid()::text",
            'kunjungan_id' => $this->string(50)->notNull()->unique(),
            'keluhan' => $this->text(),
            'anamnesis' => $this->text(),
            'tensi' => $this->string(10),
            'suhu' => $this->decimal(4, 1),
            'nadi' => $this->integer(),
            'pernapasan' => $this->integer(),
            'tinggi_cm' => $this->integer(),
            'berat_kg' => $this->integer(),
            'hasil_fisik' => $this->text(),
            'diagnosis_utama' => $this->string(128)->notNull(),
            'diagnosis_sekunder' => $this->string(128),
            'create_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'update_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'create_by' => $this->string(50),
            'update_by' => $this->string(50),
        ]);

        $this->addForeignKey('fk_pemeriksaan_kunjungan', '{{%pemeriksaan}}', 'kunjungan_id', '{{%kunjungan}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_pemeriksaan_create_by', '{{%pemeriksaan}}', 'create_by', '{{%user}}', 'id', 'SET NULL');
        $this->addForeignKey('fk_pemeriksaan_update_by', '{{%pemeriksaan}}', 'update_by', '{{%user}}', 'id', 'SET NULL');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%pemeriksaan}}');
    }
}
