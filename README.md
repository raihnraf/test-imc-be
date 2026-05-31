# IMC Backend — User Permission Management API

REST API backend untuk mengatur akses pengguna berdasarkan role (level) dan permission per-halaman. Dibangun dengan **Slim PHP 4 + PostgreSQL** sesuai spesifikasi technical test, dengan tambahan fitur-fitur **senior engineering** seperti clean architecture, comprehensive testing (136 test), rate limiting, token refresh, dan permission enforcement real-time.

> **Repo ini hanya backend.** Frontend Angular 20 ada di repo terpisah.

---

## Quick Start

```bash
cp .env.example .env                        # JWT_SECRET sudah terisi, DB credential default
docker compose up -d                        # PHP 8.1 + PostgreSQL 15
docker compose exec app php migrations/migrate.php
docker compose exec app php seeds/seed.php  
curl http://localhost:8080/                 # → {"status":"ok"}
```

**Default admin login:** `admin` / `admin123`

---

## Pemenuhan Spesifikasi Wajib (plan.md)

### 3.1 CRUD User

| Requirement | Implementasi |
|---|---|
| Admin dapat CRUD user | `GET/POST/PUT/DELETE /api/users` |
| Field: nama lengkap, username, email, password, level, status | ✅ Semua field + validasi format |
| Password wajib hash (bukan plain text) | ✅ Argon2id via `password_hash()` |
| Username dan email unik | ✅ 409 `DUPLICATE_ENTRY` dengan field indicator |
| User nonaktif tidak bisa login | ✅ 401 `ACCOUNT_INACTIVE` |

### 3.2 CRUD Level

| Requirement | Implementasi |
|---|---|
| Admin dapat CRUD level | `GET/POST/PUT/DELETE /api/levels` |
| Field: nama level, deskripsi, status | ✅ + pagination & filtering |
| Level dengan user aktif tidak boleh hapus langsung | ✅ **Soft delete** via `deleted_at` — user tetap punya FK valid tapi level tersembunyi dari list |

### 3.3 CRUD Page

| Requirement | Implementasi |
|---|---|
| Admin dapat CRUD page | `GET/POST/PUT/DELETE /api/pages` |
| Field: nama page, route/path, deskripsi, urutan tampil, status | ✅ Semua field |
| Route/path harus unik | ✅ 409 `DUPLICATE_ENTRY` + DB unique constraint |
| Page tampil berdasarkan permission user login | ✅ PermissionMiddleware cek per request dari database |

### 3.4 Manajemen User Permission

| Requirement | Implementasi |
|---|---|
| Tentukan page untuk level tertentu | ✅ `POST/DELETE /api/levels/{id}/permissions` |
| Tentukan pengecualian per user | ✅ `POST/DELETE /api/users/{id}/permissions` — grant/deny override via `is_granted` boolean |
| Tampilan permission matrix (checkbox) | ✅ `GET /api/permissions/matrix?level_id=X` atau `?user_id=X` |
| Perubahan langsung memengaruhi akses | ✅ Permission dicek dari database setiap request, bukan dari JWT |

### 3.5 Autentikasi & Otorisasi

| Requirement | Implementasi |
|---|---|
| Login dengan username/email + password | ✅ `POST /auth/login` — support keduanya |
| Backend mengembalikan token | ✅ JWT access token (15 menit) + refresh token (7 hari) |
| Backend menolak akses tanpa permission | ✅ PermissionMiddleware → 403 `FORBIDDEN` |
| Frontend menyembunyikan menu tanpa akses | — (frontend di repo terpisah) |

### 4. Deliverables

| Deliverable | Status |
|---|---|
| Source code BE (Slim PHP) | ✅ 39 file, clean architecture |
| Database migration + seed | ✅ 8 migrations, 1 seeder |
| Postman collection | ✅ `postman/IMC-Backend-API.postman_collection.json` — 23 request |

---

## Fitur Tambahan (Above Expectations)

Di luar spesifikasi minimum, project ini menunjukkan kualitas **senior-level engineering**:

### Arsitektur & Kode

