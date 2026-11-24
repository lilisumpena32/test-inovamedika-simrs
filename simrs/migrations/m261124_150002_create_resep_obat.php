<?php

use yii\db\Migration;

class m261124_150002_create_resep_obat extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%resep_obat}}', [
            'id' => "varchar(50) NOT NULL PRIMARY KEY DEFAULT gen_random_uuid()::text",
            'resep_id' => $this->string(50)->notNull(),
            'obat_id' => $this->string(50)->notNull(),
            'dosis' => $this->string(50),
            'frekuensi' => $this->string(50),
            'jumlah' => $this->integer()->notNull(),
            'create_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'update_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'create_by' => $this->string(50),
            'update_by' => $this->string(50),
        ]);

        $this->addForeignKey('fk_ro_resep', '{{%resep_obat}}', 'resep_id', '{{%resep}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_ro_obat', '{{%resep_obat}}', 'obat_id', '{{%obat}}', 'id');
        $this->addForeignKey('fk_ro_create_by', '{{%resep_obat}}', 'create_by', '{{%user}}', 'id', 'SET NULL');
        $this->addForeignKey('fk_ro_update_by', '{{%resep_obat}}', 'update_by', '{{%user}}', 'id', 'SET NULL');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%resep_obat}}');
    }
}
