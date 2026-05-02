# 06 — Wireframe Planning (UI/UX Flow)

> Dokumen ini memetakan alur utama UI per persona sebelum implementasi komponen dimulai.
> Format: Deskripsi layar + elemen kunci + transisi antar halaman.

---

## STRUKTUR NAVIGASI

```
/login                         ← Publik
/forgot-password               ← Publik

/(dashboard)
  /overview                    ← Semua role (konten disesuaikan per role)
  /employees
    /list                      ← holding_admin, entity_admin
    /[id]                      ← HRD + manager (read-only)
  /attendance                  ← Semua role
  /leave
    /requests                  ← Semua role (view sesuai scope)
    /calendar                  ← Manager + HRD
  /payroll
    /runs                      ← entity_admin, holding_admin
    /[id]/slip                 ← Semua role (employee: slip sendiri)
  /settings
    /entities                  ← super_admin only
```

---

## FLOW 1: Login & Autentikasi

```
┌─────────────────────────────────────────────────────┐
│                   HALAMAN LOGIN                     │
│                                                     │
│   Logo Tridaya Group                                │
│                                                     │
│   ┌──────────────────────────────────┐              │
│   │  Email                           │              │
│   └──────────────────────────────────┘              │
│   ┌──────────────────────────────────┐              │
│   │  Password               👁       │              │
│   └──────────────────────────────────┘              │
│                                                     │
│   [        MASUK        ]                           │
│                                                     │
│   Lupa password?                                    │
└─────────────────────────────────────────────────────┘
         │
         ▼ (sukses)
Sistem deteksi role user
         │
    ┌────┴────────────────┐
    │                     │
    ▼                     ▼
Punya >1             Hanya 1
employment?          employment
    │                     │
    ▼                     ▼
Tampil modal         Langsung ke
pilih entitas        /overview
aktif
    │
    ▼
/overview (dengan konteks entitas terpilih)
```

**Elemen Kunci:**
- Error state: "Email atau password salah" (jangan sebut mana yang salah — keamanan)
- Loading state pada tombol masuk
- Redirect setelah login berdasarkan role tertinggi

---

## FLOW 2: Dashboard Overview (Role-Aware)

### Tampilan Eksekutif / Holding Admin
```
┌──────────────────────────────────────────────────────────────┐
│ TRIDAYA HRIS    [Switcher: Semua Entitas ▼]    👤 Nama User  │
├──────────────────────────────────────────────────────────────┤
│ SIDEBAR                    │  KONTEN UTAMA                   │
│ ─────────                  │  ──────────                     │
│ 📊 Overview           ←    │  ┌──────────┐ ┌──────────┐     │
│ 👥 Karyawan                │  │Total     │ │Hadir     │     │
│ ⏰ Absensi                 │  │Karyawan  │ │Hari Ini  │     │
│ 🏖  Cuti                   │  │ 487      │ │ 412 / 487│     │
│ 💰 Payroll                 │  └──────────┘ └──────────┘     │
│ ⚙️  Pengaturan             │  ┌──────────┐ ┌──────────┐     │
│                            │  │Total     │ │Cuti      │     │
│                            │  │Pengeluaran│ │Pending   │     │
│                            │  │SDM/bln   │ │ 8        │     │
│                            │  └──────────┘ └──────────┘     │
│                            │                                 │
│                            │  Distribusi Karyawan per Entitas│
│                            │  ┌────────────────────────┐    │
│                            │  │ PT A  ████████ 210     │    │
│                            │  │ CV B  ████ 87          │    │
│                            │  │ Ysn C ███ 65           │    │
│                            │  └────────────────────────┘    │
└──────────────────────────────────────────────────────────────┘
```

### Tampilan Karyawan (Employee)
```
Overview menampilkan:
- Status kehadiran hari ini (sudah/belum clock-in)
- Saldo cuti tersisa per jenis
- Slip gaji bulan terakhir (shortcut)
- Pengajuan cuti yang sedang pending
```

---

## FLOW 3: Absensi — Clock-In GPS (Karyawan)