- **Clean Architecture 3-layer:** `Application → Domain → Infrastructure` — separation of concerns ketat
- **Repository Pattern** dengan interface contracts — semua akses data melalui interface, mudah di-test dan di-swap
- **PHP 8.1 strict types:** `declare(strict_types=1)` di **setiap file PHP** (39/39)
- **Custom Exception Hierarchy:** 6 kelas exception terstruktur (`AuthenticationException`, `AuthorizationException`, `ValidationException`, `NotFoundException`, `DuplicateEntryException`, `DomainException`) — tidak ada generic exception
- **Dependency Injection:** PHP-DI container dengan autowiring — tidak ada `new` di dalam class
- **Immutable Domain Entities:** Entities adalah plain PHP object dengan typed properties, bukan Eloquent Model

### Security

- **Rate Limiting login:** Maksimal 5 percobaan per menit per IP → 429 `RATE_LIMITED` dengan sliding window di PostgreSQL
- **Token Refresh + Rotation:** Access token 15 menit, refresh token 7 hari. Refresh token single-use — setelah dipakai, token lama langsung di-revoke (replay attack detection)
- **Permission enforcement real-time:** Setiap request dicek ke database (`hasAccess()` query dengan CASE/LEFT JOIN), bukan dari claims JWT. Perubahan permission langsung berlaku tanpa perlu logout
- **Argon2id password hashing:** Memory-hard algorithm, resistant terhadap GPU brute-force
- **IP-based rate limiting:** Pakai `REMOTE_ADDR` langsung (bukan `X-Forwarded-For` yang bisa di-spoof)

### Testing & QA

- **136 PHPUnit test**, 289 assertion — semuanya **passing**
- **Integration tests:** 105 test mencakup semua endpoint (CRUD, auth, permission, rate limit, token refresh)
- **Unit tests:** 31 test untuk repository logic, permission resolution, token service, pagination
- **Test isolation:** Database test terpisah (`imc_test`), data dibersihkan antar test
- **PHPStan level 5:** Static analysis strict — 0 error pada semua source code

### API Maturity

- **Pagination konsisten:** Semua list endpoint support `?page=N&per_page=N` dengan response `{data, meta: {page, per_page, total, total_pages}}`
- **Filtering:** Semua list endpoint support `?search=` (ILIKE), `?is_active=`, dan filter spesifik (`?level_id=`)
- **Konsisten error format:** `{statusCode, error: {type, description, errors?}}` — tidak ada endpoint yang return format berbeda
- **Input validation:** Semua POST/PUT endpoint memvalidasi input dengan structured error per-field

### Developer Experience

- **Docker Compose:** Satu perintah `docker compose up -d` — reviewer tidak perlu install PHP, Composer, atau PostgreSQL
- **Migration + Seeder:** `migrate.php` dan `rollback.php` — schema versioning jelas
- **Postman Collection:** 23 request siap pakai dengan auto-auth token flow
- **Seed data lengkap:** 4 level, 6 page, 1 admin user, Super Admin langsung punya akses ke semua page

---

## Arsitektur

```
src/
├── Application/           # Use cases, controllers, middleware
│   ├── Actions/           # Request handler — 1 class per resource
│   │   ├── Auth/          # LoginAction, RefreshTokenAction
│   │   ├── Level/         # LevelAction (list/get/create/update/delete)
│   │   ├── Page/          # PageAction
│   │   ├── Permission/    # LevelPermissionAction, UserPermissionAction, PermissionMatrixAction
│   │   └── User/          # UserAction
│   ├── Handlers/          # JsonErrorRenderer, HttpErrorHandler
│   ├── Helpers/           # PaginationHelper
│   ├── Middleware/        # JwtMiddleware, PermissionMiddleware, RateLimitMiddleware
│   └── Settings/          # Config dari .env
├── Domain/                # Business logic, entities, repository interfaces + implementations
│   ├── Exceptions/        # 6 kelas exception terstruktur
│   ├── Level/             # Level entity + LevelRepositoryInterface + LevelRepository
│   ├── Page/              # Page entity + interface + implementation
│   ├── Permission/        # PermissionRepository — hasAccess, matrix, assign/remove
│   ├── RateLimit/         # RateLimitRepository — sliding window query
│   ├── RefreshToken/      # RefreshTokenRepository — hash-based storage
│   ├── Token/             # TokenService — JWT generation + refresh token
│   └── User/              # User entity + interface + implementation
└── Infrastructure/        # Framework wiring
    └── Container/         # PHP-DI container definitions
```

