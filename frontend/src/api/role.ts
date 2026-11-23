import { client } from './client'
import type { ApiResponse, MenuAccess } from './types'

export type RoleRow = {
  id: string
  code: string
  name: string
  is_active: boolean
  user_count: number
}

export type RoleMenu = {
  id: string
  kode: string
  label: string
  parent: string | null
}

export type RoleMatrix = Record<string, Record<string, MenuAccess>>

export type RoleData = {
  items: RoleRow[]
  menus: RoleMenu[]
  matrix: RoleMatrix
}

export async function listRoles(): Promise<RoleData> {
  const response = await client.get<ApiResponse<RoleData>>('/role')
  return response.data.data
}

export async function createRole(input: {
  role_code: string
  role_name: string
}): Promise<ApiResponse<RoleRow>> {
  const response = await client.post<ApiResponse<RoleRow>>('/role', input)
  return response.data
}

export async function updateRole(
  id: string,
  input: { role_name: string },
): Promise<ApiResponse<RoleRow>> {
  const response = await client.put<ApiResponse<RoleRow>>(`/role/${id}`, input)
  return response.data
}

export async function setRoleStatus(
  id: string,
  is_active: boolean,
): Promise<ApiResponse<RoleRow>> {
  const response = await client.put<ApiResponse<RoleRow>>(`/role/${id}/status`, { is_active })
  return response.data
}

export async function setRoleAccess(
  id: string,
  access: Record<string, MenuAccess>,
): Promise<ApiResponse<RoleRow>> {
  const response = await client.put<ApiResponse<RoleRow>>(`/role/${id}/access`, { access })
  return response.data
}
