# Pengujian

Tiga lapis: suite Codeception (API), koleksi Postman, dan pencek alur browser (Playwright). Skenario teknis dokumen soal (bagian D) diuji eksplisit.

## 1. Codeception — `composer tests` (77 test / 470 assertion)

Jalankan:

```bash
docker compose stop node-api          # bridge tidak memproses outbox uji
docker compose exec -T -w /var/www/simrs php composer tests
docker compose start node-api
```

| Cest | Cakupan utama |
|---|---|
| `AuthCest` | login, password salah, token kedaluwarsa, user/role nonaktif, hak per role, pencabutan user_access berlaku langsung |
| `UserCest`, `RoleCest` | CRUD user/role, duplikat, ubah role sinkron user_access, nonaktif/hapus diri sendiri ditolak |
| `MasterCest` | pasien (NIK duplikat 422), poli (kode & initial unik), karyawan dokter (spesialis wajib), jadwal multi-sesi (overlap, bukan dokter), obat, link akun karyawan, log aktivitas |
| `KunjunganCest` | antrean berurutan 1-2, **idempotency pendaftaran** (D1), pasien dobel ditolak, tanggal bukan hari praktek, batal → daftar ulang, kode antrean `UJP-001`, kasir 403 |
| `PemeriksaanCest` | EMR menyimpan + status kunjungan, diagnosis wajib, tindakan tersalin tarif, order lab masuk outbox, **pull-hasil** (hasil eksternal diterapkan, API key salah 401), selesai tanpa resep |
| `ResepCest` | resep mengubah status kunjungan, **stok tak cukup ditolak** (D2), FEFO terbukti dari sisa batch 0/105, serah ulang ditolak, ubah item sebelum serah |
| `TagihanCest` | snapshot rincian (daftar+obat), tagihan dobel idempotent, **pembayaran bertahap** → sebagian → lunas → kunjungan selesai, **pembayaran ganda** satu baris (D3), nominal melebihi sisa, dokter 403 |
| `DashboardCest` | ringkasan atas 95 rb kunjungan, pendapatan, obat terpakai, periode salah 422 |

## 2. Postman — `postman/SIMRS-rawat-jalan.postman_collection.json`

Folder: Auth, Pendaftaran, Pemeriksaan, Laboratorium, Farmasi, Tagihan, Master, Pengaturan, Laporan. Jalankan `Auth → Login` lebih dulu (token tersimpan otomatis); variabel `tanggal` diisi otomatis oleh pre-request script koleksi. Bukti screenshot alur ada di `docs/bukti/`.

## 3. Pencek alur browser (Playwright, headless Edge)

Pencek dijalankan dari harness lokal terhadap aplikasi + data demo; screenshot hasilnya disimpan di `docs/bukti/` sebagai bukti yang dapat diperiksa tanpa menjalankan ulang skrip.

| Pemeriksaan | Alur | Hasil |
|---|---|---|
| cek-1a / cek-1b | login, guard, sidebar, CRUD user & role | 25/25, 39/39 |
| cek-2 | master pasien/poli/karyawan/jadwal/obat dari UI | 19/19 |
| cek-3 | kartu poli → modal daftar pasien (cari RM/nama, modal bertumpuk) → antrean → pembatalan | 25/25 |
| cek-4 | dokter EMR + tindakan + order lab + resep; farmasi serah; hasil lab masuk dari bridge | 19/19 |
| cek-5 | kasir buat tagihan Rp 43.500 → bayar bertahap → lunas → invoice | 11/11 |
| cek-8 | laporan operasional dengan data performa (6.005 kunjungan bulan ini) | 7/7 |
| final RBAC | dokter hanya melihat Dashboard + Antrian Periksa; membuka `/setting/user` langsung → Akses ditolak; `GET /user` → 403 | lolos |

Pemeriksaan manual yang setara: login tiap akun demo, pastikan sidebar sesuai matriks role, lalu buka URL menu terlarang — halaman harus menampilkan "Akses ditolak" dan API harus membalas 403.

## 4. Skenario teknis (bagian D dokumen soal)

| Skenario | Implementasi | Bukti |
|---|---|---|
| D1 Duplikasi pendaftaran | unik parsial + counter atomik `ON CONFLICT` | `KunjunganCest` (idempotency 200 bukan dobel) |
| D2 Stok tidak minus | lock batch `FOR UPDATE` + alokasi FEFO | `ResepCest` (sisa batch 0/105; serah ulang 422) |
| D3 Pembayaran ganda | `idempotency_key` unik di `pembayaran` | `TagihanCest` (retry key sama → satu baris) |
| D4 Perubahan setelah billing | tagihan snapshot + `log_aktivitas` before/after | `MasterCest` (log tercatat) + snapshot di `TagihanCest` |
| D5 Gangguan integrasi | outbox + retry + `failed` (data tidak hilang) | `docs/bukti/7-lab/gangguan-integrasi.txt` (mock mati → attempts naik, order tetap ada; pulih → `applied`) |
| D6 Performa | 95 rb kunjungan + EXPLAIN before/after | `docs/bukti/8-performa/` (18,97 → 7,02 ms) |
