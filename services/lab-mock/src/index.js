const crypto = require('node:crypto')
const express = require('express')

const PORT = process.env.PORT || 3002
const API_KEY = process.env.API_KEY || 'simrs-dev-mock-key'

const app = express()
app.use(express.json())

const analisa = new Map()

function auth(req, res, next) {
  if ((req.headers['x-api-key'] ?? '') !== API_KEY) {
    return res.status(401).json({ status: false, code: 401, message: 'API key tidak valid' })
  }

  next()
}

function ralat(res, code, message) {
  return res.status(code).json({ status: false, code, message })
}

app.get('/health', (req, res) => {
  res.json({ status: true, code: 200, data: { layanan: 'lab-mock', waktu: new Date().toISOString() } })
})

app.post('/analisa', auth, (req, res) => {
  const { kode, jenis, pasien } = req.body ?? {}

  if (!kode || !jenis) {
    return ralat(res, 422, 'kode dan jenis wajib diisi')
  }

  const id = crypto.randomUUID()
  const hasil = `${jenis} — ${pasien ?? '-'} (${kode}): nilai dalam batas normal kecuali leukosit 12.500/µL (batas atas 11.000/µL).`

  analisa.set(id, { kode, jenis, pasien: pasien ?? '-', hasil, waktu: new Date().toISOString() })

  res.status(201).json({ status: true, code: 201, data: { id, hasil } })
})

app.get('/analisa/:id', auth, (req, res) => {
  const data = analisa.get(req.params.id)

  if (!data) {
    return ralat(res, 404, 'Analisa tidak ditemukan')
  }

  res.json({ status: true, code: 200, data })
})

app.use((err, req, res, next) => {
  console.error(err)
  res.status(500).json({ status: false, code: 500, message: 'Terjadi kesalahan pada lab mock' })
})

app.listen(PORT, () => {
  console.log(`lab-mock berjalan di port ${PORT}`)
})
