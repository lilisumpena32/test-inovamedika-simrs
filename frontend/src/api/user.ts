import { client } from './client'
import type { ApiResponse } from './types'

export type UserRow = {
  id: string
  username: string
  full_name: string
  email: string | null
  is_active: boolean
  create_date: string
  role: {
    id: string
    code: string
    name: string
  }
}

export type UserList = {
  items: UserRow[]
  total: number
  page: number
  per: number
}

export type UserFilter = {
  q: string
  role: string
  is_active: string
  page: number
}

export type UserInput = {
  username: string
  full_name: string
  email: string
  role_id: string
  password: string
}

export type UserUpdate = {
  full_name: string
  email: string
  role_id: string
}

export async function listUsers(filter: UserFilter): Promise<UserList> {
  const response = await client.get<ApiResponse<UserList>>('/user', {
    params: {
      q: filter.q || undefined,
      role: filter.role || undefined,
      is_active: filter.is_active || undefined,
      page: filter.page > 1 ? filter.page : undefined,
    },
  })
  return response.data.data
}

export async function getUser(id: string): Promise<UserRow> {
  const response = await client.get<ApiResponse<UserRow>>(`/user/${id}`)
  return response.data.data
}

export async function createUser(input: UserInput): Promise<ApiResponse<UserRow>> {
  const response = await client.post<ApiResponse<UserRow>>('/user', input)
  return response.data
}

export async function updateUser(id: string, input: UserUpdate): Promise<ApiResponse<UserRow>> {
  const response = await client.put<ApiResponse<UserRow>>(`/user/${id}`, input)
  return response.data
}

export async function setUserStatus(id: string, is_active: boolean): Promise<ApiResponse<UserRow>> {
  const response = await client.put<ApiResponse<UserRow>>(`/user/${id}/status`, { is_active })
  return response.data
}
