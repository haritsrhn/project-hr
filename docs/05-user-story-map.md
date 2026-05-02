# 05 — User Story Map

> Format: "Sebagai [persona], saya ingin [aksi], agar [manfaat]"
> Acceptance Criteria ditulis untuk story yang kompleks atau berisiko tinggi.
> Priority: 🔴 Must Have (MVP) | 🟡 Should Have | 🟢 Nice to Have

---

## BACKBONE (Aktivitas Utama per Persona)

```
HRD/Admin    → [Setup Entitas] → [Kelola Karyawan] → [Proses Payroll] → [Pantau Kehadiran]
Karyawan     → [Onboarding]   → [Absensi Harian]  → [Kelola Cuti]    → [Lihat Slip Gaji]
Manajer      → [Pantau Tim]   → [Approve Request] → [Lihat Laporan]
Eksekutif    → [Monitor Grup] → [Lihat Agregat]
Super Admin  → [Konfigurasi]  → [Kelola Akses]
```

---

## M1 — MASTER DATA KARYAWAN

### Epic: Pengelolaan Profil Karyawan (HRD/Admin)

| ID | User Story | Priority |
|---|---|---|
| M1-01 | Sebagai HRD, saya ingin menambah karyawan baru dengan data lengkap (personal, kontak, jabatan), agar data SDM tersimpan terpusat dan tidak lagi di Excel. | 🔴 |
| M1-02 | Sebagai HRD, saya ingin mengedit data karyawan yang sudah ada, agar perubahan jabatan atau data personal selalu akurat. | 🔴 |
| M1-03 | Sebagai HRD, saya ingin mengupload dokumen karyawan (KTP, NPWP, Ijazah, SK Pengangkatan), agar semua dokumen legal tersimpan digital dan mudah ditemukan. | 🔴 |
| M1-04 | Sebagai HRD, saya ingin sistem mengingatkan saya ketika dokumen karyawan akan kadaluarsa (misal: kontrak habis), agar tidak ada yang terlewat. | 🟡 |
| M1-05 | Sebagai HRD, saya ingin mengimport data karyawan secara bulk via template Excel/CSV, agar migrasi dari sistem lama bisa dilakukan cepat tanpa input manual satu per satu. | 🔴 |
| M1-06 | Sebagai HRD, saya ingin menonaktifkan (bukan menghapus) karyawan yang resign, agar riwayat dan data historis tetap terjaga untuk audit. | 🔴 |
| M1-07 | Sebagai HRD, saya ingin assign satu karyawan ke lebih dari satu entitas dengan jabatan berbeda (dual employment), agar karyawan yang berperan lintas entitas bisa dikelola dalam satu profil. | 🔴 |

**Acceptance Criteria — M1-07 (Dual Employment):**
- Satu user hanya punya satu akun login.
- Sistem menampilkan daftar semua employment aktif milik user tersebut.
- Setiap employment memiliki entitas, jabatan, departemen, dan struktur gaji sendiri.
- Saat karyawan login, mereka melihat data sesuai employment yang di-set sebagai `is_primary`.
- HRD bisa switch context ke employment lain saat memproses payroll.

---

### Epic: Self-Service Profil (Karyawan)

| ID | User Story | Priority |
|---|---|---|
| M1-08 | Sebagai karyawan, saya ingin melihat profil saya sendiri (data personal, jabatan, riwayat posisi), agar saya tahu data saya di sistem sudah benar. | 🔴 |
| M1-09 | Sebagai karyawan, saya ingin memperbarui data kontak darurat saya sendiri, agar tidak perlu menghubungi HRD untuk perubahan kecil. | 🔴 |
| M1-10 | Sebagai karyawan, saya ingin melihat dokumen kontrak kerja saya, agar saya bisa mengaksesnya kapan saja tanpa harus minta ke HRD. | 🟡 |

---

### Epic: Visibilitas Eksekutif (Holding)

