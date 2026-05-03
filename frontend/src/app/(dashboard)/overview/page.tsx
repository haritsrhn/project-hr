'use client'

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { useAuthStore } from '@/store/auth.store'
import { formatDate } from '@/lib/utils/format'

export default function OverviewPage() {
  const { user, activeEmployment } = useAuthStore()
  const today = formatDate(new Date().toISOString())

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">
          Selamat datang, {user?.name ?? 'Pengguna'}
        </h1>
        <p className="text-gray-500 mt-1">{today}</p>
      </div>

      {activeEmployment && (
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium text-gray-500">Jabatan</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-lg font-semibold">{activeEmployment.position}</p>
            </CardContent>
          </Card>
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium text-gray-500">Departemen</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-lg font-semibold">{activeEmployment.department}</p>
            </CardContent>
          </Card>
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium text-gray-500">Status</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-lg font-semibold">{activeEmployment.status}</p>
            </CardContent>
          </Card>
        </div>
      )}
    </div>
  )
}
