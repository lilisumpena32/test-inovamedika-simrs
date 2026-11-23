<?php

use yii\db\Migration;

class m261123_000001_create_log_aktivitas extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%log_aktivitas}}', [
            'id' => "varchar(50) NOT NULL PRIMARY KEY DEFAULT gen_random_uuid()::text",
            'tabel' => $this->string(64)->notNull(),
            'record_id' => $this->string(50)->notNull(),
            'aksi' => $this->string(16)->notNull(),
            'data_lama' => 'jsonb',
            'data_baru' => 'jsonb',
            'ip' => $this->string(45),
            'create_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'update_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'create_by' => $this->string(50),
            'update_by' => $this->string(50),
        ]);

        $this->addForeignKey('fk_log_aktivitas_create_by', '{{%log_aktivitas}}', 'create_by', '{{%user}}', 'id', 'SET NULL');
        $this->addForeignKey('fk_log_aktivitas_update_by', '{{%log_aktivitas}}', 'update_by', '{{%user}}', 'id', 'SET NULL');
        $this->createIndex('ix_log_aktivitas_record', '{{%log_aktivitas}}', ['tabel', 'record_id']);

        $this->execute(<<<'SQL'
CREATE OR REPLACE FUNCTION fn_simpan_log(
    p_tabel text,
    p_record_id text,
    p_aksi text,
    p_data_lama jsonb,
    p_data_baru jsonb,
    p_ip text,
    p_pelaku text
) RETURNS void AS $fn$
BEGIN
    INSERT INTO log_aktivitas (tabel, record_id, aksi, data_lama, data_baru, ip, create_by, update_by)
    VALUES (p_tabel, p_record_id, p_aksi, p_data_lama, p_data_baru, p_ip, p_pelaku, p_pelaku);
END;
$fn$ LANGUAGE plpgsql;
SQL);
    }

    public function safeDown(): void
    {
        $this->execute('DROP FUNCTION IF EXISTS fn_simpan_log(text, text, text, jsonb, jsonb, text, text)');
        $this->dropTable('{{%log_aktivitas}}');
    }
}
