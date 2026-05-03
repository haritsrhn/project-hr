'use client'

import { useState } from 'react'
import Link from 'next/link'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import { Plus, Loader2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Card, CardContent } from '@/components/ui/card'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { PayrollStatusBadge } from '@/components/modules/payroll/PayrollStatusBadge'
import { usePayrollRuns, useCreateRun } from '@/lib/api/payroll'
import { formatRupiah, formatMonth } from '@/lib/utils/format'
import type { PayrollRun } from '@/types'

const MONTHS = [
  { value: 1, label: 'Januari' },
  { value: 2, label: 'Februari' },
  { value: 3, label: 'Maret' },
  { value: 4, label: 'April' },
  { value: 5, label: 'Mei' },
  { value: 6, label: 'Juni' },
  { value: 7, label: 'Juli' },
  { value: 8, label: 'Agustus' },
  { value: 9, label: 'September' },
  { value: 10, label: 'Oktober' },
  { value: 11, label: 'November' },
  { value: 12, label: 'Desember' },
]

const currentYear = new Date().getFullYear()
const YEARS = [currentYear - 1, currentYear, currentYear + 1]

export default function PayrollRunsPage() {
  const [yearFilter, setYearFilter] = useState(String(currentYear))
  const [showCreate, setShowCreate] = useState(false)

  const { data, isPending } = usePayrollRuns({ year: yearFilter })
  const createRun = useCreateRun()

  const runs: PayrollRun[] = data?.data?.data ?? []

  const { register, handleSubmit, setValue, watch, reset } = useForm({
    defaultValues: { period_month: new Date().getMonth() + 1, period_year: currentYear },
  })

  const onCreateSubmit = async (formData: { period_month: number; period_year: number }) => {
    try {
      await createRun.mutateAsync({
        period_month: Number(formData.period_month),
        period_year: Number(formData.period_year),
      })
      toast.success('Payroll run berhasil dibuat.')
      setShowCreate(false)
      reset()
    } catch (err: unknown) {
      const msg =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        'Gagal membuat payroll run.'
      toast.error(msg)
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold text-gray-900">Payroll Runs</h1>
        <div className="flex items-center gap-3">
          <Select value={yearFilter} onValueChange={setYearFilter}>
            <SelectTrigger aria-label="Filter tahun" className="w-28">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              {YEARS.map((y) => (
                <SelectItem key={y} value={String(y)}>
                  {y}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          <Button size="sm" onClick={() => setShowCreate(true)}>
            <Plus aria-hidden="true" className="h-4 w-4 mr-2" />
            Buat Payroll Bulan Ini
          </Button>
        </div>
      </div>

      {isPending && (
        <div className="space-y-2">
          {[...Array(4)].map((_, i) => (
            <div key={i} className="h-14 bg-gray-100 rounded animate-pulse" />
          ))}
        </div>
      )}

      {!isPending && (
        <div className="bg-white rounded-lg border overflow-hidden">
          {runs.length === 0 ? (
            <div className="py-12 text-center text-gray-500">
              Belum ada payroll run untuk tahun {yearFilter}. Buat payroll bulan ini untuk memulai.
            </div>
          ) : (
            <table className="w-full text-sm">
              <thead className="bg-gray-50 border-b">
                <tr>
                  <th scope="col" className="px-4 py-3 text-left font-medium text-gray-600">Periode</th>
                  <th scope="col" className="px-4 py-3 text-right font-medium text-gray-600">Karyawan</th>
                  <th scope="col" className="px-4 py-3 text-right font-medium text-gray-600">Total Gross</th>
                  <th scope="col" className="px-4 py-3 text-right font-medium text-gray-600">Total Net</th>
                  <th scope="col" className="px-4 py-3 text-center font-medium text-gray-600">Status</th>
                  <th scope="col" className="px-4 py-3" />
                </tr>
              </thead>
              <tbody className="divide-y">
                {runs.map((run) => (
                  <tr key={run.id} className="hover:bg-gray-50">
                    <td className="px-4 py-3 font-medium text-gray-900">
                      {formatMonth(run.periodMonth, run.periodYear)}
                    </td>
                    <td className="px-4 py-3 text-right text-gray-700">{run.totalEmployees}</td>
                    <td className="px-4 py-3 text-right text-gray-700">
                      {run.totalGross ? formatRupiah(run.totalGross) : '—'}
                    </td>
                    <td className="px-4 py-3 text-right text-gray-700">
                      {run.totalNet ? formatRupiah(run.totalNet) : '—'}
                    </td>
                    <td className="px-4 py-3 text-center">
                      <PayrollStatusBadge status={run.status} />
                    </td>
                    <td className="px-4 py-3 text-right">
                      <Link href={`/payroll/${run.id}`} className="text-xs text-blue-600 hover:underline">
                        Lihat Detail
                      </Link>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>
      )}

      {/* Create modal */}
      <Dialog open={showCreate} onOpenChange={setShowCreate}>
        <DialogContent className="max-w-sm">
          <DialogHeader>
            <DialogTitle>Buat Payroll Baru</DialogTitle>
          </DialogHeader>
          <form onSubmit={handleSubmit(onCreateSubmit)} className="space-y-4">
            <div className="space-y-1.5">
              <Label htmlFor="select-month">Bulan</Label>
              <Select
                value={String(watch('period_month'))}
                onValueChange={(v) => setValue('period_month', Number(v))}
              >
                <SelectTrigger id="select-month">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {MONTHS.map((m) => (
                    <SelectItem key={m.value} value={String(m.value)}>
                      {m.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="select-year">Tahun</Label>
              <Select
                value={String(watch('period_year'))}
                onValueChange={(v) => setValue('period_year', Number(v))}
              >
                <SelectTrigger id="select-year">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {YEARS.map((y) => (
                    <SelectItem key={y} value={String(y)}>
                      {y}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="flex gap-2 justify-end pt-2">
              <Button type="button" variant="outline" onClick={() => setShowCreate(false)}>
                Batal
              </Button>
              <Button type="submit" disabled={createRun.isPending}>
                {createRun.isPending && <Loader2 aria-hidden="true" className="h-4 w-4 mr-2 animate-spin" />}
                Buat
              </Button>
            </div>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  )
}
