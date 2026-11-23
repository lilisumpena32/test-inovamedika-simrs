<?php

use yii\db\Migration;

class m261123_000005_create_pasien extends Migration
{
    public function safeUp(): void
    {
        $this->execute('CREATE SEQUENCE seq_no_rm START 1');

        $this->createTable('{{%pasien}}', [
            'id' => "varchar(50) NOT NULL PRIMARY KEY DEFAULT gen_random_uuid()::text",
            'no_rm' => $this->string(12)->notNull()->unique(),
            'nik' => $this->string(16),
            'nama' => $this->string(120)->notNull(),
            'tanggal_lahir' => $this->date(),
            'jenis_kelamin' => $this->char(1)->notNull(),
            'no_hp' => $this->string(20),
            'alamat' => $this->text(),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'create_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'update_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'create_by' => $this->string(50),
            'update_by' => $this->string(50),
        ]);

        $this->execute("CREATE UNIQUE INDEX uq_pasien_nik ON {{%pasien}} (nik) WHERE nik IS NOT NULL AND nik <> ''");
        $this->execute("ALTER TABLE {{%pasien}} ADD CONSTRAINT ck_pasien_jk CHECK (jenis_kelamin IN ('L', 'P'))");
        $this->addForeignKey('fk_pasien_create_by', '{{%pasien}}', 'create_by', '{{%user}}', 'id', 'SET NULL');
        $this->addForeignKey('fk_pasien_update_by', '{{%pasien}}', 'update_by', '{{%user}}', 'id', 'SET NULL');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%pasien}}');
        $this->execute('DROP SEQUENCE IF EXISTS seq_no_rm');
    }
}
