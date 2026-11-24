import { client } from './client'
import type { ApiResponse } from './types'

export type JenisLabRow = {
  id: string
  kode_jenis_lab: string
  nama: string
  tarif: number
  is_active: boolean
}

export type JenisLabInput = {
  nama: string
  tarif: number
}

const LIST = '/jenis-lab'

export async function listJenisLabMaster(filter: { q: string; is_active: string }): Promise<{ items: JenisLabRow[] }> {
  const response = await client.get<ApiResponse<{ items: JenisLabRow[] }>>(LIST, {
    params: { q: filter.q || undefined, is_active: filter.is_active || undefined },
  })
  return response.data.data
}

export async function listJenisLabAktif(): Promise<JenisLabRow[]> {
  const response = await client.get<ApiResponse<{ items: JenisLabRow[] }>>('/jenis-lab-list')
  return response.data.data.items
}

export async function getJenisLab(id: string): Promise<JenisLabRow> {
  const response = await client.get<ApiResponse<JenisLabRow>>(`${LIST}/${id}`)
  return response.data.data
}

export async function createJenisLab(input: JenisLabInput): Promise<ApiResponse<JenisLabRow>> {
  const response = await client.post<ApiResponse<JenisLabRow>>(LIST, input)
  return response.data
}

export async function updateJenisLab(id: string, input: JenisLabInput): Promise<ApiResponse<JenisLabRow>> {
  const response = await client.put<ApiResponse<JenisLabRow>>(`${LIST}/${id}`, input)
  return response.data
}

export async function setJenisLabStatus(id: string, is_active: boolean): Promise<ApiResponse<JenisLabRow>> {
  const response = await client.put<ApiResponse<JenisLabRow>>(`${LIST}/${id}/status`, { is_active })
  return response.data
}
