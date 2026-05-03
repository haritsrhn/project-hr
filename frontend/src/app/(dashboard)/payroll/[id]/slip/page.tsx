'use client'

import { use } from 'react'
import { useSearchParams, useRouter } from 'next/navigation'
import { Download, ArrowLeft, Printer } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { PayslipView } from '@/components/modules/payroll/PayslipView'
import { usePayrollSlip, usePayrollRun } from '@/lib/api/payroll'
import type { PayrollItem, PayrollRun } from '@/types'

export default function PayslipPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = use(params)
  const router = useRouter()
  const searchParams = useSearchParams()
  const itemId = searchParams.get('itemId') ?? ''

  const { data: slipData, isPending: loadingSlip } = usePayrollSlip(itemId)
  const { data: runData, isPending: loadingRun } = usePayrollRun(id)

  const slip: PayrollItem | null = slipData?.data ?? null
  const run: PayrollRun | null = runData?.data ?? null
  const slipUrl = slip?.slipUrl ?? null

  const isLoading = loadingSlip || loadingRun

  if (isLoading) {
    return (
      <div className="max-w-lg mx-auto space-y-4 pt-8">
        <div className="h-8 w-48 bg-gray-100 rounded animate-pulse" />
        <div className="h-96 bg-gray-100 rounded animate-pulse" />
      </div>
    )
  }

  if (!slip || !run) {
    return (
      <div className="text-center text-gray-500 py-12">
        <p>Slip gaji tidak ditemukan.</p>
        <Button variant="ghost" className="mt-4" onClick={() => router.back()}>
          Kembali
        </Button>
      </div>
    )
  }

  return (
    <div className="space-y-4">
      {/* Actions bar — hidden when printing */}
      <div className="flex items-center justify-between print:hidden">
        <Button variant="ghost" size="sm" onClick={() => router.back()}>
          <ArrowLeft className="h-4 w-4 mr-2" />
          Kembali
        </Button>
        <div className="flex gap-2">
          {slipUrl && (
            <Button
              variant="outline"
              size="sm"
              onClick={() => window.open(slipUrl, '_blank')}
            >
              <Download className="h-4 w-4 mr-2" />
              Download PDF
            </Button>
          )}
          <Button variant="outline" size="sm" onClick={() => window.print()}>
            <Printer className="h-4 w-4 mr-2" />
            Cetak
          </Button>
        </div>
      </div>

      <PayslipView item={slip} run={run} />

      <style jsx global>{`
        @media print {
          body * { visibility: hidden; }
          .print-area, .print-area * { visibility: visible; }
          .print-area { position: absolute; left: 0; top: 0; }
        }
      `}</style>
    </div>
  )
}
