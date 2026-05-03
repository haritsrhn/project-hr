'use client'

import { useState } from 'react'
import { Loader2, Download, BarChart3 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { RoleGate } from '@/components/shared/RoleGate'
import { useMonthlyReport } from '@/lib/api/attendance'

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

const YEARS = [2023, 2024, 2025, 2026]

interface EmployeeReport {
  employment_id: string
  name: string
  nik: string
  position: string
  present: number
  late: number
  absent: number
  leave: number
}

interface MonthlyReportData {
  month: number
  year: number
  working_days: number
  employees: EmployeeReport[]
}

function exportToCsv(data: MonthlyReportData) {
  const monthLabel = MONTHS.find((m) => m.value === data.month)?.label ?? data.month
  const headers = ['Nama', 'NIK', 'Posisi', 'Hadir', 'Terlambat', 'Tidak Hadir', 'Cuti', '% Kehadiran', 'Total Hari Kerja']
  const rows = data.employees.map((emp) => {
    const attendanceRate = data.working_days > 0
      ? (((emp.present) / data.working_days) * 100).toFixed(1)
      : '0.0'
    return [
      emp.name,
      emp.nik ?? '',
      emp.position,
      emp.present,
      emp.late,
      emp.absent,
      emp.leave,
      `${attendanceRate}%`,
      data.working_days,
    ]
  })

  const csvContent = [headers, ...rows]
    .map((row) => row.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(','))
    .join('\n')

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `rekap-kehadiran-${monthLabel}-${data.year}.csv`
  link.click()
  URL.revokeObjectURL(url)
}

function ReportTable({ data }: { data: MonthlyReportData }) {
  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <p className="text-sm text-gray-500">
          Total hari kerja:{' '}
          <span className="font-semibold text-gray-900">{data.working_days} hari</span>
          {' '}— {data.employees.length} karyawan
        </p>
        <Button variant="outline" size="sm" onClick={() => exportToCsv(data)}>
          <Download aria-hidden="true" className="h-4 w-4 mr-2" />
          Export CSV
        </Button>
      </div>

      <div className="overflow-x-auto rounded-md border">
        <table className="w-full text-sm">
          <thead className="bg-gray-50 text-gray-700">
            <tr>
              <th className="px-4 py-3 text-left font-medium">Nama</th>
              <th className="px-4 py-3 text-left font-medium">NIK</th>
              <th className="px-4 py-3 text-left font-medium">Posisi</th>
              <th className="px-4 py-3 text-center font-medium">Hadir</th>
              <th className="px-4 py-3 text-center font-medium">Terlambat</th>
              <th className="px-4 py-3 text-center font-medium">Tidak Hadir</th>
              <th className="px-4 py-3 text-center font-medium">Cuti</th>
              <th className="px-4 py-3 text-center font-medium">% Kehadiran</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            {data.employees.map((emp) => {
              const attendanceRate = data.working_days > 0
                ? (emp.present / data.working_days) * 100
                : 0
              const isLowAttendance = attendanceRate < 80

              return (
                <tr
                  key={emp.employment_id}
                  className={isLowAttendance ? 'bg-red-50' : 'bg-white hover:bg-gray-50'}
                >
                  <td className="px-4 py-3 font-medium text-gray-900">{emp.name}</td>
                  <td className="px-4 py-3 text-gray-600">{emp.nik ?? '-'}</td>
                  <td className="px-4 py-3 text-gray-600">{emp.position}</td>
                  <td className="px-4 py-3 text-center">
                    <span className="inline-flex items-center justify-center w-8 h-6 rounded text-xs font-semibold bg-green-100 text-green-700">
                      {emp.present}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-center">
                    <span className="inline-flex items-center justify-center w-8 h-6 rounded text-xs font-semibold bg-yellow-100 text-yellow-700">
                      {emp.late}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-center">
                    <span className="inline-flex items-center justify-center w-8 h-6 rounded text-xs font-semibold bg-red-100 text-red-700">
                      {emp.absent}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-center">
                    <span className="inline-flex items-center justify-center w-8 h-6 rounded text-xs font-semibold bg-blue-100 text-blue-700">
                      {emp.leave}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-center">
                    <span
                      className={`inline-flex items-center justify-center px-2 h-6 rounded text-xs font-semibold ${
                        isLowAttendance
                          ? 'bg-red-200 text-red-800'
                          : 'bg-green-100 text-green-700'
                      }`}
                    >
                      {attendanceRate.toFixed(1)}%
                    </span>
                  </td>
                </tr>
              )
            })}
          </tbody>
        </table>
      </div>
    </div>
  )
}

