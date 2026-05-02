# HRIS Tridaya Sejahtera Group

Internal Human Resource Information System untuk Tridaya Sejahtera Group (Holding).

## Dokumentasi Perencanaan

| Dokumen | Deskripsi |
|---|---|
| [01 — Product Vision](docs/01-product-vision.md) | Visi produk, konteks bisnis, scope MVP |
| [02 — Architecture Decisions](docs/02-architecture-decisions.md) | ADR: keputusan teknis dan alasannya |
| [03 — Data Model](docs/03-data-model.md) | Skema database, ERD, catatan desain |
| [04 — Roadmap](docs/04-roadmap.md) | Roadmap 3 fase, checklist fitur, keputusan pending |
| [05 — User Story Map](docs/05-user-story-map.md) | 30+ user stories per persona, acceptance criteria, walking skeleton |
| [06 — Wireframe Planning](docs/06-wireframe-planning.md) | ASCII wireframe 6 flow utama, komponen shared, catatan desain |
| [07 — Setup Guide](docs/07-setup-guide.md) | Cara menjalankan proyek (Docker + Next.js) |

## Status Saat Ini

**Fase: Perencanaan (Phase 0)**

Seluruh keputusan arsitektur dan scope MVP telah ditetapkan. Langkah berikutnya adalah User Story Mapping dan setup proyek teknis.

## Stack

- **Backend:** Laravel 12 (PHP)
- **Frontend:** Next.js 15 (React) + PWA
- **Database:** PostgreSQL
- **Queue:** Redis + Laravel Horizon
- **Storage:** AWS S3 / GCS
- **Deployment:** Private Cloud (AWS / GCP)
