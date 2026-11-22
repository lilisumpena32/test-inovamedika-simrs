export type MenuAccess = {
  can_view: boolean
  can_create: boolean
  can_update: boolean
  can_delete: boolean
  can_auth: boolean
}

export type MenuNode = {
  kode: string
  label: string
  path: string | null
  icon: string | null
  sorter: number
  can: MenuAccess
  children: MenuNode[]
}

export type Profile = {
  id: string
  username: string
  full_name: string
  email: string | null
  role: {
    code: string
    name: string
  }
}

export type LoginResult = {
  token: string
  user: Profile
  menus: MenuNode[]
}

export type SessionData = {
  user: Profile
  menus: MenuNode[]
}

export type ApiResponse<T> = {
  status: true
  code: number
  data: T
}
