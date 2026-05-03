import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import apiClient from './client'

export const attendanceApi = {
  clockIn: (data: { lat: number; lng: number; device_hash: string; location_id?: string }) =>
    apiClient.post('/attendance/clock-in', data),
  clockOut: (data?: { lat?: number; lng?: number }) =>
    apiClient.post('/attendance/clock-out', data ?? {}),
  today: () => apiClient.get('/attendance/today'),
  list: (params?: Record<string, string>) => apiClient.get('/attendance', { params }),
  monthlyReport: (month: number, year: number) =>
    apiClient.get('/attendance/monthly-report', { params: { month, year } }),
}

export function getMonthlyReport(month: number, year: number) {
  return apiClient.get('/attendance/monthly-report', { params: { month, year } })
}

export const useMonthlyReport = (month: number, year: number, enabled: boolean) =>
  useQuery({
    queryKey: ['attendance-monthly-report', month, year],
    queryFn: () => attendanceApi.monthlyReport(month, year).then((r) => r.data),
    enabled,
    retry: false,
  })

export const useTodayAttendance = () =>
  useQuery({
    queryKey: ['attendance-today'],
    queryFn: () => attendanceApi.today().then((r) => r.data),
    retry: false,
  })

export const useClockIn = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: { lat: number; lng: number; device_hash: string; location_id?: string }) =>
      attendanceApi.clockIn(data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['attendance-today'] }),
  })
}

export const useClockOut = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data?: { lat?: number; lng?: number }) => attendanceApi.clockOut(data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['attendance-today'] }),
  })
}
