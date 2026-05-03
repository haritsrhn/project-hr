import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import apiClient from './client'

export const employeeApi = {
  list: (params?: Record<string, string>) =>
    apiClient.get('/employees', { params }),
  get: (id: string) => apiClient.get(`/employees/${id}`),
  create: (data: unknown) => apiClient.post('/employees', data),
  update: (id: string, data: unknown) => apiClient.put(`/employees/${id}`, data),
  remove: (id: string) => apiClient.delete(`/employees/${id}`),
}

export const useEmployees = (params?: Record<string, string>) =>
  useQuery({
    queryKey: ['employees', params],
    queryFn: () => employeeApi.list(params).then((r) => r.data),
  })

export const useEmployee = (id: string) =>
  useQuery({
    queryKey: ['employee', id],
    queryFn: () => employeeApi.get(id).then((r) => r.data),
    enabled: !!id,
  })

export const useCreateEmployee = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: unknown) => employeeApi.create(data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['employees'] }),
  })
}

export const useUpdateEmployee = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, data }: { id: string; data: unknown }) =>
      employeeApi.update(id, data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['employees'] }),
  })
}
