// ─── Auth & RBAC ──────────────────────────────────────────────────────────
export type RoleSlug =
  | 'super_admin'
  | 'holding_admin'
  | 'entity_admin'
  | 'manager'
  | 'employee'

export interface User {
  id: string
  name: string
  email: string
  phone: string
  nationalId: string
  photoUrl: string | null
  roles: RoleSlug[]
  employments: Employment[]
  primaryEmployment: Employment | null
}

// ─── Entity (Holding / PT / CV / Yayasan) ─────────────────────────────────
export type EntityType = 'HOLDING' | 'PT' | 'CV' | 'YAYASAN'

export interface Entity {
  id: string
  name: string
  type: EntityType
  npwp: string
  bankName: string
  bankAccount: string
  bankHolderName: string
  address: string
  phone: string
  parentId: string | null
}

// ─── Employment ───────────────────────────────────────────────────────────
export type EmploymentType = 'PERMANENT' | 'CONTRACT' | 'INTERN'
export type EmploymentStatus = 'ACTIVE' | 'INACTIVE' | 'TERMINATED'

export interface Employment {
  id: string
  userId: string
  entityId: string
  entity: Entity
  employeeNumber: string
  position: string
  department: string
  employmentType: EmploymentType
  salaryBasic: number
  salaryStructure: SalaryComponent[]
  joinDate: string
  endDate: string | null
  isPrimary: boolean
  status: EmploymentStatus
}

export interface SalaryComponent {
  name: string
  amount: number
  type: 'ALLOWANCE' | 'DEDUCTION'
}

// ─── Attendance ───────────────────────────────────────────────────────────
export type AttendanceMethod = 'GPS' | 'QR' | 'MANUAL'
export type AttendanceStatus = 'PRESENT' | 'LATE' | 'ABSENT' | 'LEAVE'

export interface Attendance {
  id: string
  employmentId: string
  date: string
  clockIn: string | null
  clockOut: string | null
  method: AttendanceMethod
  latIn: number | null
  lngIn: number | null
  latOut: number | null
  lngOut: number | null
  deviceHash: string | null
  locationId: string | null
  status: AttendanceStatus
}

// ─── Leave ────────────────────────────────────────────────────────────────
export type LeaveStatus = 'PENDING' | 'APPROVED' | 'REJECTED' | 'CANCELLED'

export interface LeaveType {
  id: string
  entityId: string
  name: string
  maxDaysPerYear: number
  isPaid: boolean
  carryOver: boolean
}

export interface LeaveBalance {
  id: string
  employmentId: string
  leaveTypeId: string
  leaveType: LeaveType
  year: number
  totalDays: number
  usedDays: number
  remainingDays: number
}

export interface LeaveRequest {
  id: string
  employmentId: string
  employment: Employment
  leaveTypeId: string
  leaveType: LeaveType
  startDate: string
  endDate: string
  totalDays: number
  reason: string
  status: LeaveStatus
  approvedBy: string | null
  approvedAt: string | null
  createdAt: string
}

// ─── Payroll ──────────────────────────────────────────────────────────────
export type PayrollRunStatus = 'DRAFT' | 'PROCESSED' | 'PAID'
export type PtkpStatus = 'TK0' | 'TK1' | 'TK2' | 'TK3' | 'K0' | 'K1' | 'K2' | 'K3'

export interface PayrollRun {
  id: string
  entityId: string
  entity: Entity
  periodMonth: number
  periodYear: number
  status: PayrollRunStatus
  processedAt: string | null
  processedBy: string | null
  totalGross: number
  totalNet: number
  totalEmployees: number
}

// Minimal employee snapshot returned inside PayrollItem
export interface PayrollEmployee {
  id: string
  employeeNumber: string
  position: string
  department: string
  user: { id: string; name: string; email: string } | null
}

export interface PayrollItem {
  id: string
  payrollRunId: string
  employmentId: string
  employee: PayrollEmployee | null
  grossSalary: number
  netSalary: number
  allowances: SalaryComponent[]
  deductions: SalaryComponent[]
  // BPJS Kesehatan (employee share)
  bpjsKesEmployee: number
  bpjsKesEmployer: number
  // BPJS TK
  bpjsJhtEmployee: number
  bpjsJhtEmployer: number
  bpjsJkk: number
  bpjsJkm: number
  bpjsJpEmployee: number
  bpjsJpEmployer: number
  // PPh 21
  pph21AnnualBase: number
  pph21Amount: number
  pph21Breakdown: Array<{ bracket: string; taxable: number; rate: number; tax: number }>
  // Attendance
  workingDays: number
  presentDays: number
  absentDays: number
  leaveDays: number
  slipUrl: string | null
}

// ─── Location (Geofence) ──────────────────────────────────────────────────
export interface Location {
  id: string
  entityId: string
  name: string
  address: string
  latitude: number
  longitude: number
  radiusMeters: number
  qrCodeToken: string
  qrRotatedAt: string
}

// ─── API Response Wrapper ─────────────────────────────────────────────────
export interface ApiResponse<T> {
  data: T
  message: string
  status: number
}

export interface PaginatedResponse<T> {
  data: T[]
  meta: {
    currentPage: number
    lastPage: number
    perPage: number
    total: number
  }
}
