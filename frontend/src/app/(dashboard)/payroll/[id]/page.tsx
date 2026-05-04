'use client'

import { use, useState, useEffect } from 'react'
import Link from 'next/link'
import { useRouter } from 'next/navigation'
import { toast } from 'sonner'
import { ArrowLeft, Settings, Lock, Loader2, Download } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog'
import { PayrollStatusBadge } from '@/components/modules/payroll/PayrollStatusBadge'
import { usePayrollRun, usePayrollItems, useProcessRun, useLockRun } from '@/lib/api/payroll'
import { formatRupiah, formatMonth } from '@/lib/utils/format'
import apiClient from '@/lib/api/client'
import type { PayrollItem } from '@/types'

export default function PayrollRunDetailPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = use(params)
  const router = useRouter()
  const [polling, setPolling] = useState(false)
  const [showLockConfirm, setShowLockConfirm] = useState(false)
  const [exporting, setExporting] = useState(false)

  const { data: runData, isPending: loadingRun } = usePayrollRun(id, polling ? 3000 : false)
  const run = runData?.data

  const { data: itemsData, isPending: loadingItems } = usePayrollItems(
    run?.status !== 'DRAFT' ? id : ''
  )
  const items: PayrollItem[] = itemsData?.data?.data ?? []

  const processRun = useProcessRun()
  const lockRun = useLockRun()

  // Stop polling once status changes from DRAFT
  useEffect(() => {
    if (polling && run && run.status !== 'DRAFT') {
      setPolling(false)
    }
  }, [run, polling])

  const handleProcess = async () => {
    try {
      await processRun.mutateAsync(id)
      setPolling(true)
      toast.info('Proses kalkulasi dimulai. Menunggu hasil...')
    } catch (err: unknown) {
      const msg =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        'Gagal memulai proses.'
      toast.error(msg)
    }
  }

  const handleLock = async () => {
    try {
      await lockRun.mutateAsync(id)
      toast.success('Payroll run telah dikunci.')
      setShowLockConfirm(false)
    } catch (err: unknown) {
      const msg =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        'Gagal mengunci payroll run.'
      toast.error(msg)
    }
  }

  const handleExport = async () => {
    setExporting(true)
    try {
      const response = await apiClient.get(`/payroll/runs/${id}/export`, {
        responseType: 'blob',
      })
      const disposition = (response.headers['content-disposition'] as string) ?? ''
      const match = disposition.match(/filename="([^"]+)"/)
      const filename = match?.[1] ?? `payroll-${id}.csv`
      const url = URL.createObjectURL(new Blob([response.data]))
      const a = document.createElement('a')
      a.href = url
      a.download = filename
      document.body.appendChild(a)
      a.click()
      document.body.removeChild(a)
      URL.revokeObjectURL(url)
    } catch {
      toast.error('Gagal mengekspor laporan.')
    } finally {
      setExporting(false)
    }
  }

  if (loadingRun) {
    return (
      <div className="space-y-4">
        <div className="h-8 w-48 bg-gray-100 rounded animate-pulse" />
        <div className="h-32 bg-gray-100 rounded animate-pulse" />
      </div>
    )
  }

  if (!run) {
    return (
      <div className="text-center text-gray-500 py-12">
        <p>Payroll run tidak ditemukan.</p>
        <Button variant="ghost" className="mt-4" onClick={() => router.back()}>
          Kembali
        </Button>
      </div>
    )
  }

  const period = formatMonth(run.periodMonth, run.periodYear)

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Button variant="ghost" size="sm" onClick={() => router.back()}>
            <ArrowLeft aria-hidden="true" className="h-4 w-4 mr-2" />
            Kembali
          </Button>
          <div>
            <h1 className="text-2xl font-bold text-gray-900">Payroll {period}</h1>
          </div>
          <PayrollStatusBadge status={run.status} />
          {polling && (
            <div className="flex items-center gap-1.5 text-sm text-blue-600">
              <Loader2 aria-hidden="true" className="h-4 w-4 animate-spin" />
              Memproses...
            </div>
          )}
        </div>
        <div className="flex gap-2">
          {run.status === 'DRAFT' && !polling && (
            <Button onClick={handleProcess} disabled={processRun.isPending}>
              {processRun.isPending
                ? <Loader2 aria-hidden="true" className="h-4 w-4 mr-2 animate-spin" />
                : <Settings aria-hidden="true" className="h-4 w-4 mr-2" />}
              Proses Kalkulasi
            </Button>
          )}
          {run.status === 'PROCESSED' && (
            <Button variant="outline" onClick={() => setShowLockConfirm(true)}>
              <Lock aria-hidden="true" className="h-4 w-4 mr-2" />
              Finalisasi & Kunci
            </Button>
          )}
          {(run.status === 'PROCESSED' || run.status === 'PAID') && (
            <Button variant="outline" size="sm" onClick={handleExport} disabled={exporting}>
              {exporting
                ? <Loader2 aria-hidden="true" className="h-4 w-4 mr-2 animate-spin" />
                : <Download aria-hidden="true" className="h-4 w-4 mr-2" />}
              Export Excel
            </Button>
          )}
        </div>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-3 gap-4">
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-gray-500">Total Karyawan</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-bold">{run.totalEmployees ?? 0}</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-gray-500">Total Gross</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-xl font-bold">{run.totalGross ? formatRupiah(run.totalGross) : '—'}</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-gray-500">Total Net</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-xl font-bold">{run.totalNet ? formatRupiah(run.totalNet) : '—'}</p>
          </CardContent>
        </Card>
      </div>

      {/* Items table */}
      {run.status !== 'DRAFT' && (
        <div className="bg-white rounded-lg border overflow-hidden">
          {loadingItems ? (
            <div className="p-6 space-y-2">
              {[...Array(5)].map((_, i) => (
                <div key={i} className="h-10 bg-gray-100 rounded animate-pulse" />
              ))}
            </div>
          ) : (
            <>
              <table className="w-full text-sm">
                <thead className="bg-gray-50 border-b">
                  <tr>
                    <th scope="col" className="px-4 py-3 text-left font-medium text-gray-600">Karyawan</th>
                    <th scope="col" className="px-4 py-3 text-left font-medium text-gray-600">Jabatan</th>
                    <th scope="col" className="px-4 py-3 text-right font-medium text-gray-600">Gross</th>
                    <th scope="col" className="px-4 py-3 text-right font-medium text-gray-600">BPJS</th>
                    <th scope="col" className="px-4 py-3 text-right font-medium text-gray-600">PPh 21</th>
                    <th scope="col" className="px-4 py-3 text-right font-medium text-gray-600">Net</th>
                    <th scope="col" className="px-4 py-3" />
                  </tr>
                </thead>
                <tbody className="divide-y">
                  {items.map((item) => {
                    const bpjsTotal =
                      (item.bpjsKesEmployee ?? 0) + (item.bpjsJhtEmployee ?? 0)
                    return (
                      <tr key={item.id} className="hover:bg-gray-50">
                        <td className="px-4 py-3">
                          <p className="font-medium text-gray-900">
                            {item.employee?.user?.name ?? '—'}
                          </p>
                        </td>
                        <td className="px-4 py-3 text-gray-700">
                          {item.employee?.position ?? '—'}
                        </td>
                        <td className="px-4 py-3 text-right text-gray-700">
                          {formatRupiah(item.grossSalary)}
                        </td>
                        <td className="px-4 py-3 text-right text-gray-700">
                          {formatRupiah(bpjsTotal)}
                        </td>
                        <td className="px-4 py-3 text-right text-gray-700">
                          {formatRupiah(item.pph21Amount)}
                        </td>
                        <td className="px-4 py-3 text-right font-medium text-gray-900">
                          {formatRupiah(item.netSalary)}
                        </td>
                        <td className="px-4 py-3 text-right">
                          <Link
                            href={`/payroll/${id}/slip?itemId=${item.id}`}
                            className="text-xs text-blue-600 hover:underline"
                          >
                            Slip
                          </Link>
                        </td>
                      </tr>
                    )
                  })}
                </tbody>
                <tfoot className="bg-gray-50 border-t">
                  <tr>
                    <td colSpan={5} className="px-4 py-3 text-sm font-semibold text-right text-gray-700">
                      Total Net
                    </td>
                    <td className="px-4 py-3 text-right font-bold text-gray-900">
                      {formatRupiah(items.reduce((s, i) => s + i.netSalary, 0))}
                    </td>
                    <td />
                  </tr>
                </tfoot>
              </table>
            </>
          )}
        </div>
      )}

      {/* Lock confirm */}
      <Dialog open={showLockConfirm} onOpenChange={setShowLockConfirm}>
        <DialogContent className="max-w-sm">
          <DialogHeader>
            <DialogTitle>Finalisasi Payroll</DialogTitle>
            <DialogDescription>
              Payroll {period} akan dikunci. Data tidak dapat diubah setelah dikunci. Lanjutkan?
            </DialogDescription>
          </DialogHeader>
          <div className="flex justify-end gap-2 pt-2">
            <Button variant="outline" onClick={() => setShowLockConfirm(false)}>
              Batal
            </Button>
            <Button onClick={handleLock} disabled={lockRun.isPending}>
              {lockRun.isPending && <Loader2 aria-hidden="true" className="h-4 w-4 mr-2 animate-spin" />}
              Ya, Kunci
            </Button>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  )
}
