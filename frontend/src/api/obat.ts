import { client } from './client'
import type { ApiResponse } from './types'

export type Referensi = { id: string; kode: string; name: string }

export type ObatRow = {
  id: string
  kode_obat: string
  nama_obat: string
  kategori: Referensi | null
  golongan: Referensi | null
  satuan: Referensi | null
  harga_beli: number
  harga_jual: number
  stok_minimum: number
  is_active: boolean
}

export type ObatList = {
  items: ObatRow[]
  total: number
  page: number
  per: number
}

export type ObatInput = {
  nama_obat: string
  kategori_id: string
  golongan_obat_id: string
  satuan_id: string
  harga_beli: number
  harga_jual: number
  stok_minimum: number
}

export async function listObats(filter: { q: string; is_active: string; page: number }): Promise<ObatList> {
  const response = await client.get<ApiResponse<ObatList>>('/obat', {
    params: {
      q: filter.q || undefined,
      is_active: filter.is_active || undefined,
      page: filter.page > 1 ? filter.page : undefined,
    },
  })
  return response.data.data
}

export async function getObat(id: string): Promise<ObatRow> {
  const response = await client.get<ApiResponse<ObatRow>>(`/obat/${id}`)
  return response.data.data
}

export async function createObat(input: ObatInput): Promise<ApiResponse<ObatRow>> {
  const response = await client.post<ApiResponse<ObatRow>>('/obat', input)
  return response.data
}

export async function updateObat(id: string, input: ObatInput): Promise<ApiResponse<ObatRow>> {
  const response = await client.put<ApiResponse<ObatRow>>(`/obat/${id}`, input)
  return response.data
}

export async function setObatStatus(id: string, is_active: boolean): Promise<ApiResponse<ObatRow>> {
  const response = await client.put<ApiResponse<ObatRow>>(`/obat/${id}/status`, { is_active })
  return response.data
}

export async function listKategoris(): Promise<Referensi[]> {
  const response = await client.get<ApiResponse<{ items: Referensi[] }>>('/kategori')
  return response.data.data.items
}

export async function listGolonganObats(): Promise<Referensi[]> {
  const response = await client.get<ApiResponse<{ items: Referensi[] }>>('/golongan-obat')
  return response.data.data.items
}

export async function listSatuans(): Promise<Referensi[]> {
  const response = await client.get<ApiResponse<{ items: Referensi[] }>>('/satuan')
  return response.data.data.items
}
