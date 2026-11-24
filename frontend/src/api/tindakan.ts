import { client } from './client'
import type { ApiResponse } from './types'

export type TindakanRow = {
  id: string
  kode_tindakan: string
  nama: string
  tarif: number
  is_active: boolean
}

export type TindakanInput = {
  nama: string
  tarif: number
}

const LIST = '/tindakan'

export async function listTindakanMaster(filter: { q: string; is_active: string }): Promise<{ items: TindakanRow[] }> {
  const response = await client.get<ApiResponse<{ items: TindakanRow[] }>>(LIST, {
    params: { q: filter.q || undefined, is_active: filter.is_active || undefined },
  })
  return response.data.data
}

export async function listTindakanAktif(): Promise<TindakanRow[]> {
  const response = await client.get<ApiResponse<{ items: TindakanRow[] }>>('/tindakan-list')
  return response.data.data.items
}

export async function getTindakan(id: string): Promise<TindakanRow> {
  const response = await client.get<ApiResponse<TindakanRow>>(`${LIST}/${id}`)
  return response.data.data
}

export async function createTindakan(input: TindakanInput): Promise<ApiResponse<TindakanRow>> {
  const response = await client.post<ApiResponse<TindakanRow>>(LIST, input)
  return response.data
}

export async function updateTindakan(id: string, input: TindakanInput): Promise<ApiResponse<TindakanRow>> {
  const response = await client.put<ApiResponse<TindakanRow>>(`${LIST}/${id}`, input)
  return response.data
}

export async function setTindakanStatus(id: string, is_active: boolean): Promise<ApiResponse<TindakanRow>> {
  const response = await client.put<ApiResponse<TindakanRow>>(`${LIST}/${id}/status`, { is_active })
  return response.data
}
