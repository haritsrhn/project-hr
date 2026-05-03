# HRIS Tridaya — Backend (Laravel 13)

REST API backend untuk HRIS Tridaya Sejahtera Group. Dibangun dengan Laravel 13 / PHP 8.3, PostgreSQL, Redis, dan Laravel Horizon.

## Struktur Direktori

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/        # Auth, Entity, Employee, Attendance, Leave, Payroll, Location
│   │   ├── Middleware/         # CheckRole, EntityScope
│   │   ├── Requests/           # Form request validation
│   │   └── Resources/          # API resource transformers
│   ├── Jobs/                   # ProcessPayrollJob, GeneratePayslipJob
│   ├── Models/                 # User, Entity, Employment, Attendance, PayrollRun, PayrollItem, ...
│   └── Services/               # PayrollCalculatorService (PPh21 + BPJS)
├── database/
│   ├── migrations/             # 9 migration files
│   └── seeders/                # RolePermissionSeeder (5 roles, permission matrix)
└── routes/
    └── api.php                 # Semua API routes (auth, entities, employees, attendance, leave, payroll, locations)
```

## API Routes

| Method | Endpoint | Permission |
|---|---|---|
| POST | `/api/auth/login` | Public |
| POST | `/api/auth/logout` | auth |
| GET | `/api/auth/me` | auth |
| GET/POST | `/api/entities` | entities.view / super_admin |
| GET/PUT/DELETE | `/api/entities/{entity}` | entities.view / super_admin |
| GET/POST | `/api/employees` | employees.view / employees.create |
| GET/PUT/DELETE | `/api/employees/{user}` | employees.view / employees.update / employees.delete |
| POST | `/api/attendance/clock-in` | attendance.clock_in |
| POST | `/api/attendance/clock-out` | attendance.clock_in |
| GET | `/api/attendance/today` | attendance.view_own |
| GET | `/api/attendance` | attendance.view_own |
| PUT | `/api/attendance/{attendance}/correct` | attendance.correct |
| GET/POST | `/api/leave/requests` | leave.view_own / leave.request |
| PUT | `/api/leave/requests/{id}/approve` | leave.approve |
| GET/POST | `/api/payroll/runs` | payroll.view_all / payroll.process |
| POST | `/api/payroll/runs/{run}/process` | payroll.process |
| POST | `/api/payroll/runs/{run}/lock` | payroll.lock |
| GET | `/api/payroll/runs/{run}/items` | payroll.view_all |
| GET | `/api/payroll/items/{item}/slip` | payroll.view_own_slip |

## Roles & Permissions

| Role | Scope |
|---|---|
| `super_admin` | Full access semua entitas |
| `holding_admin` | Baca data semua entitas |
| `entity_admin` | Full access 1 entitas |
| `manager` | Approve cuti, lihat absensi tim |
| `employee` | Clock-in/out, ajukan cuti, lihat slip sendiri |

## Menjalankan Lokal

```bash
# Dari root project
docker compose up -d
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed

# Jalankan queue worker
docker compose exec app php artisan horizon
```

## Testing

```bash
docker compose exec app php artisan test
```
