'use client'

import { use, useState } from 'react'
import { useRouter } from 'next/navigation'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import { ArrowLeft, Pencil, X, Save, Loader2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { useEmployee, useUpdateEmployee } from '@/lib/api/employees'
import { formatRupiah } from '@/lib/utils/format'

export default function EmployeeDetailPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = use(params)
  const router = useRouter()
  const [editing, setEditing] = useState(false)

  const { data, isPending, isError } = useEmployee(id)
  const updateEmployee = useUpdateEmployee()

  const employee = data?.data

  const { register, handleSubmit, reset } = useForm({
    values: employee
      ? {
          name: employee.user?.name ?? '',
          email: employee.user?.email ?? '',
          phone: employee.user?.phone ?? '',
          position: employee.employments?.[0]?.position ?? '',
          department: employee.employments?.[0]?.department ?? '',
          salaryBasic: employee.employments?.[0]?.salaryBasic ?? 0,
        }
      : undefined,
  })

  const onSubmit = async (formData: {
    name: string
    email: string
    phone: string
    position: string
    department: string
    salaryBasic: number
  }) => {
    try {
      await updateEmployee.mutateAsync({ id, data: formData })
      toast.success('Data karyawan berhasil diperbarui.')
      setEditing(false)
    } catch {
      toast.error('Gagal memperbarui data karyawan.')
    }
  }

  if (isPending) {
    return (
      <div className="space-y-4">
        <div className="h-8 w-48 bg-gray-100 rounded animate-pulse" />
        <div className="h-48 bg-gray-100 rounded animate-pulse" />
      </div>
    )
  }

  if (isError || !employee) {
    return (
      <div className="text-center text-gray-500 py-12">
        <p>Karyawan tidak ditemukan.</p>
        <Button variant="ghost" className="mt-4" onClick={() => router.back()}>
          Kembali
        </Button>
      </div>
    )
  }

  const primary = employee.employments?.find((e: { isPrimary: boolean }) => e.isPrimary) ?? employee.employments?.[0]

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-4">
        <Button variant="ghost" size="sm" onClick={() => router.back()}>
          <ArrowLeft className="h-4 w-4 mr-2" />
          Kembali
        </Button>
        <h1 className="text-2xl font-bold text-gray-900">{employee.user?.name}</h1>
        {primary?.status && <Badge>{primary.status}</Badge>}
      </div>

      <form onSubmit={handleSubmit(onSubmit)}>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle>Informasi Karyawan</CardTitle>
            <div className="flex gap-2">
              {editing ? (
                <>
                  <Button type="button" variant="ghost" size="sm" onClick={() => { setEditing(false); reset() }}>
                    <X className="h-4 w-4 mr-1" />Batal
                  </Button>
                  <Button type="submit" size="sm" disabled={updateEmployee.isPending}>
                    {updateEmployee.isPending ? <Loader2 className="h-4 w-4 mr-1 animate-spin" /> : <Save className="h-4 w-4 mr-1" />}
                    Simpan
                  </Button>
                </>
              ) : (
                <Button type="button" variant="outline" size="sm" onClick={() => setEditing(true)}>
                  <Pencil className="h-4 w-4 mr-1" />Edit
                </Button>
              )}
            </div>
          </CardHeader>
          <CardContent className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div className="space-y-1">
              <Label>Nama</Label>
              {editing ? <Input {...register('name')} /> : <p className="text-sm text-gray-900 py-2">{employee.user?.name}</p>}
            </div>
            <div className="space-y-1">
              <Label>Email</Label>
              {editing ? <Input type="email" {...register('email')} /> : <p className="text-sm text-gray-900 py-2">{employee.user?.email}</p>}
            </div>
            <div className="space-y-1">
              <Label>Telepon</Label>
              {editing ? <Input {...register('phone')} /> : <p className="text-sm text-gray-900 py-2">{employee.user?.phone ?? '—'}</p>}
            </div>
            <div className="space-y-1">
              <Label>Jabatan</Label>
              {editing ? <Input {...register('position')} /> : <p className="text-sm text-gray-900 py-2">{primary?.position ?? '—'}</p>}
            </div>
            <div className="space-y-1">
              <Label>Departemen</Label>
              {editing ? <Input {...register('department')} /> : <p className="text-sm text-gray-900 py-2">{primary?.department ?? '—'}</p>}
            </div>
            <div className="space-y-1">
              <Label>Gaji Pokok</Label>
              {editing ? (
                <Input type="number" {...register('salaryBasic', { valueAsNumber: true })} />
              ) : (
                <p className="text-sm text-gray-900 py-2">
                  {primary?.salaryBasic ? formatRupiah(primary.salaryBasic) : '—'}
                </p>
              )}
            </div>
          </CardContent>
        </Card>
      </form>
    </div>
  )
}
