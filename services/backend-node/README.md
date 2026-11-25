# backend-node (services/backend-node)

Backend services Node.js untuk SIMRS rawat jalan.

## Tanggung jawab

- **Worker lab (bridge)**: mem-polling tabel `outbox_lab` (koneksi `simrs_svc`, read/write outbox, read-only tabel lain), mengirim permintaan analisa ke mock lab, menuliskan hasil balik ke outbox, lalu memanggil `POST /api/v1/lab/pull-hasil` (Yii, API key) agar business logic — penerapan hasil ke order lab — tetap milik Yii.
- **REST API dashboard (read-only)**: `GET /api/dashboard?dari=YYYY-MM-DD&sampai=YYYY-MM-DD` mengembalikan ringkasan operasional (kunjungan per poli, total tindakan, pendapatan, pemakaian obat, status pembayaran). Read-only via `simrs_svc`.

Business logic klinik (order lab, status hasil, billing) **tidak** ada di sini — hanya pengangkut data dan pembacaan agregat.

## Menjalankan

Sudah masuk `docker-compose.yml` (service `node-api`):

```
docker compose up -d node-api
```

Uji cepat:

```
curl -H "x-api-key: simrs-dev-node-key" "http://localhost:3001/api/dashboard?dari=2026-11-24&sampai=2026-11-24"
```

## Variabel lingkungan (compose)

| Variabel | Default | Keterangan |
|---|---|---|
| `PG_DSN` | postgres://simrs_svc:...@db:5432/simrs | Koneksi read/write terbatas |
| `PORT` | 3001 | Port REST API (dipetakan ke host) |
| `API_KEY` | simrs-dev-node-key | Kunci `x-api-key` untuk API Node |
| `MOCK_BASE` | http://lab-mock:3002 | Alamat mock lab |
| `MOCK_API_KEY` | simrs-dev-mock-key | Kunci untuk mock lab |
| `YII_BASE` | http://nginx/api/v1 | Alamat API Yii |
| `YII_API_KEY` | simrs-dev-lab-key | Kunci untuk `POST /lab/pull-hasil` |
| `INTERVAL_MS` | 5000 | Jeda polling worker |
| `MAX_ATTEMPTS` | 5 | Batas percobaan sebelum outbox `failed` |

## Endpoint

| Method | Path | Keterangan |
|---|---|---|
| GET | `/health` | Status layanan (tanpa API key) |
| GET | `/api/dashboard` | Ringkasan dashboard, header `x-api-key` wajib |

Format response konsisten dengan Yii: `{status, code, data}` / `{status, code, message}`.

## Catatan integrasi

- Saat menjalankan **suite Codeception**, hentikan service ini dulu (`docker compose stop node-api`) agar bridge tidak memproses outbox yang dibuat fixture uji.
- Outbox gagal berulang jadi `failed` (data tidak hilang); setelah layanan pulih, kembalikan ke `pending` untuk diproses ulang.
