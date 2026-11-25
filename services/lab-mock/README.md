# lab-mock (services/lab-mock)

Simulasi sistem laboratorium pihak ketiga untuk uji integrasi bridge SIMRS.

## Endpoint

| Method | Path | Keterangan |
|---|---|---|
| GET | `/health` | Status layanan |
| POST | `/analisa` | Terima permintaan analisa, langsung balik hasil simulasi |
| GET | `/analisa/{id}` | Baca analisa yang pernah dibuat |

Semua kecuali `/health` wajib header `x-api-key` (default `simrs-dev-mock-key`, ubah via `MOCK_API_KEY`).

Format response konsisten: `{status, code, data}` / `{status, code, message}`.

Menjalankan: `docker compose up -d lab-mock` (port 3002 di dalam jaringan compose).
