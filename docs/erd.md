# ERD — SIMRS Rawat Jalan

Semua PK/FK `varchar(50)` UUID4. Setiap tabel punya kolom audit `create_date, create_by, update_date, update_by` (tidak digambar agar ringkas).

```mermaid
erDiagram
    ROLE ||--o{ USER : "role_id"
    ROLE ||--o{ ROLE_ACCESS : ""
    MENU ||--o{ ROLE_ACCESS : ""
    MENU ||--o{ USER_ACCESS : ""
    USER ||--o{ USER_ACCESS : ""
    USER ||--o{ LOG_AKTIVITAS : "pelaku"
    USER ||--o| KARYAWAN : "akun"
    JABATAN ||--o{ KARYAWAN : ""
    SPESIALIS ||--o{ KARYAWAN : ""

    PASIEN ||--o{ KUNJUNGAN : ""
    POLI ||--o{ KUNJUNGAN : ""
    KARYAWAN ||--o{ KUNJUNGAN : "dokter_id"
    JADWAL_PRAKTEK ||--o{ KUNJUNGAN : "jadwal_id (NOT NULL)"
    KARYAWAN ||--o{ JADWAL_PRAKTEK : ""
    POLI ||--o{ JADWAL_PRAKTEK : ""
    POLI ||--o{ COUNTER_ANTREAN : "nomor per tanggal"
    KUNJUNGAN ||--o| PEMERIKSAAN : ""
    KUNJUNGAN ||--o{ PEMERIKSAAN_TINDAKAN : ""
    TINDAKAN ||--o{ PEMERIKSAAN_TINDAKAN : "tarif disalin"
    KUNJUNGAN ||--o{ ORDER_LAB : ""
    JENIS_LAB ||--o{ ORDER_LAB : "tarif disalin"
    ORDER_LAB ||--o{ OUTBOX_LAB : ""

    KUNJUNGAN ||--o| RESEP : ""
    RESEP ||--o{ RESEP_OBAT : ""
    OBAT ||--o{ RESEP_OBAT : ""
    KATEGORI ||--o{ OBAT : ""
    GOLONGAN_OBAT ||--o{ OBAT : ""
    SATUAN ||--o{ OBAT : ""
    OBAT ||--o{ STOK_OBAT : "per batch"
    STOK_OBAT ||--o{ MUTASI_OBAT : ""
    OBAT ||--o{ MUTASI_OBAT : ""

    KUNJUNGAN ||--o| TAGIHAN : ""
    TAGIHAN ||--o{ TAGIHAN_ITEM : "snapshot harga"
    TAGIHAN ||--o{ PEMBAYARAN : "idempotency_key unik"

    USER {
        varchar id
        varchar username
        varchar password_hash
        varchar full_name
        varchar role_id FK
        bool is_active
        bool is_delete
    }
    KUNJUNGAN {
        varchar id
        varchar no_kunjungan UK
        varchar pasien_id FK
        varchar dokter_id FK
        varchar poli_id FK
        varchar jadwal_id FK
        date tgl_kunjungan
        int no_antrean
        varchar status
        numeric biaya_daftar
        varchar idempotency_key UK
    }
    PEMERIKSAAN {
        varchar kunjungan_id UK
        text keluhan
        text anamnesis
        varchar tensi
        numeric suhu
        int nadi
        varchar diagnosis_utama
        varchar diagnosis_sekunder
    }
    ORDER_LAB {
        varchar kunjungan_id FK
        varchar jenis_lab_id FK
        varchar status
        text hasil
        timestamp waktu_hasil
    }
    RESEP {
        varchar kunjungan_id UK
        varchar status
    }
    RESEP_OBAT {
        varchar resep_id FK
        varchar obat_id FK
        varchar dosis
        varchar frekuensi
        int jumlah
    }
    STOK_OBAT {
        varchar obat_id FK
        varchar no_batch
        date kadaluarsa
        int jumlah ">= 0"
    }
    TAGIHAN {
        varchar no_tagihan UK
        varchar kunjungan_id UK
        varchar status
        numeric total
    }
    TAGIHAN_ITEM {
        varchar tagihan_id FK
        varchar jenis
        varchar uraian
        int jumlah
        numeric harga
    }
    PEMBAYARAN {
        varchar tagihan_id FK
        varchar idempotency_key UK
        varchar metode
        numeric nominal
        timestamp waktu
    }
    OUTBOX_LAB {
        varchar order_lab_id FK
        jsonb payload
        varchar status
        int attempts
        text hasil
        text last_error
    }
```

Relasi dan aturan penting:

- `kunjungan`: unik `(poli, tanggal, no_antrean)`; unik parsial `(pasien, jadwal, tanggal) WHERE status <> 'batal'` dengan `jadwal_id NOT NULL` (pendaftaran selalu lewat jadwal).
- `order_lab`: unik parsial `(kunjungan, jenis_lab) WHERE status <> 'batal'`.
- `stok_obat`: unik `(obat, no_batch)`; check `jumlah >= 0`.
- `tagihan`: unik per `kunjungan`; `pembayaran.nominal > 0` dan tidak boleh melebihi sisa (dicek aplikasi).
