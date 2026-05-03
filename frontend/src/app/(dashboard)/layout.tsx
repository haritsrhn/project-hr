'use client'

import { useEffect } from 'react'
import Link from 'next/link'
import { usePathname, useRouter } from 'next/navigation'
import {
  LayoutDashboard,
  Users,
  Clock,
  CalendarOff,
  Banknote,
  Settings,
  LogOut,
} from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { useAuthStore } from '@/store/auth.store'
import { authApi } from '@/lib/api/auth'
import apiClient from '@/lib/api/client'
import { cn } from '@/lib/utils'
import { RoleGate } from '@/components/shared/RoleGate'
import type { RoleSlug } from '@/types'

type NavItem = {
  href: string
  label: string
  icon: React.ElementType
  roles: RoleSlug[] | null
}

const NAV_ITEMS: NavItem[] = [
  { href: '/overview', label: 'Overview', icon: LayoutDashboard, roles: null },
  { href: '/employees', label: 'Karyawan', icon: Users, roles: ['entity_admin', 'holding_admin', 'super_admin', 'manager'] },
  { href: '/attendance', label: 'Absensi', icon: Clock, roles: null },
  { href: '/leave', label: 'Cuti', icon: CalendarOff, roles: null },
  { href: '/payroll/runs', label: 'Payroll', icon: Banknote, roles: ['entity_admin', 'holding_admin', 'super_admin'] },
  { href: '/settings', label: 'Pengaturan', icon: Settings, roles: ['entity_admin', 'holding_admin', 'super_admin'] },
]

export default function DashboardLayout({ children }: { children: React.ReactNode }) {
  const pathname = usePathname()
  const router = useRouter()
  const { token, user, activeEmployment, clearAuth, setAuth } = useAuthStore()

  useEffect(() => {
    if (token && !user) {
      apiClient.get('/auth/me')
        .then(res => {
          setAuth(res.data.data, token)
        })
        .catch(() => {
          useAuthStore.getState().clearAuth()
        })
    }
  }, [token, user])

  const handleLogout = async () => {
    try {
      await authApi.logout()
    } finally {
      clearAuth()
      router.replace('/login')
    }
  }

  const initials = user?.name
    ? user.name.split(' ').map((n) => n[0]).join('').toUpperCase().slice(0, 2)
    : 'U'

  return (
    <div className="flex min-h-screen">
      {/* Sidebar */}
      <aside className="w-64 bg-white border-r flex flex-col shrink-0">
        {/* Brand */}
        <div className="px-6 py-5 border-b">
          <p className="font-semibold text-sm text-gray-900 truncate">
            {activeEmployment?.entity.name ?? 'HRIS Tridaya'}
          </p>
          <p className="text-xs text-gray-500 mt-0.5">Sistem Kepegawaian</p>
        </div>

        {/* Nav */}
        <nav className="flex-1 px-3 py-4 space-y-0.5">
          {NAV_ITEMS.map((item) => {
            const isActive = pathname === item.href || pathname.startsWith(item.href + '/')
            const Icon = item.icon

            const link = (
              <Link
                key={item.href}
                href={item.href}
                aria-current={isActive ? 'page' : undefined}
                className={cn(
                  'flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition-colors',
                  isActive
                    ? 'bg-gray-100 text-gray-900'
                    : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                )}
              >
                <Icon aria-hidden="true" className="h-4 w-4 shrink-0" />
                {item.label}
              </Link>
            )

            if (item.roles) {
              return (
                <RoleGate key={item.href} allowedRoles={item.roles}>
                  {link}
                </RoleGate>
              )
            }

            return link
          })}
        </nav>

        {/* User */}
        <div className="px-4 py-4 border-t">
          <div className="flex items-center gap-3 mb-3">
            <Avatar className="h-8 w-8">
              <AvatarFallback className="text-xs">{initials}</AvatarFallback>
            </Avatar>
            <div className="flex-1 min-w-0">
              <p className="text-sm font-medium text-gray-900 truncate">{user?.name}</p>
              <Badge variant="secondary" className="text-xs mt-0.5">
                {user?.roles?.[0] ?? 'employee'}
              </Badge>
            </div>
          </div>
          <Button
            variant="ghost"
            size="sm"
            className="w-full justify-start text-gray-600 hover:text-gray-900"
            onClick={handleLogout}
          >
            <LogOut aria-hidden="true" className="h-4 w-4 mr-2" />
            Keluar
          </Button>
        </div>
      </aside>

      {/* Main content */}
      <main className="flex-1 overflow-auto bg-gray-50">
        <div className="max-w-7xl mx-auto px-6 py-8">{children}</div>
      </main>
    </div>
  )
}
