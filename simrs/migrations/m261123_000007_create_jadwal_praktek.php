<?php

use yii\db\Migration;

class m261123_000007_create_jadwal_praktek extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%jadwal_praktek}}', [
            'id' => "varchar(50) NOT NULL PRIMARY KEY DEFAULT gen_random_uuid()::text",
            'karyawan_id' => $this->string(50)->notNull(),
            'poli_id' => $this->string(50)->notNull(),
            'hari' => $this->smallInteger()->notNull(),
            'jam_mulai' => $this->time(0)->notNull(),
            'jam_selesai' => $this->time(0)->notNull(),
            'kuota' => $this->integer()->notNull(),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'create_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'update_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'create_by' => $this->string(50),
            'update_by' => $this->string(50),
        ]);

        $this->addForeignKey('fk_jadwal_karyawan', '{{%jadwal_praktek}}', 'karyawan_id', '{{%karyawan}}', 'id');
        $this->addForeignKey('fk_jadwal_poli', '{{%jadwal_praktek}}', 'poli_id', '{{%poli}}', 'id');
        $this->execute("ALTER TABLE {{%jadwal_praktek}} ADD CONSTRAINT ck_jadwal_hari CHECK (hari BETWEEN 1 AND 7)");
        $this->execute("ALTER TABLE {{%jadwal_praktek}} ADD CONSTRAINT ck_jadwal_jam CHECK (jam_mulai < jam_selesai)");
        $this->execute("ALTER TABLE {{%jadwal_praktek}} ADD CONSTRAINT ck_jadwal_kuota CHECK (kuota > 0)");
        $this->createIndex('uq_jadwal_praktek', '{{%jadwal_praktek}}', ['karyawan_id', 'poli_id', 'hari', 'jam_mulai'], true);
        $this->addForeignKey('fk_jadwal_create_by', '{{%jadwal_praktek}}', 'create_by', '{{%user}}', 'id', 'SET NULL');
        $this->addForeignKey('fk_jadwal_update_by', '{{%jadwal_praktek}}', 'update_by', '{{%user}}', 'id', 'SET NULL');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%jadwal_praktek}}');
    }
}
