# 02 — Architecture Decisions (ADR)

> Architecture Decision Records — mencatat setiap keputusan arsitektur, alasannya, dan konsekuensinya.

---

## ADR-001: Struktur Multi-Entitas (Dual Employment)

**Keputusan:** Satu user dapat memiliki lebih dari satu employment record di entitas berbeda, dalam satu akun profil utama.

**Konteks:** Tridaya Group memiliki karyawan yang berperan di dua entitas sekaligus (contoh: Manager di PT A sekaligus Penasihat di Yayasan B).

**Konsekuensi:**
- Model data: pisahkan tabel `users` (identitas personal) dari tabel `employments` (hubungan kerja).
- Semua transaksi (absensi, payroll, cuti) terikat ke `employment_id`, bukan `user_id` langsung.
- Field `is_primary` di tabel `employments` menandai entitas utama karyawan.

---

## ADR-002: Payroll Per Entitas, Laporan Terkonsolidasi

**Keputusan:** Payroll diproses dan disimpan secara terpisah per entitas (NPWP & nomor rekening bank berbeda). Konsolidasi hanya terjadi di lapisan pelaporan eksekutif.

**Konteks:** Tiap entitas (PT/CV/Yayasan) adalah wajib pajak berbeda dengan kewajiban pelaporan PPh 21 dan BPJS masing-masing.

**Konsekuensi:**
- Tabel `payroll_runs` selalu punya kolom `entity_id`.
- Dashboard eksekutif mengagregasi data dari semua entitas via query, bukan dari satu tabel terpusat.

---

## ADR-003: Mekanisme Absensi Ganda (GPS + QR)

**Keputusan:** Kombinasi GPS Geofencing (untuk staf lapangan) dan QR Code statis (untuk kehadiran fisik di kantor).

**Validasi Anti-Manipulasi:**
- Koordinat GPS wajib dicatat setiap clock-in/out.
- Radius geofence dikonfigurasi per lokasi (fleksibel per entitas).
- Device fingerprint dicatat untuk mencegah titip absen.
- QR Code di-rotate secara periodik (time-based) agar tidak bisa di-screenshot untuk digunakan ulang.

---

## ADR-004: Deployment — Private Cloud

**Keputusan:** Hosted di AWS atau Google Cloud dengan model self-managed (Private Cloud), bukan SaaS publik.

**Alasan:**
- Akses karyawan lintas lokasi (Binjai, Deli Serdang) tanpa hardware fisik.
- Kontrol penuh atas data sesuai **UU PDP (Perlindungan Data Pribadi) Indonesia**.
- Data karyawan tidak boleh diproses di luar yurisdiksi tanpa consent eksplisit.

**Future:** Arsitektur dirancang agar bisa di-pivot ke multi-tenant SaaS di Phase 3 tanpa refactor besar.

---

## ADR-005: Tech Stack

**Keputusan:**

| Layer | Teknologi | Alasan |
|---|---|---|
| **Backend** | Laravel 12 (PHP) | Ekosistem Indonesia mature, logika PPh 21 + BPJS mudah diimplementasi |
| **Frontend** | Next.js 15 (React) + PWA | Satu codebase web + mobile, tanpa app store, SSR untuk performa |
| **Database** | PostgreSQL | Relasional kompleks, multi-tenant pattern solid |
| **Queue** | Redis + Laravel Horizon | Proses payroll batch async, notifikasi push |
| **File Storage** | AWS S3 / GCS | Dokumen karyawan, slip gaji PDF |
| **Auth** | Laravel Sanctum + JWT | API stateless, multi-device session |
| **Cache** | Redis | Session, rate limiting, data agregat eksekutif |

---

## ADR-006: RBAC — 5 Level Hierarki

```
super_admin
  └── holding_admin       (HRD Holding — lihat semua entitas)
        └── entity_admin  (HRD per entitas — kelola karyawan entitas sendiri)
              └── manager (Approval + laporan divisi)
                    └── employee (Self-service: absensi, cuti, slip gaji)
```

**Prinsip:** Setiap role hanya bisa mengakses data dalam scope-nya. Manager entitas A tidak bisa melihat data entitas B.
