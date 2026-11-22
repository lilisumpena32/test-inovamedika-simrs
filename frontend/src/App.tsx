import { useEffect, useState } from 'react'

type Health = {
  status: string
  versi: string
  waktu_database: string
  waktu_aplikasi: string
}

export default function App() {
  const [health, setHealth] = useState<Health | null>(null)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    let aktif = true

    fetch('/api/v1/health')
      .then((respons) => {
        if (!respons.ok) {
          throw new Error(`HTTP ${respons.status}`)
        }
        return respons.json() as Promise<Health>
      })
      .then((data) => {
        if (aktif) setHealth(data)
      })
      .catch((err: unknown) => {
        if (aktif) setError(err instanceof Error ? err.message : 'gagal memanggil API')
      })

    return () => {
      aktif = false
    }
  }, [])

  return (
    <main className="min-h-screen bg-slate-50 px-6 py-16 text-slate-900">
      <div className="mx-auto max-w-xl">
        <section className="mt-8 rounded-lg border border-slate-200 bg-white p-5">
          <h2 className="text-sm font-semibold">Status Endpoint</h2>
          {error && <p className="mt-3 text-sm text-red-600">Gagal: {error}</p>}
          {!error && !health && <p className="mt-3 text-sm text-slate-500">Tunggu Sebentar...</p>}
          {health && (
            <dl className="mt-3 grid grid-cols-[8rem_1fr] gap-y-2 text-sm">
              <dt className="text-slate-500">Status</dt>
              <dd className="font-mono">{health.status}</dd>
              <dt className="text-slate-500">Versi</dt>
              <dd className="font-mono">{health.versi}</dd>
              <dt className="text-slate-500">Waktu Database</dt>
              <dd className="font-mono">{health.waktu_database}</dd>
              <dt className="text-slate-500">Waktu Aplikasi</dt>
              <dd className="font-mono">{health.waktu_aplikasi}</dd>
            </dl>
          )}
        </section>
      </div>
    </main>
  )
}
