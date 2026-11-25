import { client } from './client'
import type { ApiResponse } from './types'
import type { KunjunganRow } from './kunjungan'

export type TagihanItemRow = {
  id: string
  jenis: 'daftar' | 'tindakan' | 'lab' | 'obat'
  uraian: string
  jumlah: number
  harga: number
}

export type PembayaranRow = {
  id: string
  metode: 'tunai' | 'non_tunai'
  nominal: number
  waktu: string
}

export type TagihanRow = {
  id: string
  no_tagihan: string
  status: 'belum' | 'sebagian' | 'lunas'
  total: number
  dibayar: number
  sisa: number
  kunjungan: KunjunganRow | null
  items: TagihanItemRow[]
  pembayaran: PembayaranRow[]
}

export async function listTagihan(filter: { tanggal: string; status: string }): Promise<{ items: TagihanRow[] }> {
  const response = await client.get<ApiResponse<{ items: TagihanRow[] }>>('/tagihan', {
    params: { tanggal: filter.tanggal || undefined, status: filter.status || undefined },
  })
  return response.data.data
}

export async function getTagihan(id: string): Promise<TagihanRow> {
  const response = await client.get<ApiResponse<TagihanRow>>(`/tagihan/${id}`)
  return response.data.data
}

export async function buatTagihan(kunjunganId: string): Promise<ApiResponse<TagihanRow>> {
  const response = await client.post<ApiResponse<TagihanRow>>('/tagihan', { kunjungan_id: kunjunganId })
  return response.data
}

export async function bayarTagihan(
  id: string,
  input: { idempotency_key: string; metode: string; nominal: number },
): Promise<ApiResponse<TagihanRow>> {
  const response = await client.put<ApiResponse<TagihanRow>>(`/tagihan/${id}/bayar`, input)
  return response.data
}
