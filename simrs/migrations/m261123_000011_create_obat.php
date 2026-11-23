<?php

use yii\db\Migration;

class m261123_000011_create_obat extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%obat}}', [
            'id' => "varchar(50) NOT NULL PRIMARY KEY DEFAULT gen_random_uuid()::text",
            'kode_obat' => $this->string(16)->notNull()->unique(),
            'nama_obat' => $this->string(120)->notNull(),
            'kategori_id' => $this->string(50)->notNull(),
            'golongan_obat_id' => $this->string(50)->notNull(),
            'satuan_id' => $this->string(50)->notNull(),
            'harga_beli' => $this->decimal(12, 2)->notNull()->defaultValue(0),
            'harga_jual' => $this->decimal(12, 2)->notNull()->defaultValue(0),
            'stok_minimum' => $this->integer()->notNull()->defaultValue(0),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'create_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'update_date' => $this->timestamp(0)->notNull()->defaultExpression("(now() AT TIME ZONE 'Asia/Jakarta')"),
            'create_by' => $this->string(50),
            'update_by' => $this->string(50),
        ]);

        $this->addForeignKey('fk_obat_kategori', '{{%obat}}', 'kategori_id', '{{%kategori}}', 'id');
        $this->addForeignKey('fk_obat_golongan_obat', '{{%obat}}', 'golongan_obat_id', '{{%golongan_obat}}', 'id');
        $this->addForeignKey('fk_obat_satuan', '{{%obat}}', 'satuan_id', '{{%satuan}}', 'id');
        $this->execute("ALTER TABLE {{%obat}} ADD CONSTRAINT ck_obat_harga_beli CHECK (harga_beli >= 0)");
        $this->execute("ALTER TABLE {{%obat}} ADD CONSTRAINT ck_obat_harga_jual CHECK (harga_jual >= 0)");
        $this->execute("ALTER TABLE {{%obat}} ADD CONSTRAINT ck_obat_stok_minimum CHECK (stok_minimum >= 0)");
        $this->addForeignKey('fk_obat_create_by', '{{%obat}}', 'create_by', '{{%user}}', 'id', 'SET NULL');
        $this->addForeignKey('fk_obat_update_by', '{{%obat}}', 'update_by', '{{%user}}', 'id', 'SET NULL');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%obat}}');
    }
}
