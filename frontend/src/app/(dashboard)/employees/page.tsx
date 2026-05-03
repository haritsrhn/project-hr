'use client'

import Link from 'next/link'
import { Plus, Search } from 'lucide-react'
import { useState } from 'react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent } from '@/components/ui/card'
import { useEmployees } from '@/lib/api/employees'
import { useAuthStore } from '@/store/auth.store'
import type { Employment } from '@/types'

const STATUS_VARIANT: Record<string, 'default' | 'secondary' | 'destructive'> = {
  ACTIVE: 'default',
  INACTIVE: 'secondary',
  TERMINATED: 'destructive',
}

export default function EmployeesPage() {
  const { activeEmployment } = useAuthStore()
  const [search, setSearch] = useState('')

  const params: Record<string, string> = {}
  if (activeEmployment?.entityId) params.entity_id = activeEmployment.entityId

  const { data, isPending, isError } = useEmployees(params)

  const employees: { user: { id: string; name: string; email: string }; employments: Employment[] }[] =
    data?.data?.data ?? []

  const filtered = search
    ? employees.filter(
        (e) =>
          e.user.name.toLowerCase().includes(search.toLowerCase()) ||
          e.user.email.toLowerCase().includes(search.toLowerCase())
      )
    : employees

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold text-gray-900">Karyawan</h1>
        <Link href="/employees/new">
          <Button size="sm">
            <Plus aria-hidden="true" className="h-4 w-4 mr-2" />
            Tambah Karyawan
          </Button>
        </Link>
      </div>

      <div className="relative max-w-sm">
        <Search aria-hidden="true" className="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
        <Input
          aria-label="Cari karyawan"
          placeholder="Cari nama atau email..."
          className="pl-9"
          value={search}
          onChange={(e) => setSearch(e.target.value)}
        />
      </div>

      {isPending && (
        <div className="space-y-2">
          {[...Array(5)].map((_, i) => (
            <div key={i} className="h-14 bg-gray-100 rounded-md animate-pulse" />
          ))}
        </div>
      )}

      {isError && (
        <Card>
          <CardContent className="py-8 text-center text-gray-500">
            Gagal memuat data karyawan.
          </CardContent>
        </Card>
      )}

      {!isPending && !isError && (
        <div className="bg-white rounded-lg border overflow-hidden">
          {filtered.length === 0 ? (
            <div className="py-12 text-center text-gray-500">
              {search ? 'Tidak ada karyawan yang cocok.' : 'Belum ada karyawan.'}
            </div>
          ) : (
            <table className="w-full text-sm">
              <thead className="bg-gray-50 border-b">
                <tr>
                  <th scope="col" className="px-4 py-3 text-left font-medium text-gray-600">Nama</th>
                  <th scope="col" className="px-4 py-3 text-left font-medium text-gray-600">Jabatan</th>
                  <th scope="col" className="px-4 py-3 text-left font-medium text-gray-600">Departemen</th>
                  <th scope="col" className="px-4 py-3 text-left font-medium text-gray-600">Tipe</th>
                  <th scope="col" className="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                  <th scope="col" className="px-4 py-3" />
                </tr>
              </thead>
              <tbody className="divide-y">
                {filtered.map((emp) => {
                  const primary = emp.employments.find((e) => e.isPrimary) ?? emp.employments[0]
                  return (
                    <tr key={emp.user.id} className="hover:bg-gray-50">
                      <td className="px-4 py-3">
                        <div>
                          <p className="font-medium text-gray-900">{emp.user.name}</p>
                          <p className="text-xs text-gray-500">{emp.user.email}</p>
                        </div>
                      </td>
                      <td className="px-4 py-3 text-gray-700">{primary?.position ?? '—'}</td>
                      <td className="px-4 py-3 text-gray-700">{primary?.department ?? '—'}</td>
                      <td className="px-4 py-3 text-gray-700">{primary?.employmentType ?? '—'}</td>
                      <td className="px-4 py-3">
                        {primary?.status && (
                          <Badge variant={STATUS_VARIANT[primary.status] ?? 'secondary'}>
                            {primary.status}
                          </Badge>
                        )}
                      </td>
                      <td className="px-4 py-3 text-right">
                        <Link
                          href={`/employees/${emp.user.id}`}
                          className="text-xs text-blue-600 hover:underline"
                        >
                          Detail
                        </Link>
                      </td>
                    </tr>
                  )
                })}
              </tbody>
            </table>
          )}
        </div>
      )}
    </div>
  )
}
