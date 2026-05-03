'use client'

import { useAuthStore } from '@/store/auth.store'
import type { RoleSlug } from '@/types'

interface RoleGateProps {
  allowedRoles: RoleSlug[]
  children: React.ReactNode
  fallback?: React.ReactNode
}

export function RoleGate({ allowedRoles, children, fallback = null }: RoleGateProps) {
  const hasAnyRole = useAuthStore((s) => s.hasAnyRole)
  if (!hasAnyRole(allowedRoles)) return <>{fallback}</>
  return <>{children}</>
}
