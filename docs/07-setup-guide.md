# 07 — Setup Guide (Cara Menjalankan Proyek)

## Prasyarat

- Docker Desktop (sudah terinstall, pastikan daemon berjalan)
- Node.js v22+ (sudah tersedia via nvm)
- Git

---

## Langkah 1: Clone & Konfigurasi Environment

```bash
cp .env.example .env
# Edit .env sesuai kebutuhan (DB password, dll)
```

---

## Langkah 2: Inisialisasi Laravel (Hanya Pertama Kali)

Pastikan Docker Desktop sudah berjalan, lalu:

```bash
# Install Laravel via Docker
docker run --rm \
  -v "$(pwd)/backend:/app" \
  -w /app \
  composer:2 \
  create-project laravel/laravel . --prefer-dist --no-interaction

# Konfigurasi .env Laravel
cp backend/.env.example backend/.env
```

Edit `backend/.env` dengan nilai berikut:
```env
APP_NAME="TRIDAYA HRIS"
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=tridaya_hris
DB_USERNAME=tridaya
DB_PASSWORD=secret

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=redis
REDIS_PASSWORD=secret
REDIS_PORT=6379

FILESYSTEM_DISK=s3
```

---

## Langkah 3: Install Package Laravel Tambahan

```bash
docker run --rm \
  -v "$(pwd)/backend:/app" \
  -w /app \
  composer:2 \
  require \
  laravel/sanctum \
  laravel/horizon \
  spatie/laravel-permission \
  spatie/laravel-activitylog \
  barryvdh/laravel-dompdf \
  league/flysystem-aws-s3-v3 \
  --no-interaction
```

---

## Langkah 4: Build & Jalankan Docker

```bash
# Build image PHP
docker compose build

# Jalankan semua services
docker compose up -d

# Verifikasi semua container berjalan
docker compose ps
```

---

## Langkah 5: Setup Database

```bash
# Generate app key
docker compose exec app php artisan key:generate

# Jalankan migrasi
docker compose exec app php artisan migrate

# (Opsional) Jalankan seeder untuk data awal
docker compose exec app php artisan db:seed
```

---

## Langkah 6: Frontend

```bash
cd frontend
npm install
npm run dev
```

Frontend tersedia di: http://localhost:3000
Backend API tersedia di: http://localhost:8000

---

## Services yang Berjalan

| Service | URL | Keterangan |
|---|---|---|
| Frontend (Next.js) | http://localhost:3000 | Web app |
| Backend API (Laravel) | http://localhost:8000 | REST API via Nginx |
| PostgreSQL | localhost:5432 | Database |
| Redis | localhost:6379 | Cache & Queue |

---

## Perintah Berguna

```bash
# Masuk ke container PHP
docker compose exec app sh

# Jalankan artisan command
docker compose exec app php artisan <command>

# Lihat log
docker compose logs -f app
docker compose logs -f horizon

# Stop semua service
docker compose down

# Stop dan hapus volume (HATI-HATI: menghapus data DB)
docker compose down -v
```