function AttendanceReportContent() {
  const now = new Date()
  const [selectedMonth, setSelectedMonth] = useState<number>(now.getMonth() + 1)
  const [selectedYear, setSelectedYear] = useState<number>(now.getFullYear())
  const [fetchParams, setFetchParams] = useState<{ month: number; year: number } | null>(null)

  const { data, isPending, isError } = useMonthlyReport(
    fetchParams?.month ?? selectedMonth,
    fetchParams?.year ?? selectedYear,
    fetchParams !== null,
  )

  const reportData: MonthlyReportData | null = data?.data ?? null

  const handleFetch = () => {
    setFetchParams({ month: selectedMonth, year: selectedYear })
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <BarChart3 aria-hidden="true" className="h-6 w-6 text-gray-700" />
        <h1 className="text-2xl font-bold text-gray-900">Rekap Kehadiran Bulanan</h1>
      </div>

      <Card>
        <CardHeader className="pb-4">
          <CardTitle className="text-base">Filter Periode</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="flex flex-wrap items-end gap-4">
            <div className="space-y-1.5">
              <label className="text-sm font-medium text-gray-700">Bulan</label>
              <Select
                value={String(selectedMonth)}
                onValueChange={(v) => setSelectedMonth(Number(v))}
              >
                <SelectTrigger className="w-40">
                  <SelectValue placeholder="Pilih bulan..." />
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
              <label className="text-sm font-medium text-gray-700">Tahun</label>
              <Select
                value={String(selectedYear)}
                onValueChange={(v) => setSelectedYear(Number(v))}
              >
                <SelectTrigger className="w-28">
                  <SelectValue placeholder="Pilih tahun..." />
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

            <Button onClick={handleFetch} disabled={isPending}>
              {isPending && <Loader2 aria-hidden="true" className="h-4 w-4 mr-2 animate-spin" />}
              Lihat Rekap
            </Button>
          </div>
        </CardContent>
      </Card>

      {isPending && fetchParams !== null && (
        <div className="flex items-center justify-center py-16 text-gray-500">
          <Loader2 aria-hidden="true" className="h-6 w-6 animate-spin mr-3" />
          <span>Memuat data rekap...</span>
        </div>
      )}

      {isError && (
        <div role="alert" className="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          Gagal memuat data rekap. Pastikan Anda memiliki akses yang cukup.
        </div>
      )}

      {!isPending && reportData && (
        <Card>
          <CardHeader className="pb-4">
            <CardTitle className="text-base">
              Rekap {MONTHS.find((m) => m.value === reportData.month)?.label} {reportData.year}
            </CardTitle>
          </CardHeader>
          <CardContent>
            {reportData.employees.length === 0 ? (
              <p className="text-sm text-gray-500 text-center py-8">
                Tidak ada data karyawan untuk periode ini.
              </p>
            ) : (
              <ReportTable data={reportData} />
            )}
          </CardContent>
        </Card>
      )}
    </div>
  )
}

export default function AttendanceReportPage() {
  return (
    <RoleGate
      allowedRoles={['entity_admin', 'holding_admin', 'super_admin']}
      fallback={
        <div className="flex items-center justify-center py-24 text-gray-500">
          <p>Anda tidak memiliki akses ke halaman ini.</p>
        </div>
      }
    >
      <AttendanceReportContent />
    </RoleGate>
  )
}
