<?php

use yii\db\Migration;

class m261124_180001_create_index_performa extends Migration
{
    public function safeUp(): void
    {
        $this->createIndex('ix_kunjungan_status_tgl', '{{%kunjungan}}', ['status', 'tgl_kunjungan']);
        $this->createIndex('ix_resep_kunjungan', '{{%resep}}', 'kunjungan_id');
        $this->createIndex('ix_tagihan_item_tagihan', '{{%tagihan_item}}', 'tagihan_id');
        $this->createIndex('ix_pembayaran_tagihan', '{{%pembayaran}}', 'tagihan_id');
    }

    public function safeDown(): void
    {
        $this->dropIndex('ix_pembayaran_tagihan', '{{%pembayaran}}');
        $this->dropIndex('ix_tagihan_item_tagihan', '{{%tagihan_item}}');
        $this->dropIndex('ix_resep_kunjungan', '{{%resep}}');
        $this->dropIndex('ix_kunjungan_status_tgl', '{{%kunjungan}}');
    }
}
