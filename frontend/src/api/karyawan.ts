import { client } from './client'
import type { ApiResponse } from './types'

export type KaryawanRow = {
  id: string
  kode_karyawan: string
  nama_karyawan: string
  jenis_kelamin: string
  tanggal_lahir: string | null
  jabatan: { id: string; kode: string; name: string } | null
  spesialis: { id: string; kode: string; name: string } | null
  no_hp: string | null
  user: { id: string; username: string } | null
  tanggal_bergabung: string | null
  is_active: boolean
}

export type KaryawanList = {
  items: KaryawanRow[]
  total: number
  page: number
  per: number
}

export type KaryawanInput = {
  nama_karyawan: string
  jenis_kelamin: string
  tanggal_lahir: string
  jabatan_id: string
  spesialis_id: string
  no_hp: string
  tanggal_bergabung: string
}

export type Opsi = { id: string; kode: string; name: string }

export async function getKaryawan(id: string): Promise<KaryawanRow> {
  const response = await client.get<ApiResponse<KaryawanRow>>(`/karyawan/${id}`)
  return response.data.data
}

export async function listKaryawans(filter: {
  q: string
  jabatan: string
  is_active: string
  page: number
}): Promise<KaryawanList> {
  const response = await client.get<ApiResponse<KaryawanList>>('/karyawan', {
    params: {
      q: filter.q || undefined,
      jabatan: filter.jabatan || undefined,
      is_active: filter.is_active || undefined,
      page: filter.page > 1 ? filter.page : undefined,
    },
  })
  return response.data.data
}

export async function createKaryawan(input: KaryawanInput): Promise<ApiResponse<KaryawanRow>> {
  const response = await client.post<ApiResponse<KaryawanRow>>('/karyawan', input)
  return response.data
}

export async function updateKaryawan(
  id: string,
  input: KaryawanInput,
): Promise<ApiResponse<KaryawanRow>> {
  const response = await client.put<ApiResponse<KaryawanRow>>(`/karyawan/${id}`, input)
  return response.data
}

export async function setKaryawanAkun(
  id: string,
  user_id: string | null,
): Promise<ApiResponse<KaryawanRow>> {
  const response = await client.put<ApiResponse<KaryawanRow>>(`/karyawan/${id}/akun`, {
    user_id,
  })
  return response.data
}

export async function setKaryawanStatus(
  id: string,
  is_active: boolean,
): Promise<ApiResponse<KaryawanRow>> {
  const response = await client.put<ApiResponse<KaryawanRow>>(`/karyawan/${id}/status`, {
    is_active,
  })
  return response.data
}

export async function listJabatans(): Promise<Opsi[]> {
  const response = await client.get<ApiResponse<{ items: Opsi[] }>>('/jabatan')
  return response.data.data.items
}

export async function listSpesialis(): Promise<Opsi[]> {
  const response = await client.get<ApiResponse<{ items: Opsi[] }>>('/spesialis')
  return response.data.data.items
}
