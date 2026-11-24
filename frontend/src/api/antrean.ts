import { client } from './client'
import type { ApiResponse } from './types'

export type AntreanRow = {
  id: string
  no_antrean: number
  kode_antrean: string
  no_kunjungan: string
  pasien: { no_rm: string; nama: string } | null
  dokter: string | null
  status: string
}

export async function listAntreans(
  tanggal: string,
  poli_id: string,
): Promise<{ tanggal: string; items: AntreanRow[] }> {
  const response = await client.get<ApiResponse<{ tanggal: string; items: AntreanRow[] }>>('/antrean', {
    params: {
      tanggal: tanggal || undefined,
      poli_id: poli_id || undefined,
    },
  })
  return response.data.data
}