| ID | User Story | Priority |
|---|---|---|
| M1-11 | Sebagai eksekutif, saya ingin melihat total headcount per entitas dalam satu dashboard, agar saya tahu distribusi SDM di seluruh grup secara real-time. | 🔴 |
| M1-12 | Sebagai eksekutif, saya ingin melihat perbandingan komposisi karyawan (tetap vs kontrak vs magang) per entitas, agar bisa mengambil keputusan strategis rekrutmen. | 🟡 |

---

## M2 — ABSENSI & CUTI

### Epic: Absensi Harian (Karyawan)

| ID | User Story | Priority |
|---|---|---|
| M2-01 | Sebagai karyawan, saya ingin melakukan clock-in via GPS dari ponsel saya, agar absensi bisa dilakukan tanpa harus antri di mesin fingerprint. | 🔴 |
| M2-02 | Sebagai karyawan, saya ingin melakukan clock-in dengan scan QR Code di kantor, agar kehadiran fisik saya di lokasi terverifikasi. | 🔴 |
| M2-03 | Sebagai karyawan, saya ingin sistem menolak clock-in saya jika saya di luar radius lokasi kantor, agar saya tahu absensi hanya valid di area yang ditentukan. | 🔴 |
| M2-04 | Sebagai karyawan, saya ingin mendapat notifikasi jika saya belum clock-out sebelum jam tertentu, agar saya tidak lupa dan jam lembur tercatat akurat. | 🟡 |

**Acceptance Criteria — M2-01 & M2-03 (GPS Geofencing):**
- Sistem membandingkan koordinat GPS karyawan dengan koordinat pusat lokasi kerja.
- Radius toleransi dapat dikonfigurasi per lokasi (default: 100 meter).
- Jika di luar radius, sistem menolak clock-in dan menampilkan pesan error beserta jarak aktual.
- Koordinat GPS + timestamp + device hash dicatat di setiap record absensi.
- QR Code di-regenerate setiap 10 menit (time-based token) untuk mencegah screenshot fraud.

---

### Epic: Manajemen Cuti (Karyawan)

| ID | User Story | Priority |
|---|---|---|
| M2-05 | Sebagai karyawan, saya ingin melihat saldo cuti saya yang tersisa (per jenis cuti), agar saya tahu berapa hari cuti yang masih bisa saya ajukan. | 🔴 |
| M2-06 | Sebagai karyawan, saya ingin mengajukan cuti dengan memilih tanggal, jenis cuti, dan alasan, agar pengajuan saya terdokumentasi dan bisa dilacak statusnya. | 🔴 |
| M2-07 | Sebagai karyawan, saya ingin mendapat notifikasi ketika cuti saya diapprove atau ditolak beserta alasannya, agar saya tidak perlu menunggu tanpa kepastian. | 🔴 |
| M2-08 | Sebagai karyawan, saya ingin membatalkan pengajuan cuti yang masih berstatus "pending", agar saya tidak perlu meminta HRD untuk membatalkan manual. | 🟡 |

---

### Epic: Approval & Pemantauan (Manajer)

| ID | User Story | Priority |
|---|---|---|
| M2-09 | Sebagai manajer, saya ingin menerima notifikasi ketika ada bawahan yang mengajukan cuti, agar saya bisa segera merespons tanpa harus cek aplikasi terus-menerus. | 🔴 |
| M2-10 | Sebagai manajer, saya ingin approve atau reject pengajuan cuti dengan menambahkan catatan, agar keputusan saya terekam dan karyawan mendapat alasan yang jelas. | 🔴 |
| M2-11 | Sebagai manajer, saya ingin melihat kalender kehadiran tim saya dalam satu tampilan, agar saya bisa memastikan tidak terlalu banyak yang cuti di waktu yang sama. | 🔴 |

---

### Epic: Administrasi Absensi (HRD)

