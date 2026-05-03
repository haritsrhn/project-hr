# HRIS Tridaya — Frontend (Next.js 16)

Web app frontend untuk HRIS Tridaya Sejahtera Group. Dibangun dengan Next.js 16, React 19, TypeScript 5, TanStack Query v5, dan Zustand v5.

## Struktur Direktori

```
frontend/src/
├── app/
│   ├── (auth)/login/           # Halaman login
│   └── (dashboard)/
│       ├── layout.tsx          # Dashboard layout + sidebar + RoleGate
│       ├── overview/           # Dashboard overview
│       ├── employees/          # Employee list & detail
│       ├── attendance/         # GPS clock-in/out
│       ├── leave/              # Pengajuan & approval cuti
│       └── payroll/
│           ├── runs/           # Daftar payroll runs
│           ├── [id]/           # Detail run + items table
│           └── [id]/slip/      # Payslip view & print
├── components/
│   ├── modules/                # PayrollStatusBadge, PayslipView
│   ├── shared/                 # RoleGate, EntitySwitcher
│   └── ui/                     # shadcn/ui components
├── hooks/
│   └── useGeolocation.ts       # GPS hook dengan Haversine distance
├── lib/api/
│   ├── client.ts               # Axios + interceptor (snake_case → camelCase)
│   ├── employees.ts
│   ├── attendance.ts
│   ├── leave.ts
│   └── payroll.ts
├── store/
│   └── auth.store.ts           # Zustand auth (token + user + activeEmployment, persisted)
└── types/
    └── index.ts                # TypeScript types untuk semua domain
```

## Menjalankan Lokal

```bash
npm install
npm run dev
```

Frontend tersedia di http://localhost:3000. Backend harus sudah berjalan di http://localhost:8000.

## Environment

Buat file `.env.local`:
```env
NEXT_PUBLIC_API_URL=http://localhost:8000
```

## Konvensi

- **Data fetching**: TanStack Query v5 (`useQuery`, `useMutation`) — semua di `src/lib/api/`
- **Auth state**: Zustand store di `src/store/auth.store.ts` — `token`, `user`, `activeEmployment` di-persist ke localStorage
- **Route guard**: `proxy.ts` di project root (Next.js 16 convention, named export `proxy`)
- **Role check**: `<RoleGate allowedRoles={[...]}>` atau `useAuthStore().hasRole(slug)`
- **API response**: Axios interceptor otomatis transform snake_case → camelCase

## Build

```bash
npm run build
npm run start
```
