# 01 — Product Vision & Scope

> Dokumen ini dibuat pada sesi perencanaan awal bersama AI Product Manager.
> Diperbarui setiap ada keputusan baru yang disepakati.

---

## Vision Statement

> *"Satu platform terpusat yang mengkonsolidasikan seluruh data SDM lintas entitas Tridaya Group — dari absensi harian hingga payroll yang patuh regulasi — menggantikan fragmentasi Excel dan grup chat."*

---

## Konteks Bisnis

| Atribut | Detail |
|---|---|
| **Klien / Pemilik** | Tridaya Sejahtera Group (Holding) |
| **Jenis Entitas** | Multi-entitas: PT, CV, Yayasan |
| **Skala Awal** | ~500 karyawan |
| **Target Skala** | 2.000 karyawan |
| **Sistem Lama** | Semi-manual: Excel + koordinasi via grup chat |
| **Lokasi Operasional** | Binjai, Deli Serdang (Sumatera Utara) |

---

## Pain Points yang Diselesaikan

1. **Fragmentasi Data** — Data SDM tersebar di tiap entitas, tidak ada sumber kebenaran tunggal (single source of truth).
2. **Payroll Rawan Error** — Kalkulasi PPh 21 dan BPJS dilakukan manual, rentan human error.
3. **Absensi & Cuti Tidak Real-time** — Pemantauan kehadiran sulit dilakukan lintas lokasi.
4. **Tidak Ada Visibilitas Eksekutif** — Pimpinan tidak punya dashboard agregat untuk melihat total pengeluaran SDM seluruh grup.

---

## Pengguna (User Personas)

| Role | Aktivitas Utama |
|---|---|
| **HRD / Admin** | Kelola data karyawan, proses payroll, approve cuti |
| **Karyawan** | Absensi, ajukan cuti, lihat slip gaji |
| **Manajer / Pimpinan Unit** | Approve pengajuan, lihat laporan divisi |
| **Eksekutif (Holding)** | Monitor dashboard agregat seluruh grup |
| **Super Admin (IT)** | Konfigurasi sistem, kelola entitas dan akses |

---

## Scope MVP v1.0

### Modul yang Masuk

| # | Modul | Deskripsi Singkat |
|---|---|---|
| M1 | **Master Data Karyawan** | Profil, dokumen, status jabatan, dual employment |
| M2 | **Absensi & Cuti Digital** | GPS geofencing + QR code, self-service karyawan |
| M3 | **Payroll Otomatis** | PPh 21 & BPJS sesuai regulasi Indonesia, slip gaji PDF |
| M4 | **RBAC & Multi-Entitas** | Role-based access, struktur holding > entitas > divisi |

### Modul yang TIDAK Masuk v1.0 (Backlog)

- Recruitment Pipeline
- Performance Review / KPI
- Training Management
- Asset Management
- Analytics Dashboard lanjutan
- Multi-tenant SaaS (Phase 3)

---

## Roadmap Fase

```
Phase 1 — MVP (Bulan 1–3)
  M1: Master Data Karyawan
  M2: Absensi GPS + QR
  M3: Payroll PPh21 + BPJS
  M4: RBAC 5 Level
  + Slip Gaji PDF
  + Dashboard Eksekutif (agregat grup)

Phase 2 — Expansion (Bulan 4–6)
  Recruitment Pipeline
  Performance Review
  Training Management
  Asset Management
  Analytics Dashboard

Phase 3 — SaaS Ready (Bulan 7+)
  Multi-tenant billing
  Subscription management
  White-label per klien
  API publik
```