```
Karyawan buka halaman Absensi
         │
         ▼
┌─────────────────────────────┐
│      ABSENSI HARI INI       │
│                             │
│  Sabtu, 3 Mei 2025          │
│  PT Tridaya Sejahtera       │
│                             │
│  Status: BELUM CLOCK-IN     │
│                             │
│  📍 Mendeteksi lokasi...    │ ← animasi spinner
│                             │
│  [   CLOCK IN SEKARANG   ]  │ ← disabled sampai GPS ready
│                             │
│  atau                       │
│  [ 📷 Scan QR Code ]        │
└─────────────────────────────┘
         │
    GPS terdeteksi
         │
    ┌────┴────────────────┐
    │                     │
    ▼                     ▼
Dalam radius          Di luar radius
(≤ 100m)             (> 100m)
    │                     │
    ▼                     ▼
Tombol aktif         Tampil warning:
Clock-in             "Anda berada
tersedia             350m dari kantor.
    │                Absensi tidak
    ▼                dapat dilakukan."
┌──────────────┐
│  KONFIRMASI  │
│              │
│ 📍 Kantor    │
│  Binjai      │
│              │
│ 08:47 WIB    │
│              │
│ [  CLOCK IN ]│
└──────────────┘
         │
         ▼
┌─────────────────────────────┐
│  ✅ BERHASIL CLOCK IN       │
│                             │
│  08:47 WIB                  │
│  📍 Kantor Binjai           │
│  (GPS · 23m dari pusat)     │
│                             │
│  Status: HADIR              │
└─────────────────────────────┘
```

---

## FLOW 4: Pengajuan Cuti (Karyawan)

```
/leave/requests
         │
         ▼
┌─────────────────────────────────────────────────────┐
│  CUTI SAYA                    [ + Ajukan Cuti ]     │
│                                                     │
│  Saldo Cuti:                                        │
│  ┌─────────────┐ ┌─────────────┐ ┌──────────────┐  │
│  │Cuti Tahunan │ │ Cuti Sakit  │ │Cuti Khusus   │  │
│  │  8 / 12     │ │ Tidak terbts│ │   2 / 3      │  │
│  └─────────────┘ └─────────────┘ └──────────────┘  │
│                                                     │
│  Riwayat Pengajuan:                                 │
│  ┌─────────────────────────────────────────────┐   │
│  │ Cuti Tahunan  │ 20–22 Mei  │ 3 hari │ PENDING│  │
│  │ Cuti Tahunan  │ 10 Apr     │ 1 hari │ APPROVED│  │
│  └─────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────┘
         │
    Klik [+ Ajukan Cuti]
         │
         ▼
┌─────────────────────────────┐
│  FORM PENGAJUAN CUTI        │
│                             │
│  Jenis Cuti *               │
│  [Cuti Tahunan          ▼]  │
│                             │
│  Tanggal Mulai *            │
│  [📅 Pilih tanggal    ]     │
│                             │
│  Tanggal Selesai *          │
│  [📅 Pilih tanggal    ]     │
│                             │
│  Durasi: 3 hari kerja       │ ← kalkulasi otomatis
│  Sisa setelah cuti: 5 hari  │ ← live update
│                             │
│  Alasan *                   │
│  [                        ] │
│  [                        ] │
│                             │
│  Lampiran (opsional)        │
│  [ 📎 Upload file ]         │
│                             │
│  [Batal]  [Kirim Pengajuan] │
└─────────────────────────────┘
```

---

## FLOW 5: Proses Payroll (HRD/Entity Admin)

