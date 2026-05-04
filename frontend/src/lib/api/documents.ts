import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import apiClient from './client'

export type DocumentType =
  | 'KTP'
  | 'NPWP'
  | 'IJAZAH'
  | 'SK_PENGANGKATAN'
  | 'KONTRAK'
  | 'SERTIFIKAT'
  | 'LAINNYA'

export interface EmployeeDocument {
  id: string
  type: DocumentType
  label: string | null
  expiresAt: string | null
  uploadedBy: { id: string; name: string }
  createdAt: string
}

export const DOCUMENT_TYPE_LABELS: Record<DocumentType, string> = {
  KTP: 'KTP',
  NPWP: 'NPWP',
  IJAZAH: 'Ijazah',
  SK_PENGANGKATAN: 'SK Pengangkatan',
  KONTRAK: 'Kontrak',
  SERTIFIKAT: 'Sertifikat',
  LAINNYA: 'Lainnya',
}

// ─── Raw API calls ────────────────────────────────────────────────────────────

export const getDocuments = (employmentId: string) =>
  apiClient.get(`/employees/${employmentId}/documents`)

export const uploadDocument = (employmentId: string, formData: FormData) =>
  apiClient.post(`/employees/${employmentId}/documents`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })

export const downloadDocument = async (
  employmentId: string,
  documentId: string,
  label: string
) => {
  const response = await apiClient.get(
    `/employees/${employmentId}/documents/${documentId}/download`,
    { responseType: 'blob' }
  )
  const url = URL.createObjectURL(new Blob([response.data]))
  const a = document.createElement('a')
  a.href = url
  a.download = label
  a.click()
  URL.revokeObjectURL(url)
}

export const deleteDocument = (employmentId: string, documentId: string) =>
  apiClient.delete(`/employees/${employmentId}/documents/${documentId}`)

// ─── TanStack Query hooks ─────────────────────────────────────────────────────

export const useDocuments = (employmentId: string | undefined) =>
  useQuery({
    queryKey: ['documents', employmentId],
    queryFn: () => getDocuments(employmentId!).then((r) => r.data),
    enabled: !!employmentId,
  })

export const useUploadDocument = (employmentId: string | undefined) => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (formData: FormData) => uploadDocument(employmentId!, formData),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['documents', employmentId] }),
  })
}

export const useDeleteDocument = (employmentId: string | undefined) => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (documentId: string) => deleteDocument(employmentId!, documentId),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['documents', employmentId] }),
  })
}
