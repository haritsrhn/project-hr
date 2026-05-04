# HRIS Tridaya Sejahtera Group

Internal Human Resource Information System untuk Tridaya Sejahtera Group (Holding) — mengelola data karyawan, absensi GPS, cuti, dan payroll otomatis untuk 500–2.000+ karyawan lintas multi-entitas.

## Status

**Phase 1 MVP selesai** — Seluruh milestone Phase 1 sudah berjalan di `main`.

| Milestone | Status |
|---|---|
| #1 Auth: Models, Migrations & LoginController | ✅ Done |
| #2 RBAC: RoleMiddleware & Route Protection | ✅ Done |
| #3 Auth: Login Page & Route Guard | ✅ Done |
| #4 Master Data: Entity & Employee CRUD | ✅ Done |
| #5 Master Data: Dashboard Layout & Employee Pages | ✅ Done |
| #6 Absensi: Clock-in/out & Haversine Geofencing | ✅ Done |
| #7 Absensi: GPS Attendance Frontend | ✅ Done |
| #8 Payroll: Calculator, Jobs & PDF Slip | ✅ Done |
| #9 Payroll: Runs, Kalkulasi & Slip Frontend | ✅ Done |
| #10 M1: Manajemen Dokumen Karyawan (API + UI) | ✅ Done |
| #11 M2: Rekap Kehadiran Bulanan (BE + FE) | ✅ Done |
| #12 M3: Export Laporan Payroll (CSV) | ✅ Done |
| #13 M4: Audit Log via spatie/laravel-activitylog | ✅ Done |

## Stack

| Layer | Teknologi |
|---|---|
| Backend | Laravel 13 (PHP 8.3) |
| Frontend | Next.js 16 (React 19, TypeScript 5) |
| Database | PostgreSQL 16 |
| Queue | Redis + Laravel Horizon |
| Storage | Local disk (dev) / AWS S3 (prod) |
| Auth | Laravel Sanctum (token-based) |

## Quick Start

```bash
# 1. Salin environment files
cp .env.example .env
cp backend/.env.example backend/.env

# 2. Jalankan Docker (backend + db + redis)
docker compose up -d

# 3. Setup database
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed

# 4. Jalankan frontend
cd frontend && npm install && npm run dev
```

| Service | URL |
|---|---|
| Frontend (Next.js) | http://localhost:3000 |
| Backend API (Laravel) | http://localhost:8000 |
| PostgreSQL | localhost:5432 |
| Redis | localhost:6379 |

Lihat [docs/07-setup-guide.md](docs/07-setup-guide.md) untuk panduan lengkap.

## Dokumentasi

| Dokumen | Deskripsi |
|---|---|
| [01 — Product Vision](docs/01-product-vision.md) | Visi produk, konteks bisnis, scope MVP |
| [02 — Architecture Decisions](docs/02-architecture-decisions.md) | ADR: keputusan teknis dan alasannya |
| [03 — Data Model](docs/03-data-model.md) | Skema database, ERD, catatan desain |
| [04 — Roadmap](docs/04-roadmap.md) | Roadmap 3 fase, checklist fitur |
| [05 — User Story Map](docs/05-user-story-map.md) | User stories per persona, acceptance criteria |
| [06 — Wireframe Planning](docs/06-wireframe-planning.md) | ASCII wireframe 6 flow utama |
| [07 — Setup Guide](docs/07-setup-guide.md) | Cara menjalankan proyek (Docker + Next.js) |