```
/payroll/runs
         │
         ▼
┌─────────────────────────────────────────────────────┐
│  PAYROLL RUNS                    [ + Buat Payroll ] │
│  PT Tridaya Sejahtera                               │
│                                                     │
│  ┌──────────────────────────────────────────────┐  │
│  │ April 2025  │ 127 Kary │ Rp 487.2jt │  PAID  │  │
│  │ Maret 2025  │ 125 Kary │ Rp 479.8jt │  PAID  │  │
│  │ Mei 2025    │ 129 Kary │ —          │  DRAFT │→ │
│  └──────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────┘
         │
    Klik Mei 2025 (DRAFT)
         │
         ▼
┌─────────────────────────────────────────────────────┐
│  PAYROLL MEI 2025 — PT TRIDAYA       [DRAFT]        │
│                                                     │
│  129 Karyawan                                       │
│                                                     │
│  [  ⚙️ PROSES KALKULASI  ]  ← trigger batch job     │
│                                                     │
│  Filter: [ Semua Dept ▼ ] [ Semua Status ▼ ]       │
│                                                     │
│  ┌──────────────────────────────────────────────┐  │
│  │ Nama       │ Gross    │ BPJS   │ PPh21 │ Net  │  │
│  │ Budi S.    │ 8.5jt   │ 425rb  │ 0     │ 8.1jt│  │
│  │ Siti R.    │ 12jt    │ 600rb  │ 125rb │ 11.3j│  │
│  │ ...        │ ...     │ ...    │ ...   │ ...  │  │
│  └──────────────────────────────────────────────┘  │
│                                                     │
│  Total Gross: Rp 512.4jt                           │
│  Total Net:   Rp 487.1jt                           │
│                                                     │
│  [  🔒 FINALISASI & KUNCI  ]  ← hanya setelah review│
└─────────────────────────────────────────────────────┘
         │
    Klik Finalisasi
         │
         ▼
Modal konfirmasi:
"Setelah dikunci, payroll tidak dapat diedit.
 Lanjutkan?"
[Batal]  [Ya, Kunci Payroll]
         │
         ▼
Status berubah: PROCESSED
Slip gaji di-generate otomatis untuk semua karyawan
Notifikasi dikirim ke karyawan
```

---

## FLOW 6: Slip Gaji (Karyawan)

```
/payroll/[id]/slip
         │
         ▼
┌─────────────────────────────────────┐
│          SLIP GAJI                  │
│     PT Tridaya Sejahtera            │
│                                     │
│  Nama      : Budi Santoso           │
│  NIK       : 0012                   │
│  Jabatan   : Staff Marketing        │
│  Periode   : Mei 2025               │
│                                     │
│  PENGHASILAN                        │
│  Gaji Pokok          Rp  8.000.000  │
│  Tunjangan Jabatan   Rp    500.000  │
│  Tunjangan Transport Rp    300.000  │
│  ─────────────────────────────────  │
│  Total Bruto         Rp  8.800.000  │
│                                     │
│  POTONGAN                           │
│  BPJS Kesehatan (1%) Rp     88.000  │
│  BPJS JHT (2%)       Rp    176.000  │
│  PPh 21              Rp          0  │
│  ─────────────────────────────────  │
│  Total Potongan      Rp    264.000  │
│                                     │
│  GAJI BERSIH         Rp  8.536.000  │
│                                     │
│         [ ⬇ Download PDF ]          │
└─────────────────────────────────────┘
```

---

## KOMPONEN SHARED (Design System)

| Komponen | Digunakan di |
|---|---|
| `<StatsCard>` | Overview dashboard |
| `<DataTable>` | Karyawan, payroll, cuti |
| `<StatusBadge>` | Status payroll, absensi, cuti |
| `<EntitySwitcher>` | Sidebar (holding_admin) |
| `<RoleGate>` | Wrapper semua elemen conditional per role |
| `<GeofenceMap>` | Halaman absensi GPS |
| `<PayslipView>` | Slip gaji (web + PDF layout) |
| `<ApprovalActions>` | Approve/reject cuti |

---

## CATATAN DESAIN

1. **Mobile-first** — Halaman absensi dan slip gaji harus sempurna di layar 375px (iPhone SE).
2. **Sidebar collapse** — Di mobile, sidebar tersembunyi dan muncul via hamburger menu.
3. **Role Gate** — Komponen `<RoleGate>` menyembunyikan elemen UI yang tidak relevan per role, bukan redirect. Menu payroll tetap muncul untuk employee tapi hanya menampilkan slip gaji miliknya.
4. **Offline resilience** — Halaman absensi mendeteksi status koneksi. Jika offline, tampil pesan informatif (tidak crash).
5. **Bahasa** — Seluruh UI dalam Bahasa Indonesia.
