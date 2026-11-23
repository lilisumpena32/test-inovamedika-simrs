import { client } from './client'
import type { ApiResponse } from './types'

export type PasienRow = {
  id: string
  no_rm: string
  nik: string | null
  nama: string
  tanggal_lahir: string | null
  jenis_kelamin: string
  no_hp: string | null
  alamat: string | null
  is_active: boolean
}

export type PasienList = {
  items: PasienRow[]
  total: number
  page: number
  per: number
}

export type PasienInput = {
  nama: string
  nik: string | null
  tanggal_lahir: string
  jenis_kelamin: string
  no_hp: string
  alamat: string
}

const LIST = '/pasien'

export async function getPasien(id: string): Promise<PasienRow> {
  const response = await client.get<ApiResponse<PasienRow>>(`${LIST}/${id}`)
  return response.data.data
}

export async function listPasiens(filter: { q: string; is_active: string; page: number }): Promise<PasienList> {
  const response = await client.get<ApiResponse<PasienList>>(LIST, {
    params: {
      q: filter.q || undefined,
      is_active: filter.is_active || undefined,
      page: filter.page > 1 ? filter.page : undefined,
    },
  })
  return response.data.data
}

export async function createPasien(input: PasienInput): Promise<ApiResponse<PasienRow>> {
  const response = await client.post<ApiResponse<PasienRow>>(LIST, input)
  return response.data
}

export async function updatePasien(id: string, input: PasienInput): Promise<ApiResponse<PasienRow>> {
  const response = await client.put<ApiResponse<PasienRow>>(`${LIST}/${id}`, input)
  return response.data
}

export async function setPasienStatus(id: string, is_active: boolean): Promise<ApiResponse<PasienRow>> {
  const response = await client.put<ApiResponse<PasienRow>>(`${LIST}/${id}/status`, { is_active })
  return response.data
}
