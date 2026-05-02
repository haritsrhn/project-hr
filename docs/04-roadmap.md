# 04 — Product Roadmap

> Roadmap ini bersifat living document — diperbarui setiap sprint review atau sesi perencanaan.

---

## Overview 3 Fase

| Fase | Nama | Estimasi | Status |
|---|---|---|---|
| Phase 1 | MVP — Core HR | Bulan 1–3 | Perencanaan |
| Phase 2 | Expansion | Bulan 4–6 | Belum dimulai |
| Phase 3 | SaaS Ready | Bulan 7+ | Belum dimulai |

---

## Phase 1 — MVP v1.0 (Bulan 1–3)

### M1: Master Data Karyawan
- [ ] CRUD profil karyawan (data personal, kontak darurat)
- [ ] Manajemen dokumen (upload KTP, NPWP, Ijazah, SK)
- [ ] Multi-employment per karyawan (dual role lintas entitas)
- [ ] Status jabatan & riwayat perubahan posisi
- [ ] Import bulk karyawan via CSV/Excel

### M2: Absensi & Cuti Digital
- [ ] GPS Geofencing check-in/check-out (web + PWA mobile)
- [ ] QR Code statis per lokasi (time-based rotation)
- [ ] Device fingerprint validation (anti-titip absen)
- [ ] Pengajuan cuti self-service oleh karyawan
- [ ] Workflow approval cuti (Karyawan → Manager → HRD)
- [ ] Manajemen saldo cuti tahunan per karyawan
- [ ] Rekap kehadiran bulanan (laporan HRD)

### M3: Payroll Otomatis
- [ ] Konfigurasi komponen gaji per karyawan
- [ ] Kalkulasi PPh 21 otomatis (metode gross-up & non gross-up)
- [ ] Kalkulasi BPJS Kesehatan (4%/1% employer/employee)
- [ ] Kalkulasi BPJS Ketenagakerjaan (JHT, JKK, JKM, JP)
- [ ] Proses payroll per entitas per bulan
- [ ] Generate slip gaji PDF per karyawan
- [ ] Export laporan payroll (Excel/PDF) untuk pelaporan pajak

### M4: RBAC & Sistem Akses
- [ ] 5 level role: super_admin, holding_admin, entity_admin, manager, employee
- [ ] Permission matrix per role
- [ ] Isolasi data antar entitas (entity_admin hanya lihat entitas sendiri)
- [ ] Audit log setiap aksi sensitif (login, edit payroll, approve cuti)

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
| P1 | User Story detail per modul (M1–M4) | Sesi berikutnya |
| P2 | Desain UI/UX (wireframe utama) | Setelah user story selesai |
| P3 | Pilihan final AWS vs GCP | Sebelum setup infrastructure |
| P4 | Skema penggajian Tridaya (komponen gaji aktual) | Sebelum build M3 |
