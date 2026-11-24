<?php

use yii\db\Migration;

class m261124_150001_create_resep extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%resep}}', [
            'id' => "varchar(50) NOT NULL PRIMARY KEY DEFAULT gen_random_uuid()::text",
            'kunjungan_id' => $this->string(50)->notNull()->unique(),
            'status' => $this->string(16)->notNull()->defaultValue('menunggu'),
            'create_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'update_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'create_by' => $this->string(50),
            'update_by' => $this->string(50),
        ]);

        $this->addForeignKey('fk_resep_kunjungan', '{{%resep}}', 'kunjungan_id', '{{%kunjungan}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_resep_create_by', '{{%resep}}', 'create_by', '{{%user}}', 'id', 'SET NULL');
        $this->addForeignKey('fk_resep_update_by', '{{%resep}}', 'update_by', '{{%user}}', 'id', 'SET NULL');
        $this->addCheck('ck_resep_status', '{{%resep}}', "status IN ('menunggu', 'selesai', 'batal')");
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%resep}}');
    }
}
