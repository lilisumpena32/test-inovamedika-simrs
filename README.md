# SIMRS Inova Medika

Sistem Informasi Manajemen Rumah Sakit untuk **pelayanan rawat jalan terintegrasi** — dari pendaftaran, pemeriksaan dokter (EMR), tindakan, resep farmasi, pemeriksaan penunjang laboratorium, hingga tagihan dan pembayaran.

| Komponen | Teknologi |
|---|---|
| Frontend | React 19 + TypeScript + Tailwind 4 + TanStack Query |
| Backend utama (SIMRS) | Yii2 (PHP 8.3), prefix `/api/v1` |
| Backend services | Node.js 20 (bridge lab + REST API dashboard) |
| Database | PostgreSQL 16 (schema `simrs`) |
| API Testing | Postman (`postman/`) + Codeception (suite `Api`) |
| Version control | Git |

## Menjalankan

Prasyarat: Docker Desktop dengan Compose, Node tidak wajib di host (service Node jalan di container).

```bash
# 1. Nyalakan stack (db + php-fpm + nginx + node-api + lab-mock)
docker compose up -d --wait

# 2. Skema database
docker compose exec -T -w /var/www/simrs php php yii migrate --interactive=0

# 3. Data dasar: role, menu, hak akses, jabatan/spesialis
docker compose exec -T -w /var/www/simrs php php yii rbac/init

# 4. Seed demo (insert-only, aman diulang)
docker compose exec -T -w /var/www/simrs php php yii seed/admin    # akun admin
docker compose exec -T -w /var/www/simrs php php yii seed/master   # master + kunjungan demo

# 5. Frontend (hasil build dilayani nginx dari frontend/dist)
cd frontend && npm install && npm run build
```

Aplikasi: `http://localhost:8080`. API: `http://localhost:8080/api/v1`. Dashboard Node: `http://localhost:3001`.

## Akun demo

| Bagian | Username | Password |
|---|---|---|
| Administrator | `admin` | `admin123` |
| Petugas Pendaftaran | `sari` | `password123` |
| Dokter | `andi` | `password123` |
| Petugas Farmasi | `maya` | `password123` |
| Petugas Laboratorium | `lina` | `password123` |
| Kasir | `budi` | `password123` |

Kunjungan demo hari ini (dari `seed/master`, idempoten) berada lintas status: `menunggu`, `diperiksa` (dengan order lab), `menunggu_obat` (resep siap diserahkan), dan `menunggu_bayar` (dengan tindakan) — sehingga setiap layar transaksi punya data.

## Pengujian

```bash
# Suite Codeception (77 test). Hentikan bridge dulu agar worker tidak
# memproses outbox yang dibuat fixture uji.
docker compose stop node-api
docker compose exec -T -w /var/www/simrs php composer tests
docker compose start node-api

# Data performa: 100 pasien + 95 rb kunjungan untuk uji performa (opsional)
docker compose exec -T -w /var/www/simrs php php yii seed/performa
```

Koleksi Postman: `postman/SIMRS-rawat-jalan.postman_collection.json` — set variabel `baseUrl`, jalankan request **Auth → Login** (token tersimpan otomatis).

Pencek alur browser (Playwright) dan bukti EXPLAIN performa ada di `docs/bukti/`.

## Struktur

```
inovamedika-simrs/
├── docker/            # image PHP-FPM + konfigurasi nginx
├── db/init/           # role & schema awal (simrs_app, simrs_svc)
├── simrs/             # aplikasi Yii2 (api module, models, migrations, tests)
├── frontend/          # aplikasi React
├── services/
│   ├── backend-node/  # worker bridge lab + REST API dashboard (Node)
│   └── lab-mock/      # simulasi lab pihak ketiga
├── postman/           # koleksi Postman
└── docs/              # arsitektur, ERD, API, pengujian, bukti
```

## Pembagian tanggung jawab Yii vs Node.js

Business logic klinik seluruhnya di **Yii**: order lab, status kunjungan (state machine di fungsi database), hasil pemeriksaan, tagihan, pembayaran, FEFO stok.

**Node.js** tidak memegang logika klinik; ia hanya:

1. **Worker bridge lab** — polling `outbox_lab` (koneksi `simrs_svc`), mengirim permintaan analisa ke lab pihak ketiga (mock), menuliskan hasil balik ke outbox, lalu memanggil `POST /api/v1/lab/pull-hasil` (API key) agar **Yii** yang menerapkan hasil ke order lab. Gagal kirim dicatat (`attempts`, `last_error`, maksimal 5 → `failed`) — data tidak hilang, dapat diulang setelah layanan pulih.
2. **REST API dashboard (read-only)** — `GET /api/dashboard?dari=&sampai=` membaca agregat operasional lewat koneksi terbatas `simrs_svc`.

Kedua layanan saling melengkapi tanpa duplikasi logic: Yii satu-satunya penulis data bisnis (worker Node menulis hanya tabel outbox); Node membaca untuk dashboard.

## Keputusan teknis utama

- **PK/FK `varchar(50)` UUID4** dengan default `gen_random_uuid()::text`; semua tabel punya kolom audit `create_date/create_by/update_date/update_by` (WIB).
- **Otorisasi**: tabel sendiri `role`, `menu`, `role_access` (5 flag `can_*`), `user_access` — menu efektif = irisan keduanya, dibaca dari database tiap request (bukan klaim JWT). Endpoint lintas modul mendukung guard multi-menu (OR).
- **State machine kunjungan** ditegakkan oleh fungsi database `fn_ubah_status_kunjungan` — transisi melawan aturan ditolak 422.
- **Idempotency**: pendaftaran (`idempotency_key` wajib) dan pembayaran (key unik; retry tidak menggandakan transaksi).
- **Nomor antrean**: counter atomik per poli per tanggal (`INSERT … ON CONFLICT … RETURNING`), ditampilkan sebagai `{initial poli}-{nomor 3 digit}`.
- **FEFO stok**: penyerahan resep mengunci batch (`FOR UPDATE`) lalu mengalokasikan mulai kadaluarsa terdekat; stok minus mustahil.
- **Snapshot tagihan**: harga disalin saat tagihan dibuat — perubahan master sesudahnya tidak mengubah tagihan; perubahan tercatat di `log_aktivitas` (telusur).
- **Outbox pattern** untuk integrasi lab: gagal kirim bukan kegagalan transaksi klinik.
- **Data performa** (`seed/performa`) memakai prefix kunjungan `PF-` agar tidak tersapu fixture uji (`KJ-%`).

## Dokumen lanjutan

- [Arsitektur](docs/arsitektur.md)
- [ERD](docs/erd.md)
- [Dokumentasi API](docs/api.md)
- [Pengujian](docs/pengujian.md)
- [Bukti implementasi](docs/bukti/)
