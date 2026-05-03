import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import apiClient from './client'

export const attendanceApi = {
  clockIn: (data: { lat: number; lng: number; device_hash: string; location_id?: string }) =>
    apiClient.post('/attendance/clock-in', data),
  clockOut: (data?: { lat?: number; lng?: number }) =>
    apiClient.post('/attendance/clock-out', data ?? {}),
  today: () => apiClient.get('/attendance/today'),
  list: (params?: Record<string, string>) => apiClient.get('/attendance', { params }),
}

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
