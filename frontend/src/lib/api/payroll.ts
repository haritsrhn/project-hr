import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import apiClient from './client'

export const payrollApi = {
  getRuns: (params?: { year?: string }) =>
    apiClient.get('/payroll/runs', { params }),
  getRun: (id: string) => apiClient.get(`/payroll/runs/${id}`),
  createRun: (data: { period_month: number; period_year: number }) =>
    apiClient.post('/payroll/runs', data),
  processRun: (id: string) => apiClient.post(`/payroll/runs/${id}/process`),
  lockRun: (id: string) => apiClient.post(`/payroll/runs/${id}/lock`),
  getItems: (runId: string, params?: { department?: string }) =>
    apiClient.get(`/payroll/runs/${runId}/items`, { params }),
  getSlip: (itemId: string) => apiClient.get(`/payroll/items/${itemId}/slip`),
}

export const usePayrollRuns = (params?: { year?: string }) =>
  useQuery({
    queryKey: ['payroll-runs', params],
    queryFn: () => payrollApi.getRuns(params).then((r) => r.data),
  })

export const usePayrollRun = (id: string, refetchInterval?: number | false) =>
  useQuery({
    queryKey: ['payroll-run', id],
    queryFn: () => payrollApi.getRun(id).then((r) => r.data),
    enabled: !!id,
    refetchInterval: refetchInterval ?? false,
  })

export const usePayrollItems = (runId: string) =>
  useQuery({
    queryKey: ['payroll-items', runId],
    queryFn: () => payrollApi.getItems(runId).then((r) => r.data),
    enabled: !!runId,
  })

export const usePayrollSlip = (itemId: string) =>
  useQuery({
    queryKey: ['payroll-slip', itemId],
    queryFn: () => payrollApi.getSlip(itemId).then((r) => r.data),
    enabled: !!itemId,
  })

export const useCreateRun = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: { period_month: number; period_year: number }) =>
      payrollApi.createRun(data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['payroll-runs'] }),
  })
}

export const useProcessRun = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: string) => payrollApi.processRun(id),
    onSuccess: (_, id) => qc.invalidateQueries({ queryKey: ['payroll-run', id] }),
  })
}

export const useLockRun = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: string) => payrollApi.lockRun(id),
    onSuccess: (_, id) => {
      qc.invalidateQueries({ queryKey: ['payroll-run', id] })
      qc.invalidateQueries({ queryKey: ['payroll-runs'] })
    },
  })
}
