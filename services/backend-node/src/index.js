const express = require('express')
const { Pool } = require('pg')

const PORT = process.env.PORT || 3001
const API_KEY = process.env.API_KEY || 'simrs-dev-node-key'
const MOCK_BASE = process.env.MOCK_BASE || 'http://lab-mock:3002'
const MOCK_API_KEY = process.env.MOCK_API_KEY || 'simrs-dev-mock-key'
const YII_BASE = process.env.YII_BASE || 'http://nginx/api/v1'
const YII_API_KEY = process.env.YII_API_KEY || 'simrs-dev-lab-key'
const INTERVAL = Number(process.env.INTERVAL_MS || 5000)
const MAX_ATTEMPTS = Number(process.env.MAX_ATTEMPTS || 5)

const pool = new Pool({ connectionString: process.env.PG_DSN })

const app = express()
app.use(express.json())

function ralat(res, code, message) {
  return res.status(code).json({ status: false, code, message })
}

function auth(req, res, next) {
  if ((req.headers['x-api-key'] ?? '') !== API_KEY) {
    return ralat(res, 401, 'API key tidak valid')
  }

  next()
}

app.get('/health', (req, res) => {
  res.json({ status: true, code: 200, data: { layanan: 'backend-node', waktu: new Date().toISOString() } })
})

app.get('/api/dashboard', auth, async (req, res, next) => {
  try {
    const dari = String(req.query.dari || '').slice(0, 10)
    const sampai = String(req.query.sampai || '').slice(0, 10)

    if (!/^\d{4}-\d{2}-\d{2}$/.test(dari) || !/^\d{4}-\d{2}-\d{2}$/.test(sampai)) {
      return ralat(res, 422, 'Parameter dari dan sampai wajib format YYYY-MM-DD')
    }

    const { rows: kunjungan } = await pool.query(
      `SELECT po.nama AS poli, COUNT(*) AS jumlah
         FROM kunjungan k JOIN poli po ON po.id = k.poli_id
        WHERE k.tgl_kunjungan BETWEEN $1 AND $2 AND k.status <> 'batal'
        GROUP BY po.nama ORDER BY po.nama`,
      [dari, sampai],
    )

    const { rows: tindakan } = await pool.query(
      `SELECT COUNT(*) AS jumlah
         FROM pemeriksaan_tindakan pt JOIN kunjungan k ON k.id = pt.kunjungan_id
        WHERE k.tgl_kunjungan BETWEEN $1 AND $2 AND k.status <> 'batal'`,
      [dari, sampai],
    )

    const { rows: pendapatan } = await pool.query(
      `SELECT COALESCE(SUM(p.nominal), 0) AS total
         FROM pembayaran p JOIN tagihan t ON t.id = p.tagihan_id
                          JOIN kunjungan k ON k.id = t.kunjungan_id
        WHERE k.tgl_kunjungan BETWEEN $1 AND $2`,
      [dari, sampai],
    )

    const { rows: obat } = await pool.query(
      `SELECT o.nama_obat, SUM(ro.jumlah) AS jumlah
         FROM resep_obat ro JOIN obat o ON o.id = ro.obat_id
                            JOIN resep r ON r.id = ro.resep_id
                            JOIN kunjungan k ON k.id = r.kunjungan_id
        WHERE k.tgl_kunjungan BETWEEN $1 AND $2
        GROUP BY o.nama_obat ORDER BY 2 DESC`,
      [dari, sampai],
    )

    const { rows: pembayaran } = await pool.query(
      `SELECT t.status, COUNT(*) AS jumlah, COALESCE(SUM(t.total), 0) AS nominal
         FROM tagihan t JOIN kunjungan k ON k.id = t.kunjungan_id
        WHERE k.tgl_kunjungan BETWEEN $1 AND $2
        GROUP BY t.status`,
      [dari, sampai],
    )

    res.json({
      status: true,
      code: 200,
      data: {
        periode: { dari, sampai },
        kunjungan_per_poli: kunjungan,
        tindakan_total: Number(tindakan[0]?.jumlah ?? 0),
        pendapatan: Number(pendapatan[0]?.total ?? 0),
        obat_terpakai: obat,
        status_pembayaran: pembayaran,
      },
    })
  } catch (err) {
    next(err)
  }
})

app.use((err, req, res, next) => {
  console.error(err)
  res.status(500).json({ status: false, code: 500, message: 'Terjadi kesalahan pada backend node' })
})

// ---------- worker lab ----------

async function prosesOutbox() {
  const { rows: tugas } = await pool.query(
    `SELECT * FROM outbox_lab
      WHERE status = 'pending' AND attempts < $1
      ORDER BY create_date LIMIT 10 FOR UPDATE SKIP LOCKED`,
    [MAX_ATTEMPTS],
  )

  for (const tugas1 of tugas) {
    try {
      const resp = await fetch(`${MOCK_BASE}/analisa`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'x-api-key': MOCK_API_KEY },
        body: JSON.stringify(tugas1.payload),
      })

      const body = await resp.json()

      if (!resp.ok || body.status !== true) {
        throw new Error(body.message || `mock lab balas ${resp.status}`)
      }

      await pool.query(
        `UPDATE outbox_lab SET status = 'sent', hasil = $1, attempts = attempts + 1,
                update_date = now() AT TIME ZONE 'Asia/Jakarta'
          WHERE id = $2`,
        [body.data.hasil, tugas1.id],
      )

      const yii = await fetch(`${YII_BASE}/lab/pull-hasil`, {
        method: 'POST',
        headers: { 'x-api-key': YII_API_KEY },
      })

      if (!yii.ok) {
        console.error(`pull-hasil gagal: ${yii.status}`)
      }
    } catch (err) {
      const gagal = Number(tugas1.attempts) + 1 >= MAX_ATTEMPTS
      await pool.query(
        `UPDATE outbox_lab SET attempts = attempts + 1, last_error = $1,
                status = $2, update_date = now() AT TIME ZONE 'Asia/Jakarta'
          WHERE id = $3`,
        [String(err.message ?? err), gagal ? 'failed' : 'pending', tugas1.id],
      )
      console.error(`outbox ${tugas1.id} gagal (${tugas1.attempts + 1}): ${err.message ?? err}`)
    }
  }
}

function jalankanWorker() {
  setInterval(() => {
    prosesOutbox().catch((err) => console.error('worker: ', err.message ?? err))
  }, INTERVAL)
}

app.listen(PORT, () => {
  console.log(`backend-node berjalan di port ${PORT}`)
  jalankanWorker()
})
