import { client } from './client'
import type { ApiResponse } from './types'

export type JadwalRow = {
  id: string
  karyawan: { id: string; kode: string; name: string } | null
  poli: { id: string; kode: string; name: string } | null
  hari: number
  jam_mulai: string
  jam_selesai: string
  kuota: number
  is_active: boolean
}

export type JadwalInput = {
  hari: number
  jam_mulai: string
  jam_selesai: string
  kuota: number
}

export type JadwalSesiInput = {
  karyawan_id: string
  poli_id: string
  sesi: JadwalInput[]
}

const HARI = ['', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu']

export function namaHari(hari: number): string {
  return HARI[hari] ?? String(hari)
}

export async function listJadwals(filter: {
  karyawan_id: string
  poli_id: string
}): Promise<JadwalRow[]> {
  const response = await client.get<ApiResponse<{ items: JadwalRow[] }>>('/jadwal', {
    params: {
      karyawan_id: filter.karyawan_id || undefined,
      poli_id: filter.poli_id || undefined,
    },
  })
  return response.data.data.items
}

export async function getJadwal(id: string): Promise<JadwalRow> {
  const response = await client.get<ApiResponse<JadwalRow>>(`/jadwal/${id}`)
  return response.data.data
}

export async function createJadwal(input: JadwalSesiInput): Promise<ApiResponse<{ items: JadwalRow[] }>> {
  const response = await client.post<ApiResponse<{ items: JadwalRow[] }>>('/jadwal', input)
  return response.data
}

export type JadwalPerbarui = {
  karyawan_id: string
  poli_id: string
  hari: number
  jam_mulai: string
  jam_selesai: string
  kuota: number
}

export async function updateJadwal(
  id: string,
  input: JadwalPerbarui,
): Promise<ApiResponse<JadwalRow>> {
  const response = await client.put<ApiResponse<JadwalRow>>(`/jadwal/${id}`, input)
  return response.data
}

export async function setJadwalStatus(
  id: string,
  is_active: boolean,
): Promise<ApiResponse<JadwalRow>> {
  const response = await client.put<ApiResponse<JadwalRow>>(`/jadwal/${id}/status`, { is_active })
  return response.data
}