| ID | User Story | Priority |
|---|---|---|
| M2-12 | Sebagai HRD, saya ingin melihat rekap kehadiran seluruh karyawan per entitas per bulan, agar saya bisa memverifikasi data sebelum payroll diproses. | 🔴 |
| M2-13 | Sebagai HRD, saya ingin melakukan koreksi manual pada absensi karyawan (dengan alasan dan bukti), agar ketidakakuratan teknis bisa diperbaiki secara transparan. | 🔴 |
| M2-14 | Sebagai HRD, saya ingin mengkonfigurasi jadwal kerja (shift) per departemen atau entitas, agar sistem bisa otomatis mendeteksi keterlambatan. | 🟡 |
| M2-15 | Sebagai HRD, saya ingin mengexport laporan absensi ke Excel, agar bisa digunakan untuk keperluan pelaporan eksternal. | 🔴 |

---

## M3 — PAYROLL OTOMATIS

### Epic: Konfigurasi Gaji (HRD)

| ID | User Story | Priority |
|---|---|---|
| M3-01 | Sebagai HRD, saya ingin mengkonfigurasi komponen gaji karyawan (gaji pokok, tunjangan jabatan, tunjangan transport, dll), agar kalkulasi payroll otomatis mencerminkan struktur gaji aktual. | 🔴 |
| M3-02 | Sebagai HRD, saya ingin mengatur status PTKP (Penghasilan Tidak Kena Pajak) setiap karyawan (TK/0, K/1, K/2, dll), agar PPh 21 dihitung sesuai kondisi keluarga masing-masing. | 🔴 |
| M3-03 | Sebagai HRD, saya ingin mengkonfigurasi apakah karyawan ditanggung BPJS oleh perusahaan atau mandiri, agar iuran BPJS dihitung dengan benar per karyawan. | 🔴 |

---

### Epic: Proses Payroll Bulanan (HRD)

| ID | User Story | Priority |
|---|---|---|
| M3-04 | Sebagai HRD, saya ingin memproses payroll satu entitas untuk satu periode (bulan/tahun) dengan satu klik, agar tidak perlu kalkulasi manual di Excel. | 🔴 |
| M3-05 | Sebagai HRD, saya ingin mereview hasil kalkulasi payroll (gross, potongan BPJS, PPh 21, net) sebelum finalisasi, agar saya bisa mengoreksi jika ada data yang salah. | 🔴 |
| M3-06 | Sebagai HRD, saya ingin menambahkan potongan atau tunjangan insidentil (bonus, denda keterlambatan) sebelum payroll difinalisasi, agar semua komponen bulan ini tercakup. | 🔴 |
| M3-07 | Sebagai HRD, saya ingin mengunci payroll yang sudah difinalisasi agar tidak bisa diedit, dan hanya bisa dibatalkan oleh super admin dengan alasan tercatat. | 🔴 |
| M3-08 | Sebagai HRD, saya ingin mengexport daftar transfer gaji (nama, bank, nomor rekening, nominal) ke format yang bisa diupload ke aplikasi perbankan, agar proses transfer massal lebih mudah. | 🟡 |

**Acceptance Criteria — M3-04 (Proses Payroll):**
- Sistem otomatis mengambil data kehadiran bulan tersebut untuk menghitung potongan alpha/terlambat.
- Kalkulasi PPh 21 menggunakan tarif progresif terbaru (UU HPP 2021) + metode yang dipilih (gross/gross-up).
- BPJS Kesehatan: 4% employer + 1% employee dari gaji (cap sesuai regulasi BPJS).
- BPJS Ketenagakerjaan: JHT (3.7% employer + 2% employee), JKK (sesuai risiko), JKM (0.3% employer), JP (2% employer + 1% employee, jika berlaku).
- Hasil kalkulasi tersimpan sebagai snapshot — perubahan regulasi di masa depan tidak mempengaruhi data historis.

---

### Epic: Slip Gaji (Karyawan)

| ID | User Story | Priority |
|---|---|---|
| M3-09 | Sebagai karyawan, saya ingin melihat slip gaji saya setiap bulan langsung di aplikasi, agar saya tidak perlu meminta HRD. | 🔴 |
| M3-10 | Sebagai karyawan, saya ingin mendownload slip gaji saya dalam format PDF, agar bisa digunakan untuk keperluan seperti pengajuan kredit. | 🔴 |
| M3-11 | Sebagai karyawan, saya ingin melihat riwayat slip gaji 12 bulan ke belakang, agar saya punya akses ke histori kompensasi saya. | 🟡 |

