import { client } from './client'
import type { ApiResponse } from './types'

export type KunjunganRow = {
  id: string
  no_kunjungan: string
  pasien: { id: string; no_rm: string; nama: string } | null
  dokter: { id: string; name: string } | null
  poli: { id: string; name: string; initial: string } | null
  jadwal_id: string
  tgl_kunjungan: string
  no_antrean: number
  kode_antrean: string
  status: string
  biaya_daftar: number
}

export type KunjunganList = {
  items: KunjunganRow[]
  total: number
  page: number
  per: number
}

export type JadwalTersedia = {
  id: string
  dokter: { id: string; name: string } | null
  jam_mulai: string
  jam_selesai: string
  kuota: number
  terisi: number
  sisa: number
}

export type KunjunganInput = {
  idempotency_key: string
  pasien_id: string
  jadwal_id: string
  tgl_kunjungan: string
}

export const STATUS_LABEL: Record<string, string> = {
  menunggu: 'Menunggu',
  diperiksa: 'Diperiksa',
  menunggu_obat: 'Menunggu obat',
  menunggu_bayar: 'Menunggu bayar',
  selesai: 'Selesai',
  batal: 'Batal',
}

export async function listKunjungans(filter: {
  tanggal: string
  poli_id: string
  status: string
  page: number
}): Promise<KunjunganList> {
  const response = await client.get<ApiResponse<KunjunganList>>('/kunjungan', {
    params: {
      tanggal: filter.tanggal || undefined,
      poli_id: filter.poli_id || undefined,
      status: filter.status || undefined,
      page: filter.page > 1 ? filter.page : undefined,
    },
  })
  return response.data.data
}

export async function getKunjungan(id: string): Promise<KunjunganRow> {
  const response = await client.get<ApiResponse<KunjunganRow>>(`/kunjungan/${id}`)
  return response.data.data
}

export async function listJadwalTersedia(
  poli_id: string,
  tanggal: string,
): Promise<{ tanggal: string; items: JadwalTersedia[] }> {
  const response = await client.get<ApiResponse<{ tanggal: string; items: JadwalTersedia[] }>>(
    '/kunjungan/jadwal-tersedia',
    { params: { poli_id, tanggal } },
  )
  return response.data.data
}

export async function createKunjungan(input: KunjunganInput): Promise<ApiResponse<KunjunganRow>> {
  const response = await client.post<ApiResponse<KunjunganRow>>('/kunjungan', input)
  return response.data
}

export async function batalKunjungan(id: string): Promise<ApiResponse<KunjunganRow>> {
  const response = await client.put<ApiResponse<KunjunganRow>>(`/kunjungan/${id}/batal`)
  return response.data
}
