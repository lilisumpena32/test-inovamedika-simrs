import { client } from './client'
import type { ApiResponse } from './types'
import type { KunjunganRow } from './kunjungan'

export type PemeriksaanRow = {
  id: string
  kunjungan_id: string
  keluhan: string | null
  anamnesis: string | null
  tensi: string | null
  suhu: number | null
  nadi: number | null
  pernapasan: number | null
  tinggi_cm: number | null
  berat_kg: number | null
  hasil_fisik: string | null
  diagnosis_utama: string
  diagnosis_sekunder: string | null
}

export type TindakanDetail = {
  id: string
  kunjungan_id: string
  tindakan: { id: string; kode: string; nama: string } | null
  tarif: number
}

export type LabOrder = {
  id: string
  kunjungan_id: string
  jenis_lab: { id: string; kode: string; nama: string } | null
  tarif: number
  status: string
  hasil: string | null
  waktu_hasil: string | null
}

export type PemeriksaanInput = {
  keluhan: string
  anamnesis: string
  tensi: string
  suhu: string
  nadi: string
  pernapasan: string
  tinggi_cm: string
  berat_kg: string
  hasil_fisik: string
  diagnosis_utama: string
  diagnosis_sekunder: string
}

const KOSONG: PemeriksaanInput = {
  keluhan: '',
  anamnesis: '',
  tensi: '',
  suhu: '',
  nadi: '',
  pernapasan: '',
  tinggi_cm: '',
  berat_kg: '',
  hasil_fisik: '',
  diagnosis_utama: '',
  diagnosis_sekunder: '',
}

export function isiKosong(): PemeriksaanInput {
  return { ...KOSONG }
}

function angka(nilai: string): number | null {
  return nilai === '' ? null : Number(nilai)
}

function keInput(row: PemeriksaanRow): PemeriksaanInput {
  return {
    keluhan: row.keluhan ?? '',
    anamnesis: row.anamnesis ?? '',
    tensi: row.tensi ?? '',
    suhu: row.suhu === null ? '' : String(row.suhu),
    nadi: row.nadi === null ? '' : String(row.nadi),
    pernapasan: row.pernapasan === null ? '' : String(row.pernapasan),
    tinggi_cm: row.tinggi_cm === null ? '' : String(row.tinggi_cm),
    berat_kg: row.berat_kg === null ? '' : String(row.berat_kg),
    hasil_fisik: row.hasil_fisik ?? '',
    diagnosis_utama: row.diagnosis_utama,
    diagnosis_sekunder: row.diagnosis_sekunder ?? '',
  }
}

export function isiPemeriksaan(row: PemeriksaanRow | null): PemeriksaanInput {
  return row === null ? isiKosong() : keInput(row)
}

function bersihkan(input: PemeriksaanInput) {
  return {
    keluhan: input.keluhan || null,
    anamnesis: input.anamnesis || null,
    tensi: input.tensi || null,
    suhu: angka(input.suhu),
    nadi: angka(input.nadi),
    pernapasan: angka(input.pernapasan),
    tinggi_cm: angka(input.tinggi_cm),
    berat_kg: angka(input.berat_kg),
    hasil_fisik: input.hasil_fisik || null,
    diagnosis_utama: input.diagnosis_utama,
    diagnosis_sekunder: input.diagnosis_sekunder || null,
  }
}

export async function simpanPemeriksaan(
  kunjunganId: string,
  input: PemeriksaanInput,
): Promise<ApiResponse<PemeriksaanRow>> {
  const response = await client.post<ApiResponse<PemeriksaanRow>>(
    `/kunjungan/${kunjunganId}/pemeriksaan`,
    bersihkan(input),
  )
  return response.data
}

export async function getPemeriksaan(kunjunganId: string): Promise<PemeriksaanRow | null> {
  const response = await client.get<ApiResponse<PemeriksaanRow | null>>(
    `/kunjungan/${kunjunganId}/pemeriksaan`,
  )
  return response.data.data
}

export async function listTindakanKunjungan(kunjunganId: string): Promise<TindakanDetail[]> {
  const response = await client.get<ApiResponse<{ items: TindakanDetail[] }>>(
    `/kunjungan/${kunjunganId}/tindakan`,
  )
  return response.data.data.items
}

export async function tambahTindakan(kunjunganId: string, tindakan_id: string): Promise<ApiResponse<TindakanDetail>> {
  const response = await client.post<ApiResponse<TindakanDetail>>(`/kunjungan/${kunjunganId}/tindakan`, { tindakan_id })
  return response.data
}

export async function hapusTindakanKunjungan(kunjunganId: string, detailId: string): Promise<ApiResponse<null>> {
  const response = await client.delete<ApiResponse<null>>(`/kunjungan/${kunjunganId}/tindakan/${detailId}`)
  return response.data
}

export async function listLabKunjungan(kunjunganId: string): Promise<LabOrder[]> {
  const response = await client.get<ApiResponse<{ items: LabOrder[] }>>(`/kunjungan/${kunjunganId}/lab`)
  return response.data.data.items
}

export async function tambahLab(kunjunganId: string, jenis_lab_id: string): Promise<ApiResponse<LabOrder>> {
  const response = await client.post<ApiResponse<LabOrder>>(`/kunjungan/${kunjunganId}/lab`, { jenis_lab_id })
  return response.data
}

export async function selesaiPemeriksaan(kunjunganId: string): Promise<ApiResponse<KunjunganRow>> {
  const response = await client.put<ApiResponse<KunjunganRow>>(`/kunjungan/${kunjunganId}/selesai`)
  return response.data
}

export type LabDaftarRow = LabOrder & {
  kunjungan: {
    no_kunjungan: string
    tgl_kunjungan: string
    pasien: { no_rm: string; nama: string } | null
    poli: string | null
  } | null
}

export async function listOrderLab(filter: { tanggal: string; status: string }): Promise<{ items: LabDaftarRow[] }> {
  const response = await client.get<ApiResponse<{ items: LabDaftarRow[] }>>('/lab', {
    params: { tanggal: filter.tanggal || undefined, status: filter.status || undefined },
  })
  return response.data.data
}

export async function simpanHasilLab(id: string, hasil: string): Promise<ApiResponse<LabDaftarRow>> {
  const response = await client.put<ApiResponse<LabDaftarRow>>(`/lab/${id}/hasil`, { hasil })
  return response.data
}
