# Dokumentasi API

Koleksi Postman siap pakai: `postman/SIMRS-rawat-jalan.postman_collection.json` (variabel `baseUrl`; request login menyimpan token otomatis).

Envelop konsisten:

- Sukses: `{ "status": true, "code": 200, "data": …, "message": "…" }`
- Gagal: `{ "status": false, "code": 4xx/5xx, "message": "…" }`

Otentikasi: `Authorization: Bearer <token>` (dari `POST /auth/login`). Kecuali endpoint login dan `POST /lab/pull-hasil` (API key worker).

## Auth & menu (Yii)

| Method | Path | Guard | Keterangan |
|---|---|---|---|
| POST | `/auth/login` | — | `{username, password}` → token + profil |
| GET | `/auth/me` | JWT | Profil + hak user |
| GET | `/menu` | JWT | Menu efektif (irisan user_access & role_access) |

## User & role (Yii, tahap 1B)

| Method | Path | Keterangan |
|---|---|---|
| GET | `/user?q=&role=&status=&page=` | List user (filter, pagination) |
| POST | `/user` | Buat user (sinkron user_access) |
| GET | `/user/{id}` | Detail user |
| PUT | `/user/{id}` | Ubah user (ganti role → sinkron ulang) |
| PUT | `/user/{id}/status` | Aktif/nonaktif (diri sendiri ditolak) |
| DELETE | `/user/{id}` | Soft delete (diri sendiri ditolak) |
| PUT | `/user/{id}/password` | Ganti password |
| GET | `/role` | List role + jumlah user |
| POST | `/role` | Buat role |
| PUT | `/role/{id}` | Ubah nama role |
| PUT | `/role/{id}/status` | Aktif/nonaktif (role sendiri ditolak) |
| PUT | `/role/{id}/access` | Tulis matriks hak role |

## Master (Yii)

| Method | Path | Menu guard |
|---|---|---|
| CRUD | `/pasien`, `/pasien/{id}`, `/pasien/{id}/status` | `master.pasien` |
| CRUD | `/poli` (termasuk `initial` 3 huruf unik) | `master.poli` |
| CRUD | `/karyawan` (+ `PUT /karyawan/{id}/akun`, `GET /user?tanpa_karyawan=1`) | `master.karyawan` |
| CRUD | `/jadwal` (multi-sesi dalam satu POST) | `master.jadwal` |
| CRUD | `/obat` (kategori/golongan/satuan wajib; list memuat stok) | `farmasi.obat` |
| CRUD | `/tindakan`, `GET /tindakan-list` (dropdown dokter) | `master.tindakan` |
| CRUD | `/jenis-lab`, `GET /jenis-lab-list` | `master.jenis-lab` |
| GET | `/jabatan`, `/spesialis`, `/satuan`, `/kategori`, `/golongan-obat` | mengikuti modul pemakai |

## Pendaftaran & antrean (Yii, tahap 3)

| Method | Path | Keterangan |
|---|---|---|
| POST | `/kunjungan` | `{idempotency_key (wajib), pasien_id, jadwal_id, tgl_kunjungan}` → nomor antrean atomik; retry key sama → 200 bukan dobel |
| GET | `/kunjungan?tanggal=&poli_id=&status=&page=` | List (dibaca pendaftaran, dokter, kasir) |
| GET | `/kunjungan/{id}` | Detail |
| PUT | `/kunjungan/{id}/batal` | Hanya status `menunggu` |
| GET | `/kunjungan/jadwal-tersedia?poli_id=&tanggal=` | Kuota/terisi/sisa per jadwal |
| GET | `/antrean?poli_id=&tanggal=` | Daftar antrean (kode `ANK-001` dst.) |

## Pemeriksaan (Yii, tahap 4)

| Method | Path | Keterangan |
|---|---|---|
| POST | `/kunjungan/{id}/pemeriksaan` | EMR; `menunggu` → `diperiksa`; diagnosis utama wajib |
| GET | `/kunjungan/{id}/pemeriksaan` | Baca EMR |
| POST | `/kunjungan/{id}/tindakan` | Tambah tindakan (tarif disalin) |
| GET | `/kunjungan/{id}/tindakan` | List tindakan kunjungan |
| DELETE | `/kunjungan/{id}/tindakan/{detailId}` | Batalkan baris tindakan |
| POST | `/kunjungan/{id}/lab` | Minta lab (tarif disalin) → masuk outbox |
| GET | `/kunjungan/{id}/lab` | Order lab kunjungan (+hasil) |
| PUT | `/kunjungan/{id}/selesai` | Tutup tanpa resep → `menunggu_bayar` |

## Laboratorium (Yii, tahap 4 & 7)

| Method | Path | Keterangan |
|---|---|---|
| GET | `/lab?tanggal=&status=` | Daftar order lab (petugas lab) |
| PUT | `/lab/{id}/hasil` | Input hasil manual (petugas lab) |
| POST | `/lab/pull-hasil` | **Internal worker**: header `X-Api-Key`, menerapkan hasil outbox `sent` |

## Farmasi (Yii, tahap 5)

| Method | Path | Keterangan |
|---|---|---|
| POST | `/kunjungan/{id}/resep` | Buat resep (validasi stok) → `menunggu_obat` |
| PUT | `/kunjungan/{id}/resep` | Ubah item selama resep `menunggu` |
| GET | `/farmasi/resep?tanggal=&status=` | Daftar resep farmasi |
| GET | `/resep/{id}` | Detail resep |
| GET | `/kunjungan/{id}/resep` | Resep berdasarkan kunjungan |
| PUT | `/resep/{id}/serah` | Serahkan obat: FEFO + mutasi keluar → kunjungan `menunggu_bayar` |

## Tagihan & pembayaran (Yii, tahap 6)

| Method | Path | Keterangan |
|---|---|---|
| POST | `/tagihan` | Snapshot biaya (daftar+tindakan+lab+obat); dobel → 200 |
| GET | `/tagihan?tanggal=&status=` | List tagihan |
| GET | `/tagihan/{id}` | Rincian + riwayat pembayaran + sisa |
| PUT | `/tagihan/{id}/bayar` | `{idempotency_key, metode, nominal}`; bertahap; lunas → kunjungan `selesai` |

## Laporan (Yii, tahap 8)

| Method | Path | Keterangan |
|---|---|---|
| GET | `/dashboard/ringkasan?dari=&sampai=` | Kunjungan total/per poli, tindakan, status pembayaran |
| GET | `/dashboard/pendapatan?dari=&sampai=` | Pendapatan total/per metode/per hari |
| GET | `/dashboard/obat?dari=&sampai=` | Obat terpakai per nama obat |

## Backend services (Node)

`backend-node` — port 3001:

| Method | Path | Keterangan |
|---|---|---|
| GET | `/health` | Status layanan (tanpa key) |
| GET | `/api/dashboard?dari=&sampai=` | Ringkasan operasional read-only, header `x-api-key` |

`lab-mock` — port 3002 di jaringan compose (tidak diekspos ke host; simulasi lab pihak ketiga, header `x-api-key`):

| Method | Path | Keterangan |
|---|---|---|
| GET | `/health` | Status layanan |
| POST | `/analisa` | Terima permintaan analisa |
| GET | `/analisa/{id}` | Hasil simulasi lab |

Worker `backend-node` memanggil `POST /analisa` lalu meneruskan hasilnya ke Yii lewat `POST /api/v1/lab/pull-hasil`; tidak ada business logic klinik di Node.

Semua response Node memakai envelope yang sama dengan Yii.