---

## API Reference

### Authentication

| Method | Endpoint | Auth | Rate Limited | Deskripsi |
|---|---|---|---|---|
| `POST` | `/auth/login` | — | ✅ 5/menit/IP | Login (username atau email) |
| `POST` | `/auth/refresh` | — | — | Refresh access token |

**Login response:**
```json
{
  "data": {
    "access_token": "eyJ...",
    "refresh_token": "a1b2c3...",
    "token_type": "Bearer",
    "expires_in": 900,
    "user": { "id": 1, "username": "admin", "nama_lengkap": "Super Admin", "level_id": 1 }
  }
}
```

### Levels — `/api/levels`

| Method | Endpoint | Deskripsi |
|---|---|---|
| `GET` | `/api/levels` | List levels (paginated, filterable: `?search=&is_active=`) |
| `GET` | `/api/levels/{id}` | Detail level |
| `POST` | `/api/levels` | Buat level baru |
| `PUT` | `/api/levels/{id}` | Update level |
| `DELETE` | `/api/levels/{id}` | Soft-delete level |

Semua endpoint di atas butuh JWT token (`Authorization: Bearer <token>`).

### Users — `/api/users`

| Method | Endpoint | Deskripsi |
|---|---|---|
| `GET` | `/api/users` | List users (paginated, filterable: `?search=&is_active=&level_id=`) |
| `GET` | `/api/users/{id}` | Detail user (password tidak muncul) |
| `POST` | `/api/users` | Buat user baru |
| `PUT` | `/api/users/{id}` | Update user |
| `DELETE` | `/api/users/{id}` | Hapus user |

### Pages — `/api/pages`

| Method | Endpoint | Deskripsi |
|---|---|---|
| `GET` | `/api/pages` | List pages (paginated, filterable) |
| `GET` | `/api/pages/{id}` | Detail page |
| `POST` | `/api/pages` | Buat page baru |
| `PUT` | `/api/pages/{id}` | Update page |
| `DELETE` | `/api/pages/{id}` | Hapus page |

### Permissions

| Method | Endpoint | Deskripsi |
|---|---|---|
| `GET` | `/api/levels/{id}/permissions` | Matrix permission per level |
| `POST` | `/api/levels/{id}/permissions` | Assign page ke level (body: `{page_id}`) |
| `DELETE` | `/api/levels/{id}/permissions` | Remove page dari level (body: `{page_id}`) |
| `GET` | `/api/users/{id}/permissions` | Matrix permission per user (dengan override) |
| `POST` | `/api/users/{id}/permissions` | Grant/deny page untuk user (body: `{page_id, is_granted}`) |
| `DELETE` | `/api/users/{id}/permissions` | Hapus override user |
| `GET` | `/api/permissions/matrix?level_id=X` | Matrix global — query by level atau user |

### Response Format

**Sukses (200/201):**
```json
{
  "data": { ... },
  "meta": { "page": 1, "per_page": 20, "total": 42, "total_pages": 3 }
}
```

**Error:**
```json
{
  "statusCode": 422,
  "error": {
    "type": "VALIDATION_ERROR",
    "description": "Invalid input",
    "errors": { "username": ["Username already taken"] }
  }
}
```

### Error Types

