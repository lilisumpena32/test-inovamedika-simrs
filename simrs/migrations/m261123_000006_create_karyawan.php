<?php

use yii\db\Migration;

class m261123_000006_create_karyawan extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%karyawan}}', [
            'id' => "varchar(50) NOT NULL PRIMARY KEY DEFAULT gen_random_uuid()::text",
            'kode_karyawan' => $this->string(16)->notNull()->unique(),
            'nama_karyawan' => $this->string(120)->notNull(),
            'jenis_kelamin' => $this->char(1)->notNull(),
            'tanggal_lahir' => $this->date(),
            'jabatan_id' => $this->string(50)->notNull(),
            'spesialis_id' => $this->string(50),
            'no_hp' => $this->string(20),
            'user_id' => $this->string(50),
            'tanggal_bergabung' => $this->date(),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'create_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'update_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'create_by' => $this->string(50),
            'update_by' => $this->string(50),
        ]);

        $this->addForeignKey('fk_karyawan_jabatan', '{{%karyawan}}', 'jabatan_id', '{{%jabatan}}', 'id');
        $this->addForeignKey('fk_karyawan_spesialis', '{{%karyawan}}', 'spesialis_id', '{{%spesialis}}', 'id');
        $this->addForeignKey('fk_karyawan_user', '{{%karyawan}}', 'user_id', '{{%user}}', 'id');
        $this->execute("CREATE UNIQUE INDEX uq_karyawan_user ON {{%karyawan}} (user_id) WHERE user_id IS NOT NULL");
        $this->execute("ALTER TABLE {{%karyawan}} ADD CONSTRAINT ck_karyawan_jk CHECK (jenis_kelamin IN ('L', 'P'))");
        $this->addForeignKey('fk_karyawan_create_by', '{{%karyawan}}', 'create_by', '{{%user}}', 'id', 'SET NULL');
        $this->addForeignKey('fk_karyawan_update_by', '{{%karyawan}}', 'update_by', '{{%user}}', 'id', 'SET NULL');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%karyawan}}');
    }
}
