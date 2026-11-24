<?php

use yii\db\Migration;

class m261123_130003_add_fn_ubah_status_kunjungan extends Migration
{
    public function safeUp(): void
    {
        $this->execute(<<<'SQL'
CREATE OR REPLACE FUNCTION fn_ubah_status_kunjungan(
    p_id text,
    p_status text
) RETURNS void AS $fn$
DECLARE
    v_status_lama text;
BEGIN
    SELECT status INTO v_status_lama FROM kunjungan WHERE id = p_id FOR UPDATE;

    IF v_status_lama IS NULL THEN
        RAISE EXCEPTION 'Kunjungan tidak ditemukan' USING ERRCODE = 'P0002';
    END IF;

    IF NOT (
        (v_status_lama = 'menunggu' AND p_status IN ('diperiksa', 'batal'))
        OR (v_status_lama = 'diperiksa' AND p_status IN ('menunggu_obat', 'menunggu_bayar'))
        OR (v_status_lama = 'menunggu_obat' AND p_status = 'menunggu_bayar')
        OR (v_status_lama = 'menunggu_bayar' AND p_status = 'selesai')
        OR (v_status_lama IN ('menunggu', 'diperiksa', 'menunggu_obat', 'menunggu_bayar') AND p_status = 'batal')
    ) THEN
        RAISE EXCEPTION 'Transisi status % ke % tidak diizinkan', v_status_lama, p_status USING ERRCODE = 'P0001';
    END IF;

    UPDATE kunjungan
    SET status = p_status,
        update_date = (now() AT TIME ZONE 'Asia/Jakarta')
    WHERE id = p_id;
END;
$fn$ LANGUAGE plpgsql;
SQL);
    }

    public function safeDown(): void
    {
        $this->execute('DROP FUNCTION IF EXISTS fn_ubah_status_kunjungan(text, text)');
    }
}
