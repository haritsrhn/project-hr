# 04 — Product Roadmap

> Roadmap ini bersifat living document — diperbarui setiap sprint review atau sesi perencanaan.

---

## Overview 3 Fase

| Fase | Nama | Estimasi | Status |
|---|---|---|---|
| Phase 1 | MVP — Core HR | Bulan 1–3 | ✅ Done |
| Phase 2 | Expansion | Bulan 4–6 | Belum dimulai |
| Phase 3 | SaaS Ready | Bulan 7+ | Belum dimulai |

---

## Phase 1 — MVP v1.0 (Bulan 1–3)

### M1: Master Data Karyawan
- [x] CRUD profil karyawan (data personal, kontak darurat)
- [x] Manajemen dokumen (upload KTP, NPWP, Ijazah, SK) — PR #22 & #23
- [x] Multi-employment per karyawan (dual role lintas entitas)
- [x] Status jabatan & riwayat perubahan posisi
- [x] Import bulk karyawan via CSV/Excel

### M2: Absensi & Cuti Digital
- [x] GPS Geofencing check-in/check-out (web + PWA mobile)
- [x] QR Code statis per lokasi (time-based rotation)
- [x] Device fingerprint validation (anti-titip absen)
- [x] Pengajuan cuti self-service oleh karyawan
- [x] Workflow approval cuti (Karyawan → Manager → HRD)
- [x] Manajemen saldo cuti tahunan per karyawan
- [x] Rekap kehadiran bulanan (laporan HRD) — PR #20

### M3: Payroll Otomatis
- [x] Konfigurasi komponen gaji per karyawan
- [x] Kalkulasi PPh 21 otomatis (UU HPP 2021, PMK 168/2023)
- [x] Kalkulasi BPJS Kesehatan (4%/1% employer/employee)
- [x] Kalkulasi BPJS Ketenagakerjaan (JHT, JKK, JKM, JP)
- [x] Proses payroll per entitas per bulan (async via Redis queue)
- [x] Generate slip gaji PDF per karyawan
- [x] Export laporan payroll (CSV/Excel) untuk pelaporan pajak — PR #19

### M4: RBAC & Sistem Akses
- [x] 5 level role: super_admin, holding_admin, entity_admin, manager, employee
- [x] Permission matrix per role
- [x] Isolasi data antar entitas (entity_admin hanya lihat entitas sendiri)
- [x] Audit log setiap aksi sensitif (login, edit payroll, approve cuti) — PR #21

### Infrastructure MVP
- [ ] Setup environment AWS/GCP (staging + production)
- [ ] CI/CD pipeline (GitHub Actions)
- [ ] Database backup otomatis harian
- [ ] HTTPS + SSL certificate
- [ ] Basic monitoring (uptime + error alerting)

---

## Phase 2 — Expansion (Bulan 4–6)

- [ ] **Recruitment Pipeline** — Job posting, lamaran, screening, onboarding
- [ ] **Performance Review** — Template KPI, siklus review, penilaian 360°
- [ ] **Training Management** — Jadwal training, sertifikasi, riwayat pelatihan
- [ ] **Asset Management** — Inventaris aset yang dipinjamkan ke karyawan
- [ ] **Advanced Analytics** — Dashboard eksekutif dengan chart interaktif, trend SDM

---

## Phase 3 — SaaS Ready (Bulan 7+)

- [ ] **Multi-tenant architecture** — Isolasi data antar klien SaaS
- [ ] **Subscription billing** — Integrasi payment gateway, manajemen paket
- [ ] **White-label** — Custom domain + branding per klien
- [ ] **Public API** — REST API terdokumentasi untuk integrasi pihak ketiga
- [ ] **Mobile App Native** — iOS & Android (jika PWA tidak cukup)

---

## Keputusan yang Masih Pending

| # | Pertanyaan | Target Keputusan |
|---|---|---|
| P3 | Pilihan final AWS vs GCP | Sebelum setup infrastructure |
| P4 | Skema penggajian Tridaya (komponen gaji aktual) | Sebelum payroll production |
| ~~P5~~ | ~~Export laporan payroll format final (Excel/PDF)~~ | ✅ Resolved — CSV streaming (PR #19) |
| ~~P6~~ | ~~Audit log — spatie/laravel-activitylog perlu di-wire~~ | ✅ Resolved — fully wired (PR #21) |
