# 03 — Data Model (Entity Relationship)

> Desain skema database awal untuk MVP v1.0.
> Semua tabel menggunakan UUID sebagai primary key untuk keamanan dan portabilitas.

---

## Diagram Relasi Utama

```
┌──────────────┐       ┌────────────────────┐       ┌──────────────────┐
│    users     │ 1───N │    employments      │ N───1 │     entities     │
│──────────────│       │────────────────────│       │──────────────────│
│ id (UUID)    │       │ id (UUID)          │       │ id (UUID)        │
│ name         │       │ user_id (FK)       │       │ name             │
│ email        │       │ entity_id (FK)     │       │ type (enum)      │
│ phone        │       │ employee_number    │       │   PT/CV/YAYASAN  │
│ national_id  │       │ position           │       │ npwp             │
│ birth_date   │       │ department         │       │ bank_name        │
│ gender       │       │ employment_type    │       │ bank_account     │
│ address      │       │   (enum)           │       │ bank_holder_name │
│ photo_url    │       │   PERMANENT/       │       │ address          │
│ password     │       │   CONTRACT/INTERN  │       │ phone            │
│ created_at   │       │ salary_basic       │       │ parent_id (FK)   │ ← holding
│ updated_at   │       │ salary_structure   │         │ created_at       │
└──────────────┘       │   (JSON)           │       │ updated_at       │
                       │ join_date          │       └──────────────────┘
                       │ end_date (null)    │
                       │ is_primary (bool)  │
                       │ status (enum)      │
                       │   ACTIVE/INACTIVE/ │
                       │   TERMINATED       │
                       │ created_at         │
                       │ updated_at         │
                       └────────────────────┘
                                │
              ┌─────────────────┼──────────────────┐
              │                 │                  │
    ┌─────────▼──────┐  ┌───────▼──────┐  ┌───────▼────────┐
    │  attendances   │  │ leave_requests│  │  payroll_runs  │
    │────────────────│  │──────────────│  │────────────────│
    │ id             │  │ id           │  │ id             │
    │ employment_id  │  │ employment_id│  │ entity_id (FK) │
    │ date           │  │ leave_type_id│  │ period_month   │
    │ clock_in       │  │ start_date   │  │ period_year    │
    │ clock_out      │  │ end_date     │  │ status (enum)  │
    │ method (enum)  │  │ reason       │  │   DRAFT/       │
    │   GPS/QR/MANUAL│  │ status (enum)│  │   PROCESSED/   │
    │ lat_in         │  │   PENDING/   │  │   PAID         │
    │ lng_in         │  │   APPROVED/  │  │ processed_at   │
    │ lat_out        │  │   REJECTED   │  │ processed_by   │
    │ lng_out        │  │ approved_by  │  │ created_at     │
    │ device_hash    │  │ approved_at  │  └────────────────┘
    │ location_id    │  │ created_at   │          │
    │ status (enum)  │  └──────────────┘          │
    │   PRESENT/     │                   ┌────────▼────────┐
    │   LATE/ABSENT  │                   │  payroll_items  │
    │ created_at     │                   │─────────────────│
    └────────────────┘                   │ id              │
                                         │ payroll_run_id  │
                                         │ employment_id   │
                                         │ gross_salary    │
                                         │ bpjs_kesehatan  │
                                         │ bpjs_tk_jht     │
                                         │ bpjs_tk_jkk     │
                                         │ bpjs_tk_jkm     │
                                         │ pph21_amount    │
                                         │ deductions (JSON│
                                         │ allowances (JSON│
                                         │ net_salary      │
                                         │ slip_url        │
                                         └─────────────────┘
```

---

## Tabel Pendukung

### `locations` — Lokasi Geofence
```
id, entity_id, name, address, latitude, longitude, radius_meters, qr_code_token, qr_rotated_at
```

### `leave_types` — Jenis Cuti
```
id, entity_id, name, max_days_per_year, is_paid, carry_over (bool)
```
Contoh: Cuti Tahunan (12 hari), Cuti Sakit, Cuti Melahirkan, dll.

### `leave_balances` — Saldo Cuti per Karyawan
```
id, employment_id, leave_type_id, year, total_days, used_days, remaining_days
```

### `roles` & `permissions` — RBAC
```
roles: id, name, slug, scope (HOLDING/ENTITY/DEPARTMENT)
permissions: id, name, slug
role_permissions: role_id, permission_id
user_roles: user_id, role_id, entity_id (nullable)
```

### `documents` — Dokumen Karyawan
```
id, employment_id, type (KTP/NPWP/IJAZAH/SK/dll), file_url, expires_at, uploaded_at
```

---

## Catatan Desain Penting

1. **`salary_structure` di `employments`** disimpan sebagai JSON untuk fleksibilitas komponen gaji (tunjangan jabatan, tunjangan transport, dll) tanpa perlu tabel terpisah.
2. **`deductions` & `allowances` di `payroll_items`** juga JSON — mencatat komponen potongan/tunjangan aktual saat payroll diproses (bukan konfigurasi).
3. **`entities.parent_id`** adalah self-referential foreign key — memungkinkan hierarki Holding > Anak Perusahaan.
4. Semua tabel memiliki `created_at` dan `updated_at`, serta **soft delete** (`deleted_at`) untuk audit trail.