---

### Epic: Laporan Eksekutif (Holding)

| ID | User Story | Priority |
|---|---|---|
| M3-12 | Sebagai eksekutif, saya ingin melihat total pengeluaran tenaga kerja seluruh grup bulan ini (dibagi per entitas), agar saya bisa memantau cost SDM tanpa perlu meminta laporan manual. | 🔴 |
| M3-13 | Sebagai eksekutif, saya ingin melihat tren biaya tenaga kerja 6 bulan terakhir per entitas dalam bentuk grafik, agar bisa mendeteksi anomali atau kenaikan tidak wajar. | 🟡 |

---

## M4 — RBAC & MULTI-ENTITAS

### Epic: Konfigurasi Sistem (Super Admin)

| ID | User Story | Priority |
|---|---|---|
| M4-01 | Sebagai super admin, saya ingin menambah entitas baru (PT/CV/Yayasan) ke dalam sistem, agar Tridaya Group bisa onboard unit bisnis baru tanpa perlu konfigurasi teknis kompleks. | 🔴 |
| M4-02 | Sebagai super admin, saya ingin mengatur hierarki entitas (holding → anak perusahaan), agar struktur grup tercermin dalam sistem. | 🔴 |
| M4-03 | Sebagai super admin, saya ingin assign role ke user dengan scope tertentu (misal: entity_admin hanya untuk PT A), agar akses user terbatas sesuai tanggung jawabnya. | 🔴 |
| M4-04 | Sebagai super admin, saya ingin melihat audit log semua aksi sensitif (login, edit payroll, approve, perubahan role), agar ada jejak digital untuk kebutuhan audit internal. | 🔴 |

---

### Epic: Isolasi Data Antar Entitas

| ID | User Story | Priority |
|---|---|---|
| M4-05 | Sebagai entity_admin PT A, saya ingin sistem memastikan saya hanya bisa melihat dan mengelola data karyawan PT A, agar data entitas lain tidak bisa saya akses secara tidak sengaja. | 🔴 |
| M4-06 | Sebagai holding_admin, saya ingin bisa berpindah konteks antar entitas dalam satu sesi login, agar saya bisa mengelola semua entitas tanpa harus login ulang. | 🔴 |

---

## RINGKASAN PRIORITAS MVP

### 🔴 Must Have (Wajib di v1.0)
Total: **27 stories**
Modul M1: 8 stories | M2: 10 stories | M3: 7 stories | M4: 5 stories (estimasi: tidak kritis karena RBAC melekat di semua modul)

### 🟡 Should Have (Target v1.1 — jika waktu cukup)
M1-04, M1-12, M2-08, M2-14, M3-08, M3-13

### 🟢 Nice to Have (Backlog Phase 2)
M2-04, M1-10, M3-11

---

## WALKING SKELETON (Alur Minimum yang Bisa Didemonstrasikan)

Urutan implementasi yang menghasilkan sistem bisa dijalankan end-to-end secepatnya:

```
1. Auth + RBAC dasar (login, session, role check)
        ↓
2. Setup entitas + tambah karyawan (M1-01, M4-01)
        ↓
3. Karyawan bisa login + lihat profil (M1-08)
        ↓
4. Clock-in GPS + rekap sederhana (M2-01, M2-12)
        ↓
5. Konfigurasi gaji + proses payroll + slip gaji (M3-01, M3-04, M3-09)
        ↓
[DEMO-READY: Sistem bisa diperlihatkan ke stakeholder]
        ↓
6. Cuti: pengajuan + approval (M2-06, M2-10)
7. QR Code absensi (M2-02)
8. Import bulk karyawan (M1-05)
9. Dashboard eksekutif (M1-11, M3-12)
```
