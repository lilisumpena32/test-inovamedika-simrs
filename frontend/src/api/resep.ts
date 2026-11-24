import { client } from './client'
import type { ApiResponse } from './types'
import type { KunjunganRow } from './kunjungan'

export type ResepRow = {
  id: string
  kunjungan_id: string
  kunjungan: KunjunganRow | null
  status: string
  items: ResepItem[]
}

export type ResepItem = {
  id: string
  obat: { id: string; kode: string; nama: string } | null
  dosis: string | null
  frekuensi: string | null
  jumlah: number
}

export type ResepItemInput = {
  obat_id: string
  dosis: string
  frekuensi: string
  jumlah: number
}

export async function buatResep(
  kunjunganId: string,
  items: ResepItemInput[],
): Promise<ApiResponse<ResepRow>> {
  const response = await client.post<ApiResponse<ResepRow>>(`/kunjungan/${kunjunganId}/resep`, { items })
  return response.data
}

export async function ubahResep(
  kunjunganId: string,
  items: ResepItemInput[],
): Promise<ApiResponse<ResepRow>> {
  const response = await client.put<ApiResponse<ResepRow>>(`/kunjungan/${kunjunganId}/resep`, { items })
  return response.data
}

export async function listResep(filter: { tanggal: string; status: string }): Promise<{ items: ResepRow[] }> {
  const response = await client.get<ApiResponse<{ items: ResepRow[] }>>('/farmasi/resep', {
    params: { tanggal: filter.tanggal || undefined, status: filter.status || undefined },
  })
  return response.data.data
}

export async function getResep(id: string): Promise<ResepRow> {
  const response = await client.get<ApiResponse<ResepRow>>(`/resep/${id}`)
  return response.data.data
}

export async function getResepKunjungan(kunjunganId: string): Promise<ResepRow | null> {
  const response = await client.get<ApiResponse<ResepRow | null>>(
    `/kunjungan/${kunjunganId}/resep`,
  )
  return response.data.data
}

export async function serahkanResep(id: string): Promise<ApiResponse<ResepRow>> {
  const response = await client.put<ApiResponse<ResepRow>>(`/resep/${id}/serah`)
  return response.data
}
