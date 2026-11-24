<?php

use yii\db\Migration;

class m261123_130002_create_counter_antrean extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%counter_antrean}}', [
            'poli_id' => $this->string(50)->notNull(),
            'tanggal' => $this->date()->notNull(),
            'nomor_terakhir' => $this->integer()->notNull()->defaultValue(0),
            'create_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'update_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'create_by' => $this->string(50),
            'update_by' => $this->string(50),
        ]);

        $this->addPrimaryKey('pk_counter_antrean', '{{%counter_antrean}}', ['poli_id', 'tanggal']);
        $this->addForeignKey('fk_counter_antrean_poli', '{{%counter_antrean}}', 'poli_id', '{{%poli}}', 'id');
        $this->addForeignKey('fk_counter_antrean_create_by', '{{%counter_antrean}}', 'create_by', '{{%user}}', 'id', 'SET NULL');
        $this->addForeignKey('fk_counter_antrean_update_by', '{{%counter_antrean}}', 'update_by', '{{%user}}', 'id', 'SET NULL');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%counter_antrean}}');
    }
}
