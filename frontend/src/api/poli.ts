import { client } from './client'
import type { ApiResponse } from './types'

export type PoliRow = {
  id: string
  kode: string
  nama: string
  biaya_daftar: number
  is_active: boolean
}

export type PoliInput = {
  nama: string
  biaya_daftar: number
}

export async function listPolis(): Promise<PoliRow[]> {
  const response = await client.get<ApiResponse<{ items: PoliRow[] }>>('/poli')
  return response.data.data.items
}

export async function getPoli(id: string): Promise<PoliRow> {
  const response = await client.get<ApiResponse<PoliRow>>(`/poli/${id}`)
  return response.data.data
}

export async function createPoli(input: {
  kode: string
  nama: string
  biaya_daftar: number
}): Promise<ApiResponse<PoliRow>> {
  const response = await client.post<ApiResponse<PoliRow>>('/poli', input)
  return response.data
}

export async function updatePoli(id: string, input: PoliInput): Promise<ApiResponse<PoliRow>> {
  const response = await client.put<ApiResponse<PoliRow>>(`/poli/${id}`, input)
  return response.data
}

export async function setPoliStatus(id: string, is_active: boolean): Promise<ApiResponse<PoliRow>> {
  const response = await client.put<ApiResponse<PoliRow>>(`/poli/${id}/status`, { is_active })
  return response.data
}
