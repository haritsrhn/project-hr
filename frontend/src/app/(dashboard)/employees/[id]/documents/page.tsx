'use client'

import { use, useRef, useState } from 'react'
import { useRouter } from 'next/navigation'
import { toast } from 'sonner'
import {
  ArrowLeft,
  Upload,
  Download,
  Trash2,
  Loader2,
  FileText,
} from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent } from '@/components/ui/card'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
  DialogClose,
} from '@/components/ui/dialog'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { RoleGate } from '@/components/shared/RoleGate'
import { useEmployee } from '@/lib/api/employees'
import {
  useDocuments,
  useUploadDocument,
  useDeleteDocument,
  downloadDocument,
  DOCUMENT_TYPE_LABELS,
  type DocumentType,
  type EmployeeDocument,
} from '@/lib/api/documents'
import { formatDate } from '@/lib/utils/format'

const DOCUMENT_TYPES = Object.entries(DOCUMENT_TYPE_LABELS) as [DocumentType, string][]

const ALLOWED_MIME_TYPES = ['application/pdf', 'image/jpeg', 'image/png']
const MAX_FILE_SIZE_BYTES = 5 * 1024 * 1024 // 5 MB

const WRITE_ROLES = ['entity_admin', 'holding_admin', 'super_admin', 'manager'] as const

// ─── Upload Dialog ────────────────────────────────────────────────────────────

interface UploadDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  employmentId: string
}

