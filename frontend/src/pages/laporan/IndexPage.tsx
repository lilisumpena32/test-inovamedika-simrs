import { useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { client } from '../../api/client'
import type { ApiResponse } from '../../api/types'
import { errorMessage } from '../../api/client'
import { tanggalHariIni } from '../../lib/tanggal'

function formatRupiah(nilai: number): string {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(nilai)
}

type Periode = { dari: string; sampai: string }

type Ringkasan = {
  periode: Periode
  kunjungan_total: number
  kunjungan_per_poli: { poli: string; jumlah: string }[]
  tindakan_total: number
  status_pembayaran: { status: string; jumlah: string }[]
}

type Pendapatan = {
  periode: Periode
  total: number
  per_metode: { metode: string; jumlah: string; nominal: string }[]
  per_hari: { tanggal: string; nominal: string }[]
}

type ObatTerpakai = {
  periode: Periode
  items: { nama_obat: string; jumlah: string }[]
}

async function getRingkasan(periode: Periode): Promise<Ringkasan> {
  const response = await client.get<ApiResponse<Ringkasan>>('/dashboard/ringkasan', {
    params: { dari: periode.dari, sampai: periode.sampai },
  })
  return response.data.data
}

async function getPendapatan(periode: Periode): Promise<Pendapatan> {
  const response = await client.get<ApiResponse<Pendapatan>>('/dashboard/pendapatan', {
    params: { dari: periode.dari, sampai: periode.sampai },
  })
  return response.data.data
}

async function getObatTerpakai(periode: Periode): Promise<ObatTerpakai> {
  const response = await client.get<ApiResponse<ObatTerpakai>>('/dashboard/obat', {
    params: { dari: periode.dari, sampai: periode.sampai },
  })
  return response.data.data
}

function tanggalPertamaBulan(): string {
  const sekarang = new Date()

  return `${sekarang.getFullYear()}-${String(sekarang.getMonth() + 1).padStart(2, '0')}-01`
}

export function IndexPage() {
  const [dari, setDari] = useState(tanggalPertamaBulan())
  const [sampai, setSampai] = useState(tanggalHariIni())

  const periode = { dari, sampai }

  const ringkasan = useQuery({
    queryKey: ['laporan-ringkasan', periode],
    queryFn: () => getRingkasan(periode),
  })

  const pendapatan = useQuery({
    queryKey: ['laporan-pendapatan', periode],
    queryFn: () => getPendapatan(periode),
  })

  const obat = useQuery({
    queryKey: ['laporan-obat', periode],
    queryFn: () => getObatTerpakai(periode),
  })

  const galat = ringkasan.isError || pendapatan.isError || obat.isError
    ? errorMessage(ringkasan.error ?? pendapatan.error ?? obat.error)
    : ''

  return (
    <div className="space-y-6">
      <h1 className="text-xl font-semibold sm:text-2xl">Laporan Operasional</h1>

      <section className="card max-w-md space-y-3 p-5">
        <div className="grid gap-3 sm:grid-cols-2">
          <div>
            <label htmlFor="dari" className="mb-1.5 block text-sm font-medium">
              Dari
            </label>
            <input
              id="dari"
              type="date"
              value={dari}
              onChange={(e) => setDari(e.target.value)}
              className="input"
            />
          </div>
          <div>
            <label htmlFor="sampai" className="mb-1.5 block text-sm font-medium">
              Sampai
            </label>
            <input
              id="sampai"
              type="date"
              value={sampai}
              onChange={(e) => setSampai(e.target.value)}
              className="input"
            />
          </div>
        </div>
      </section>

      {galat !== '' && (
        <div
          role="alert"
          className="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
        >
          {galat}{' '}
          <button
            type="button"
            onClick={() => {
              void ringkasan.refetch()
              void pendapatan.refetch()
              void obat.refetch()
            }}
            className="font-semibold underline"
          >
            Coba lagi
          </button>
        </div>
      )}

      <section className="card">
        <h2 className="border-b border-slate-200 px-4 py-3 font-semibold">Ringkasan kunjungan</h2>
        {ringkasan.isPending && (
          <p className="px-4 py-3 text-sm text-slate-500">Memuat...</p>
        )}
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
              <th className="px-4 py-3 font-medium">Poli</th>
              <th className="px-4 py-3 font-medium">Kunjungan</th>
            </tr>
          </thead>
          <tbody>
            {ringkasan.data?.kunjungan_per_poli.map((baris) => (
              <tr key={baris.poli} className="border-b border-slate-100 last:border-0">
                <td className="px-4 py-3">{baris.poli}</td>
                <td className="px-4 py-3 tabular-nums">{baris.jumlah}</td>
              </tr>
            ))}
            <tr className="font-semibold">
              <td className="px-4 py-3">Total</td>
              <td className="px-4 py-3 tabular-nums">
                {ringkasan.data?.kunjungan_total ?? '-'}
              </td>
            </tr>
            <tr>
              <td className="px-4 py-3 text-slate-500">Tindakan</td>
              <td className="px-4 py-3 tabular-nums">{ringkasan.data?.tindakan_total ?? '-'}</td>
            </tr>
          </tbody>
        </table>
        </div>
      </section>

      <section className="card">
        <h2 className="border-b border-slate-200 px-4 py-3 font-semibold">Pendapatan</h2>
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
          <tbody>
            <tr className="border-b border-slate-100 font-semibold">
              <td className="px-4 py-3">Total diterima</td>
              <td className="px-4 py-3 text-right tabular-nums">
                {formatRupiah(pendapatan.data?.total ?? 0)}
              </td>
            </tr>
            {(pendapatan.data?.per_metode ?? []).map((baris) => (
              <tr key={baris.metode} className="border-b border-slate-100 last:border-0">
                <td className="px-4 py-3">
                  {baris.metode === 'non_tunai' ? 'Non-tunai' : 'Tunai'} ({baris.jumlah}x)
                </td>
                <td className="px-4 py-3 text-right tabular-nums">
                  {formatRupiah(Number(baris.nominal))}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        </div>
      </section>

      <section className="card">
        <h2 className="border-b border-slate-200 px-4 py-3 font-semibold">Obat terpakai</h2>
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
              <th className="px-4 py-3 font-medium">Obat</th>
              <th className="px-4 py-3 font-medium">Jumlah</th>
            </tr>
          </thead>
          <tbody>
            {(obat.data?.items ?? []).map((baris) => (
              <tr key={baris.nama_obat} className="border-b border-slate-100 last:border-0">
                <td className="px-4 py-3">{baris.nama_obat}</td>
                <td className="px-4 py-3 tabular-nums">{baris.jumlah}</td>
              </tr>
            ))}
            {(obat.data?.items.length ?? 0) === 0 && (
              <tr>
                <td colSpan={2} className="px-4 py-6 text-center text-slate-500">
                  Tidak ada pemakaian obat pada periode ini.
                </td>
              </tr>
            )}
          </tbody>
        </table>
        </div>
      </section>
    </div>
  )
}
