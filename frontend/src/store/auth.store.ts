import { create } from 'zustand'
import { persist } from 'zustand/middleware'
import type { User, Employment, RoleSlug } from '@/types'

interface AuthState {
  user: User | null
  token: string | null
  activeEmployment: Employment | null
  setAuth: (user: User, token: string) => void
  setActiveEmployment: (employment: Employment) => void
  clearAuth: () => void
  hasRole: (role: RoleSlug) => boolean
  hasAnyRole: (roles: RoleSlug[]) => boolean
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      user: null,
      token: null,
      activeEmployment: null,

      setAuth: (user, token) => {
        if (typeof window !== 'undefined') {
          localStorage.setItem('auth_token', token)
          document.cookie = `auth_token=${token}; path=/; max-age=86400; SameSite=Lax`
        }
        set({ user, token, activeEmployment: user.primaryEmployment })
      },

      setActiveEmployment: (employment) => set({ activeEmployment: employment }),

      clearAuth: () => {
        if (typeof window !== 'undefined') {
          localStorage.removeItem('auth_token')
          document.cookie = 'auth_token=; path=/; max-age=0'
        }
        set({ user: null, token: null, activeEmployment: null })
      },

      hasRole: (role) => get().user?.roles.includes(role) ?? false,

      hasAnyRole: (roles) =>
        roles.some((role) => get().user?.roles.includes(role) ?? false),
    }),
    { name: 'auth-store', partialize: (state) => ({ token: state.token }) }
  )
)