function UploadDialog({ open, onOpenChange, employmentId }: UploadDialogProps) {
  const fileRef = useRef<HTMLInputElement>(null)
  const [docType, setDocType] = useState<DocumentType | ''>('')
  const [label, setLabel] = useState('')
  const [expiresAt, setExpiresAt] = useState('')
  const [fileError, setFileError] = useState('')

  const upload = useUploadDocument(employmentId)

  const resetForm = () => {
    setDocType('')
    setLabel('')
    setExpiresAt('')
    setFileError('')
    if (fileRef.current) fileRef.current.value = ''
  }

  const handleClose = (nextOpen: boolean) => {
    if (!nextOpen) resetForm()
    onOpenChange(nextOpen)
  }

  const validateFile = (file: File): string => {
    if (!ALLOWED_MIME_TYPES.includes(file.type)) {
      return 'Format tidak didukung. Gunakan PDF, JPG, atau PNG.'
    }
    if (file.size > MAX_FILE_SIZE_BYTES) {
      return 'Ukuran file melebihi batas 5 MB.'
    }
    return ''
  }

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (!file) return setFileError('')
    setFileError(validateFile(file))
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()

    if (!docType) {
      toast.error('Pilih tipe dokumen terlebih dahulu.')
      return
    }

    const file = fileRef.current?.files?.[0]
    if (!file) {
      toast.error('Pilih file untuk diunggah.')
      return
    }

    const validationError = validateFile(file)
    if (validationError) {
      setFileError(validationError)
      return
    }

    const formData = new FormData()
    formData.append('type', docType)
    formData.append('file', file)
    if (label.trim()) formData.append('label', label.trim())
    if (expiresAt) formData.append('expires_at', expiresAt)

    try {
      await upload.mutateAsync(formData)
      toast.success('Dokumen berhasil diunggah.')
      handleClose(false)
    } catch {
      toast.error('Gagal mengunggah dokumen. Coba lagi.')
    }
  }

  return (
    <Dialog open={open} onOpenChange={handleClose}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Upload Dokumen</DialogTitle>
        </DialogHeader>

        <form onSubmit={handleSubmit} className="space-y-4">
          {/* Tipe Dokumen */}
          <div className="space-y-1.5">
            <Label htmlFor="doc-type">
              Tipe Dokumen <span className="text-red-500">*</span>
            </Label>
            <Select
              value={docType}
              onValueChange={(v) => setDocType(v as DocumentType)}
            >
              <SelectTrigger id="doc-type" aria-required="true">
                <SelectValue placeholder="Pilih tipe..." />
              </SelectTrigger>
              <SelectContent>
                {DOCUMENT_TYPES.map(([value, label]) => (
                  <SelectItem key={value} value={value}>
                    {label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          {/* Label */}
          <div className="space-y-1.5">
            <Label htmlFor="doc-label">Label (opsional)</Label>
            <Input
              id="doc-label"
              placeholder="Mis. KTP Pusat, Ijazah S1..."
              value={label}
              onChange={(e) => setLabel(e.target.value)}
            />
          </div>

          {/* Kadaluarsa */}
          <div className="space-y-1.5">
            <Label htmlFor="doc-expires">Tanggal Kadaluarsa (opsional)</Label>
            <Input
              id="doc-expires"
              type="date"
              value={expiresAt}
              onChange={(e) => setExpiresAt(e.target.value)}
            />
          </div>

          {/* File */}
          <div className="space-y-1.5">
            <Label htmlFor="doc-file">
              File <span className="text-red-500">*</span>
            </Label>
            <Input
              id="doc-file"
              type="file"
              accept=".pdf,.jpg,.jpeg,.png"
              ref={fileRef}
              onChange={handleFileChange}
              aria-describedby={fileError ? 'file-error' : undefined}
            />
            {fileError ? (
              <p id="file-error" role="alert" className="text-xs text-red-500">
                {fileError}
              </p>
            ) : (
              <p className="text-xs text-gray-500">PDF, JPG, PNG — maks. 5 MB</p>
            )}
          </div>

          <DialogFooter>
            <DialogClose
              disabled={upload.isPending}
              render={
                <Button type="button" variant="outline" disabled={upload.isPending}>
                  Batal
                </Button>
              }
            />
            <Button type="submit" disabled={upload.isPending || !!fileError}>
              {upload.isPending ? (
                <>
                  <Loader2 className="h-4 w-4 mr-2 animate-spin" aria-hidden="true" />
                  Mengunggah...
                </>
              ) : (
                <>
                  <Upload className="h-4 w-4 mr-2" aria-hidden="true" />
                  Upload
                </>
              )}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}

// ─── Delete Confirm Dialog ────────────────────────────────────────────────────

interface DeleteConfirmDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  document: EmployeeDocument | null
  employmentId: string
}

function DeleteConfirmDialog({
  open,
  onOpenChange,
  document,
  employmentId,
}: DeleteConfirmDialogProps) {
  const deleteDoc = useDeleteDocument(employmentId)

  const handleDelete = async () => {
    if (!document) return
    try {
      await deleteDoc.mutateAsync(document.id)
      toast.success('Dokumen berhasil dihapus.')
      onOpenChange(false)
    } catch {
      toast.error('Gagal menghapus dokumen. Coba lagi.')
    }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Hapus Dokumen</DialogTitle>
        </DialogHeader>
        <p className="text-sm text-gray-600">
          Apakah Anda yakin ingin menghapus dokumen{' '}
          <span className="font-medium text-gray-900">
            {document?.label || (document ? DOCUMENT_TYPE_LABELS[document.type] : '')}
          </span>
          ? Tindakan ini tidak dapat dibatalkan.
        </p>
        <DialogFooter>
          <DialogClose
            disabled={deleteDoc.isPending}
            render={
              <Button variant="outline" disabled={deleteDoc.isPending}>
                Batal
              </Button>
            }
          />
          <Button
            variant="destructive"
            onClick={handleDelete}
            disabled={deleteDoc.isPending}
          >
            {deleteDoc.isPending ? (
              <>
                <Loader2 className="h-4 w-4 mr-2 animate-spin" aria-hidden="true" />
                Menghapus...
              </>
            ) : (
              <>
                <Trash2 className="h-4 w-4 mr-2" aria-hidden="true" />
                Hapus
              </>
            )}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}

// ─── Main Page ────────────────────────────────────────────────────────────────

export default function EmployeeDocumentsPage({
  params,
}: {
  params: Promise<{ id: string }>
}) {
  const { id } = use(params)
  const router = useRouter()

  const [uploadOpen, setUploadOpen] = useState(false)
  const [deleteTarget, setDeleteTarget] = useState<EmployeeDocument | null>(null)
  const [downloadingId, setDownloadingId] = useState<string | null>(null)

  // Fetch employee to get employment ID
  const { data: employeeData, isPending: empPending, isError: empError } = useEmployee(id)
  const employee = employeeData?.data

  const primaryEmployment =
    employee?.employments?.find((e: { isPrimary: boolean }) => e.isPrimary) ??
    employee?.employments?.[0]
  const employmentId: string | undefined = primaryEmployment?.id

  // Fetch documents once we have the employment ID
  const {
    data: documentsData,
    isPending: docsPending,
    isError: docsError,
  } = useDocuments(employmentId)

  const documents: EmployeeDocument[] = documentsData?.data ?? []

  const handleDownload = async (doc: EmployeeDocument) => {
    if (!employmentId) return
    setDownloadingId(doc.id)
    try {
      await downloadDocument(
        employmentId,
        doc.id,
        doc.label || DOCUMENT_TYPE_LABELS[doc.type]
      )
    } catch {
      toast.error('Gagal mengunduh dokumen. Coba lagi.')
    } finally {
      setDownloadingId(null)
    }
  }

  // ── Loading employee ──
  if (empPending) {
    return (
      <div className="space-y-4">
        <div className="h-8 w-48 bg-gray-100 rounded animate-pulse" />
        <div className="h-48 bg-gray-100 rounded animate-pulse" />
      </div>
    )
  }

  if (empError || !employee) {
    return (
      <div className="text-center text-gray-500 py-12">
        <p>Karyawan tidak ditemukan.</p>
        <Button variant="ghost" className="mt-4" onClick={() => router.back()}>
          Kembali
        </Button>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center gap-4">
        <Button variant="ghost" size="sm" onClick={() => router.back()}>
          <ArrowLeft className="h-4 w-4 mr-2" aria-hidden="true" />
          Kembali
        </Button>
        <div className="flex-1">
          <h1 className="text-2xl font-bold text-gray-900">Dokumen Karyawan</h1>
          <p className="text-sm text-gray-500">{employee.user?.name}</p>
        </div>
        <RoleGate allowedRoles={[...WRITE_ROLES]}>
          <Button size="sm" onClick={() => setUploadOpen(true)}>
            <Upload className="h-4 w-4 mr-2" aria-hidden="true" />
            Upload Dokumen
          </Button>
        </RoleGate>
      </div>

      {/* Document list */}
      {docsPending && (
        <div className="space-y-2">
          {[...Array(4)].map((_, i) => (
            <div key={i} className="h-14 bg-gray-100 rounded-md animate-pulse" />
          ))}
        </div>
      )}

      {docsError && (
        <Card>
          <CardContent className="py-8 text-center text-gray-500">
            Gagal memuat daftar dokumen.
          </CardContent>
        </Card>
      )}

      {!docsPending && !docsError && (
        <div className="bg-white rounded-lg border overflow-hidden">
          {documents.length === 0 ? (
            <div className="py-12 text-center text-gray-500">
              <FileText className="h-8 w-8 mx-auto mb-2 text-gray-300" aria-hidden="true" />
              Belum ada dokumen untuk karyawan ini.
            </div>
          ) : (
            <table className="w-full text-sm">
              <thead className="bg-gray-50 border-b">
                <tr>
                  <th scope="col" className="px-4 py-3 text-left font-medium text-gray-600">
                    Tipe
                  </th>
                  <th scope="col" className="px-4 py-3 text-left font-medium text-gray-600">
                    Label
                  </th>
                  <th scope="col" className="px-4 py-3 text-left font-medium text-gray-600">
                    Kadaluarsa
                  </th>
                  <th scope="col" className="px-4 py-3 text-left font-medium text-gray-600">
                    Di-upload oleh
                  </th>
                  <th scope="col" className="px-4 py-3 text-left font-medium text-gray-600">
                    Tanggal Upload
                  </th>
                  <th scope="col" className="px-4 py-3" />
                </tr>
              </thead>
              <tbody className="divide-y">
                {documents.map((doc) => (
                  <tr key={doc.id} className="hover:bg-gray-50">
                    <td className="px-4 py-3">
                      <Badge variant="secondary">
                        {DOCUMENT_TYPE_LABELS[doc.type] ?? doc.type}
                      </Badge>
                    </td>
                    <td className="px-4 py-3 text-gray-700">{doc.label || '—'}</td>
                    <td className="px-4 py-3 text-gray-700">
                      {doc.expiresAt ? formatDate(doc.expiresAt) : '—'}
                    </td>
                    <td className="px-4 py-3 text-gray-700">
                      {doc.uploadedBy?.name ?? '—'}
                    </td>
                    <td className="px-4 py-3 text-gray-700">{formatDate(doc.createdAt)}</td>
                    <td className="px-4 py-3">
                      <div className="flex items-center justify-end gap-2">
                        {/* Download — available to all authenticated users */}
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => handleDownload(doc)}
                          disabled={downloadingId === doc.id}
                          aria-label={`Unduh ${doc.label || DOCUMENT_TYPE_LABELS[doc.type]}`}
                        >
                          {downloadingId === doc.id ? (
                            <Loader2 className="h-4 w-4 animate-spin" aria-hidden="true" />
                          ) : (
                            <Download className="h-4 w-4" aria-hidden="true" />
                          )}
                        </Button>

                        {/* Delete — admin/manager only */}
                        <RoleGate allowedRoles={[...WRITE_ROLES]}>
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => setDeleteTarget(doc)}
                            aria-label={`Hapus ${doc.label || DOCUMENT_TYPE_LABELS[doc.type]}`}
                            className="text-red-500 hover:text-red-700 hover:bg-red-50"
                          >
                            <Trash2 className="h-4 w-4" aria-hidden="true" />
                          </Button>
                        </RoleGate>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>
      )}

      {/* Dialogs */}
      {employmentId && (
        <UploadDialog
          open={uploadOpen}
          onOpenChange={setUploadOpen}
          employmentId={employmentId}
        />
      )}

      <DeleteConfirmDialog
        open={!!deleteTarget}
        onOpenChange={(open) => { if (!open) setDeleteTarget(null) }}
        document={deleteTarget}
        employmentId={employmentId ?? ''}
      />
    </div>
  )
}
