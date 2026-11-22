import { client } from './client'
import type { ApiResponse, LoginResult, SessionData } from './types'

export async function login(username: string, password: string): Promise<LoginResult> {
  const response = await client.post<ApiResponse<LoginResult>>('/auth/login', {
    username,
    password,
  })
  return response.data.data
}

export async function fetchSession(): Promise<SessionData> {
  const response = await client.get<ApiResponse<SessionData>>('/auth/me')
  return response.data.data
}
