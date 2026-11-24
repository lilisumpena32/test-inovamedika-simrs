<?php

use yii\db\Migration;

class m261123_130001_create_kunjungan extends Migration
{
    public function safeUp(): void
    {
        $this->execute('CREATE SEQUENCE seq_kunjungan START 1');

        $this->createTable('{{%kunjungan}}', [
            'id' => "varchar(50) NOT NULL PRIMARY KEY DEFAULT gen_random_uuid()::text",
            'no_kunjungan' => $this->string(20)->notNull()->unique(),
            'pasien_id' => $this->string(50)->notNull(),
            'dokter_id' => $this->string(50)->notNull(),
            'poli_id' => $this->string(50)->notNull(),
            'jadwal_id' => $this->string(50)->notNull(),
            'tgl_kunjungan' => $this->date()->notNull(),
            'no_antrean' => $this->integer()->notNull(),
            'status' => $this->string(20)->notNull()->defaultValue('menunggu'),
            'biaya_daftar' => $this->decimal(12, 2)->notNull()->defaultValue(0),
            'idempotency_key' => $this->string(64)->notNull()->unique(),
            'create_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'update_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'create_by' => $this->string(50),
            'update_by' => $this->string(50),
        ]);

        $this->addForeignKey('fk_kunjungan_pasien', '{{%kunjungan}}', 'pasien_id', '{{%pasien}}', 'id');
        $this->addForeignKey('fk_kunjungan_dokter', '{{%kunjungan}}', 'dokter_id', '{{%karyawan}}', 'id');
        $this->addForeignKey('fk_kunjungan_poli', '{{%kunjungan}}', 'poli_id', '{{%poli}}', 'id');
        $this->addForeignKey('fk_kunjungan_jadwal', '{{%kunjungan}}', 'jadwal_id', '{{%jadwal_praktek}}', 'id');
        $this->addForeignKey('fk_kunjungan_create_by', '{{%kunjungan}}', 'create_by', '{{%user}}', 'id', 'SET NULL');
        $this->addForeignKey('fk_kunjungan_update_by', '{{%kunjungan}}', 'update_by', '{{%user}}', 'id', 'SET NULL');
        $this->execute("CREATE UNIQUE INDEX uq_kunjungan_pasien_jadwal ON {{%kunjungan}} (pasien_id, jadwal_id, tgl_kunjungan) WHERE (status <> 'batal')");
        $this->execute("ALTER TABLE {{%kunjungan}} ADD CONSTRAINT ck_kunjungan_status CHECK (status IN ('menunggu', 'diperiksa', 'menunggu_obat', 'menunggu_bayar', 'selesai', 'batal'))");
        $this->createIndex('ix_kunjungan_tanggal', '{{%kunjungan}}', ['tgl_kunjungan', 'poli_id']);
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%kunjungan}}');
        $this->execute('DROP SEQUENCE IF EXISTS seq_kunjungan');
    }
}
