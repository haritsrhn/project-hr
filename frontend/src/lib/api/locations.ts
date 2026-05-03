import { useQuery } from '@tanstack/react-query'
import apiClient from './client'

export const locationApi = {
  list: () => apiClient.get('/locations'),
}

export const useLocations = () =>
  useQuery({
    queryKey: ['locations'],
    queryFn: () => locationApi.list().then((r) => r.data),
  })
