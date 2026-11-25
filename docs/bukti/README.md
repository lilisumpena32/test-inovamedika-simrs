# Bukti implementasi

Screenshot diambil dari aplikasi yang berjalan dengan data demo (`yii seed/master`) memakai Playwright (Edge headless), viewport desktop 1360 px dan mobile 390 px.

| Berkas | Tahap | Isi |
|---|---|---|
| `1-login.png` | 1 | Halaman login |
| `1-dashboard-admin.png` | 1, 8 | Dashboard admin: sapaan + seluruh menu (Master, Pendaftaran, Farmasi, Pemeriksaan, Laboratorium, Kasir, Laporan, Pengaturan) |
| `1-user.png` | 1 | List user |
| `1-role.png` | 1 | List role |
| `1-sidebar-dokter.png` | 1 | Sidebar dokter: hanya Dashboard dan Antrian Periksa (tanpa Kunjungan/User) |
| `1-akses-ditolak.png` | 1 | Dokter membuka `/setting/user` langsung → Akses ditolak |
| `1-mobile.png` | 1 | Tampilan mobile halaman pemeriksaan |
| `2-master-pasien.png` | 2 | List pasien |
| `2-master-karyawan.png` | 2 | List karyawan |
| `2-master-jadwal.png` | 2 | List jadwal praktik |
| `3-list-kunjungan.png` | 3 | List kunjungan |
| `3-antrean.png` | 3 | Antrean per poli dengan kode `{initial}-{nomor}` |
| `4-antrian-periksa.png` | 4 | Antrian periksa dokter |
| `4-emr.png` | 4 | Form EMR + tindakan + order lab + resep |
| `5-resep-dokter.png` | 5 | Resep dari dokter |
| `5-resep-diserahkan.png` | 5 | Resep setelah diserahkan farmasi |
| `6-tagihan.png` | 6 | Detail tagihan + pembayaran bertahap |
| `6-invoice.png` | 6 | Invoice siap cetak |
| `7-lab-hasil.png` | 7 | Hasil lab masuk dari bridge/mock lab |
| `7-lab/gangguan-integrasi.txt` | 7 | Transkrip D5: mock lab mati → `attempts` naik → `failed`, order tetap `diminta`; pulih + requeue → `applied`, order `selesai` |
| `8-laporan-operasional.png` | 8 | Laporan operasional dengan filter periode |
| `8-performa/explain-sebelum.txt` | 8 | `EXPLAIN (ANALYZE, BUFFERS)` sebelum index (95.005 kunjungan) |
| `8-performa/explain-sesudah.txt` | 8 | `EXPLAIN (ANALYZE, BUFFERS)` sesudah index |

Ringkasan performa: query obat terpakai **18,97 ms → 7,02 ms** (index `resep.kunjungan_id`), query kunjungan per poli 56,65 ms → 50,51 ms. Data performa dibuat oleh `php yii seed/performa` (100 pasien + 95.000 kunjungan selesai, prefix `PF-`).

Cara mengulang bukti:

```bash
docker compose exec -T -w /var/www/simrs php php yii seed/master    # data demo
docker compose exec -T -w /var/www/simrs php php yii seed/performa  # data performa
docker compose exec -T -w /var/www/simrs php composer tests         # suite API
```

Skenario teknis D1–D6 dan hasil tiap suite dijelaskan di [../pengujian.md](../pengujian.md).
