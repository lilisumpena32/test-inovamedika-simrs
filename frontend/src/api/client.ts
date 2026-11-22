import axios from 'axios'

export const TOKEN_KEY = 'simrs.token'
export const UNAUTHORIZED_EVENT = 'simrs:unauthorized'

export const client = axios.create({
  baseURL: '/api/v1',
  headers: {
    Accept: 'application/json',
  },
})

client.interceptors.request.use((config) => {
  const token = localStorage.getItem(TOKEN_KEY)
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

client.interceptors.response.use(
  (response) => response,
  (error: unknown) => {
    const rejected =
      axios.isAxiosError(error) &&
      error.response?.status === 401 &&
      Boolean(error.config?.headers?.Authorization)

    if (rejected) {
      window.dispatchEvent(new Event(UNAUTHORIZED_EVENT))
    }

    return Promise.reject(error)
  },
)

export function errorMessage(error: unknown): string {
  if (axios.isAxiosError(error)) {
    const data = error.response?.data as { message?: string } | undefined
    if (data?.message) return data.message
    if (error.response) return 'Permintaan tidak bisa diproses. Coba lagi.'
    return 'Tidak bisa menghubungi server'
  }

  return error instanceof Error ? error.message : 'Terjadi kesalahan'
}