| Type | HTTP | Arti |
|---|---|---|
| `TOKEN_MISSING` | 401 | Tidak ada Authorization header |
| `INVALID_TOKEN` | 401 | Token invalid/expired/tidak valid |
| `ACCOUNT_INACTIVE` | 401 | User sudah dinonaktifkan |
| `RATE_LIMITED` | 429 | Terlalu banyak percobaan login |
| `FORBIDDEN` | 403 | Tidak punya permission untuk halaman ini |
| `NOT_FOUND` | 404 | Resource tidak ditemukan |
| `VALIDATION_ERROR` | 422 | Input tidak valid (detail per-field) |
| `DUPLICATE_ENTRY` | 409 | Username/email/route_path sudah dipakai |

---

## Database Schema

```
levels                          users
├── id (PK)                     ├── id (PK)
├── nama_level                  ├── nama_lengkap
├── deskripsi                   ├── username (UNIQUE)
├── is_active                   ├── email (UNIQUE)
├── deleted_at (soft delete)    ├── password (ARGON2ID)
├── created_at                  ├── level_id (FK → levels)
└── updated_at                  ├── is_active
                                ├── created_at
pages                           └── updated_at
├── id (PK)
├── nama_page                   level_permissions
├── route_path (UNIQUE)         ├── level_id (PK, FK → levels)
├── deskripsi                   └── page_id (PK, FK → pages)
├── urutan_tampil
├── is_active                   user_permissions
├── created_at                  ├── user_id (PK, FK → users)
└── updated_at                  ├── page_id (PK, FK → pages)
                                └── is_granted (BOOLEAN)

login_attempts                  refresh_tokens
├── ip_address                  ├── id (PK)
├── attempted_at                ├── user_id (FK → users)
└── INDEX (ip, time)            ├── token_hash (SHA-256, UNIQUE)
                                ├── expires_at
                                ├── revoked_at
                                └── created_at
```

---

## Testing

```bash
# Semua test (136 test, 289 assertion)
docker compose exec app vendor/bin/phpunit

# Unit test saja
docker compose exec app vendor/bin/phpunit --testsuite Unit

# Integration test saja
docker compose exec app vendor/bin/phpunit --testsuite Integration

# Static analysis
docker compose exec app vendor/bin/phpstan analyse src/ --level=5 --memory-limit=256M
```

| Suite | Test | Assertion |
|---|---|---|
| Unit (Domain logic, pagination) | 31 | 52 |
| Integration (API endpoints) | 105 | 237 |
| **Total** | **136** | **289** |

---

## Tech Stack

| Komponen | Library | Versi |
|---|---|---|
| Framework | Slim Framework | 4.x |
| DI Container | PHP-DI | 7.x |
| JWT | firebase/php-jwt | 6.x |
| Database | PostgreSQL | 15 (Alpine) |
| ORM/Migration | illuminate/database (Eloquent) | 11.x |
| PSR-7 | slim/psr7 | 1.x |
| Password Hashing | PHP `password_hash()` — Argon2id | Built-in |
| Dotenv | vlucas/phpdotenv | 5.x |
| Testing | PHPUnit | 10.x |
| Static Analysis | PHPStan | 1.x (level 5) |
| Runtime | PHP | 8.1 (Apache) |
| Infra | Docker Compose | v2 |

---

## Struktur Proyek

```
.
├── docker-compose.yml                  # PHP 8.1 + PostgreSQL 15
├── docker/
│   ├── Dockerfile                      # PHP Apache + pdo_pgsql
│   └── php/php.ini                     # Production config
├── public/
│   ├── index.php                       # Entry point — Slim App + DI
│   └── .htaccess                       # Apache rewrite
├── src/                                # Source code (39 file)
│   ├── Application/                    # Actions, middleware, handlers, helpers
│   ├── Domain/                         # Entities, interfaces, repositories
│   └── Infrastructure/                 # DI container
├── tests/                              # PHPUnit (17 file)
│   ├── Unit/
│   └── Integration/
├── migrations/                         # 8 migrations + runner + rollback
├── seeds/
│   └── seed.php                        # 4 levels, 6 pages, 1 admin + permissions
├── routes/                             # Route + middleware registration
├── postman/
│   └── IMC-Backend-API.postman_collection.json
├── .planning/                          # GSD planning artifacts
├── composer.json
├── phpunit.xml
├── phpstan.neon
└── .env.example
```
