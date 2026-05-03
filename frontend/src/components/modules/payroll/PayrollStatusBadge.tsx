import { Badge } from '@/components/ui/badge'
import type { PayrollRunStatus } from '@/types'

const CONFIG: Record<PayrollRunStatus, { label: string; className: string }> = {
  DRAFT: { label: 'Draft', className: 'bg-gray-100 text-gray-700 border-gray-200' },
  PROCESSED: { label: 'Diproses', className: 'bg-blue-100 text-blue-700 border-blue-200' },
  PAID: { label: 'Lunas', className: 'bg-green-100 text-green-700 border-green-200' },
}

export function PayrollStatusBadge({ status }: { status: PayrollRunStatus }) {
  const { label, className } = CONFIG[status] ?? CONFIG.DRAFT
  return (
    <Badge variant="outline" className={className}>
      {label}
    </Badge>
  )
}
