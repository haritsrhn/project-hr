import { formatRupiah, formatMonth } from '@/lib/utils/format'
import type { PayrollItem, PayrollRun } from '@/types'

interface PayslipViewProps {
  item: PayrollItem
  run: PayrollRun
}

export function PayslipView({ item, run }: PayslipViewProps) {
  const period = formatMonth(run.periodMonth, run.periodYear)
  const employee = item.employee
  const user = employee?.user

  const allowances = item.allowances ?? []
  const deductions = item.deductions ?? []

  const bpjsKes = item.bpjsKesEmployee ?? 0
  const bpjsJht = item.bpjsJhtEmployee ?? 0
  const pph21 = item.pph21Amount ?? 0
  const customDeductions = deductions.reduce((s, d) => s + d.amount, 0)
  const totalDeductions = bpjsKes + bpjsJht + pph21 + customDeductions

  return (
    <div className="bg-white border rounded-lg p-8 max-w-lg mx-auto font-mono text-sm print:border-none print:shadow-none">
      {/* Header */}
      <div className="text-center mb-6 border-b pb-4">
        <p className="text-base font-bold uppercase">PT Tridaya Sejahtera</p>
        <p className="font-semibold mt-2 uppercase tracking-wide">Slip Gaji — {period}</p>
      </div>

      {/* Employee info */}
      <table className="w-full mb-4 text-xs">
        <tbody>
          <tr>
            <td className="text-gray-500 w-28 py-0.5">Nama</td>
            <td>: {user?.name ?? '—'}</td>
          </tr>
          <tr>
            <td className="text-gray-500 py-0.5">NIK</td>
            <td>: {employee?.employeeNumber ?? '—'}</td>
          </tr>
          <tr>
            <td className="text-gray-500 py-0.5">Jabatan</td>
            <td>: {employee?.position ?? '—'}</td>
          </tr>
          <tr>
            <td className="text-gray-500 py-0.5">Departemen</td>
            <td>: {employee?.department ?? '—'}</td>
          </tr>
          <tr>
            <td className="text-gray-500 py-0.5">Periode</td>
            <td>: {period}</td>
          </tr>
        </tbody>
      </table>

      {/* Earnings */}
      <div className="border-t pt-3 mb-3">
        <p className="font-semibold text-xs uppercase text-gray-500 mb-1.5">Penghasilan</p>
        <table className="w-full text-xs">
          <tbody>
            {allowances.map((a, i) => (
              <tr key={i}>
                <td className="py-0.5">{a.name}</td>
                <td className="text-right">{formatRupiah(a.amount)}</td>
              </tr>
            ))}
            <tr className="border-t font-semibold">
              <td className="py-1">Total Bruto</td>
              <td className="text-right">{formatRupiah(item.grossSalary)}</td>
            </tr>
          </tbody>
        </table>
      </div>

      {/* Deductions */}
      <div className="border-t pt-3 mb-3">
        <p className="font-semibold text-xs uppercase text-gray-500 mb-1.5">Potongan</p>
        <table className="w-full text-xs">
          <tbody>
            {bpjsKes > 0 && (
              <tr>
                <td className="py-0.5">BPJS Kesehatan</td>
                <td className="text-right">{formatRupiah(bpjsKes)}</td>
              </tr>
            )}
            {bpjsJht > 0 && (
              <tr>
                <td className="py-0.5">BPJS JHT</td>
                <td className="text-right">{formatRupiah(bpjsJht)}</td>
              </tr>
            )}
            {pph21 > 0 && (
              <tr>
                <td className="py-0.5">PPh 21</td>
                <td className="text-right">{formatRupiah(pph21)}</td>
              </tr>
            )}
            {deductions.map((d, i) => (
              <tr key={i}>
                <td className="py-0.5">{d.name}</td>
                <td className="text-right">{formatRupiah(d.amount)}</td>
              </tr>
            ))}
            <tr className="border-t font-semibold">
              <td className="py-1">Total Potongan</td>
              <td className="text-right">{formatRupiah(totalDeductions)}</td>
            </tr>
          </tbody>
        </table>
      </div>

      {/* Net */}
      <div className="border-t-2 border-gray-900 pt-3">
        <table className="w-full text-sm font-bold">
          <tbody>
            <tr>
              <td>GAJI BERSIH</td>
              <td className="text-right">{formatRupiah(item.netSalary)}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  )
}
