# IMC Backend — User Permission Management API

REST API backend untuk mengatur akses pengguna berdasarkan role (level) dan permission per-halaman. Dibangun dengan **Slim PHP 4 + PostgreSQL** sesuai spesifikasi technical test, dengan fitur-fitur tambahan seperti repository pattern, comprehensive testing (192 tests), rate limiting, token refresh, dan permission enforcement real-time.

> **Repo ini hanya backend.** Frontend Angular 20 ada di repo terpisah.

---

## Quick Start (30 detik)

```bash
cp .env.example .env
docker compose up -d
docker compose exec app php migrations/migrate.php
docker compose exec app php seeds/seed.php
# → App running at http://localhost:8080
curl http://localhost:8080/                 # → {"status":"ok"}
```

**Default credentials:**

| Role | Username | Password |
|------|----------|----------|
| Super Admin | `admin` | `admin123` (ganti via `ADMIN_PASSWORD` di `.env`) |

---

## Architecture Decisions

| Decision | Pilihan | Alasan |
|----------|---------|--------|
| Password hashing | **Argon2id** | Memory-hard algorithm, resistant terhadap GPU brute-force. Lebih aman dari bcrypt untuk aplikasi baru |
| Token strategy | **Access + Refresh token rotation** | Access token short-lived (15 menit), refresh token single-use (7 hari). Setelah dipakai, token lama di-revoke — mencegah replay attack |
| Permission enforcement | **Database lookup per request** | Bukan dari JWT claims. Perubahan permission langsung berlaku tanpa logout. Trade-off: 1 extra query per request, tapi audit trail lebih akurat |
| Level soft delete | **`deleted_at` column + guard** | Level dengan user aktif tidak bisa dihapus (409 `RESOURCE_IN_USE`). Data historis tetap tersedia untuk audit |
| Rate limiting | **Sliding window di PostgreSQL** | Bukan in-memory (Redis). Lebih sederhana untuk skala test project, persist across restarts, dan cukup untuk 5 req/menit/IP |
| Repository pattern | **Interface + implementation** | Semua akses data via interface. Memungkinkan mocking di unit test dan swap implementasi tanpa ubah business logic |
| Custom exception hierarchy | **7 typed exceptions** | Tidak ada generic `Exception`. Setiap error type punya HTTP status code dan error code yang konsisten |
| Environment auto-detect | **DB_HOST presence check** | Kode otomatis tahu apakah di Docker atau local. Tidak perlu ubah `.env` untuk switch environment |
| Eloquent migrations | **illuminate/database** | Migration + seeder tooling dalam satu package. Tidak perlu build custom CLI tool untuk schema management |

---

## Quick Start (Detail)

### Opsi 1: Docker (Recommended)

```bash
cp .env.example .env                        # JWT_SECRET sudah terisi, DB credential default
docker compose up -d                        # PHP 8.1 + PostgreSQL 15
docker compose exec app php migrations/migrate.php
docker compose exec app php seeds/seed.php  
curl http://localhost:8080/                 # → {"status":"ok"}
```

### Opsi 2: Local Machine

Prasyarat: PHP 8.1+, Composer, PostgreSQL, dan extension `pdo_pgsql`.

```bash
cp .env.example .env                        # DB_HOST tidak perlu diset — auto-detect ke localhost
composer install
php migrations/migrate.php
php seeds/seed.php
php -S localhost:8080 -t public/
curl http://localhost:8080/                 # → {"status":"ok"}
```

### Contoh Penggunaan API

```bash
# 1. Login — dapatkan JWT token (password default: admin123, bisa diganti via ADMIN_PASSWORD di .env)
curl -X POST http://localhost:8080/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'

# 2. Gunakan token untuk akses endpoint
curl http://localhost:8080/api/users \
  -H "Authorization: Bearer <access_token_dari_login>"

# 3. Lihat semua page yang tersedia
curl http://localhost:8080/api/pages \
  -H "Authorization: Bearer <access_token_dari_login>"
```

**Default admin login:** `admin` / `admin123` (atau set `ADMIN_PASSWORD` di `.env` lalu re-seed)

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
| Level dengan user aktif tidak boleh hapus langsung | ✅ **Soft delete** via `deleted_at` + guard check: jika level masih punya user aktif → 409 `RESOURCE_IN_USE` |

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
| Source code BE (Slim PHP) | ✅ 46 file, 3-layer architecture |
| Database migration + seed | ✅ 9 migrations + bootstrap, 1 seeder |
| Postman collection | ✅ `postman/IMC-Backend-API.postman_collection.json` — 23 request |

---

## Fitur Tambahan (Above Expectations)

Di luar spesifikasi minimum, project ini dilengkapi dengan fitur-fitur engineering quality:

### Arsitektur & Kode

- **3-layer architecture:** `Application → Domain → Infrastructure` — separation of concerns
- **Repository Pattern** dengan interface contracts — semua akses data melalui interface, mudah di-test dan di-swap
- **PHP strict types:** `declare(strict_types=1)` di setiap file PHP
- **Custom Exception Hierarchy:** 7 kelas exception terstruktur (`AuthenticationException`, `AuthorizationException`, `ValidationException`, `NotFoundException`, `DuplicateEntryException`, `ResourceInUseException`, `DomainException`) — tidak ada generic exception
- **Dependency Injection:** PHP-DI container dengan autowiring
- **Dedicated Validators:** `UserValidator` (30 unit tests), `LevelValidator`, `PageValidator` — terpisah dari Action
- **Trait-based dispatch:** `DispatchByMethod` trait menghilangkan duplikasi method routing di semua Action
- **Entity `toApiResponse()`:** Mapping entity→response terpusat di entity, bukan di Action
- **CORS Middleware:** Support cross-origin request untuk frontend terpisah

### Security

- **Security Headers:** `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Strict-Transport-Security`, `Referrer-Policy`, `Cache-Control: no-store` — diterapkan ke semua response via middleware
- **Rate Limiting login:** Maksimal 5 percobaan per menit per IP → 429 `RATE_LIMITED` dengan sliding window di PostgreSQL
- **Token Refresh + Rotation:** Access token 15 menit, refresh token 7 hari. Refresh token single-use — setelah dipakai, token lama langsung di-revoke (replay attack detection)
- **Permission enforcement real-time:** Setiap request dicek ke database (`hasAccess()` query), bukan dari claims JWT. Perubahan permission langsung berlaku tanpa perlu logout
- **Argon2id password hashing:** Memory-hard algorithm, resistant terhadap GPU brute-force
- **IP-based rate limiting:** Pakai `REMOTE_ADDR` langsung (bukan `X-Forwarded-For` yang bisa di-spoof)
- **Level delete guard:** Level yang masih punya user aktif tidak bisa dihapus → 409 `RESOURCE_IN_USE`

### Testing & QA

- **192 PHPUnit test**, 530 assertion — semuanya **passing** (local + Docker)
- **Integration tests:** 78 test mencakup semua endpoint (CRUD, auth, permission, rate limit, token refresh)
- **Unit tests:** 114 test untuk validator, repository logic, permission resolution, token service, pagination
- **PHPStan level 5:** Static analysis — 0 error pada semua source code
- **PHP-CS-Fixer:** Coding standard otomatis via `.php-cs-fixer.dist.php`

### API Maturity

- **Pagination konsisten:** Semua list endpoint support `?page=N&per_page=N` dengan response `{data, meta: {page, per_page, total, total_pages}}`
- **Filtering:** Semua list endpoint support `?search=` (ILIKE), `?is_active=`, dan filter spesifik (`?level_id=`)
- **Konsisten error format:** `{statusCode, error: {type, description, errors?}}` — semua endpoint return format yang sama
- **Input validation:** Semua POST/PUT endpoint memvalidasi input dengan structured error per-field

### Developer Experience

- **Auto-detect environment:** Kode otomatis mendeteksi Docker vs local — tidak perlu ubah `.env` untuk switch environment
- **Docker Compose:** Satu perintah `docker compose up -d` — tidak perlu install PHP, Composer, atau PostgreSQL
- **Shared CLI bootstrap:** `migrations/bootstrap.php` — single source of truth untuk koneksi database di migrate/rollback/seed
- **Migration version tracking:** Tabel `schema_migrations` — menjalankan migrate dua kali aman (skip yang sudah applied)
- **Postman Collection:** 23 request siap pakai dengan auto-auth token flow
- **Seed data lengkap:** 4 level, 6 page, 1 admin user, Super Admin langsung punya akses ke semua page

---

## Arsitektur

```
src/
├── Application/                    # Use cases, controllers, middleware
│   ├── Actions/                    # Request handler — 1 class per resource
│   │   ├── Auth/                   # LoginAction, RefreshTokenAction
│   │   ├── Level/                  # LevelAction (list/get/create/update/delete)
│   │   ├── Page/                   # PageAction
│   │   ├── Permission/             # LevelPermissionAction, UserPermissionAction, PermissionMatrixAction
│   │   ├── User/                   # UserAction
│   │   ├── BaseAction.php          # Shared response helpers
│   │   └── DispatchByMethod.php    # Trait — method dispatch via HTTP verb
│   ├── Handlers/                   # JsonErrorRenderer, HttpErrorHandler
│   ├── Helpers/                    # PaginationHelper (pure formatter)
│   ├── Middleware/                 # JwtMiddleware, PermissionMiddleware, RateLimitMiddleware, CorsMiddleware, SecurityHeadersMiddleware
│   ├── Settings/                   # Config dari .env (auto-detect Docker/local)
│   └── Validation/                 # UserValidator, LevelValidator, PageValidator
├── Domain/                         # Business logic, entities, repository interfaces + implementations
│   ├── Exceptions/                 # 7 kelas exception terstruktur
│   ├── Level/                      # Level entity + LevelRepositoryInterface + LevelRepository
│   ├── Page/                       # Page entity + interface + implementation
│   ├── Permission/                 # PermissionRepository — hasAccess, matrix, assign/remove
│   ├── RateLimit/                  # RateLimitRepository — sliding window query
│   ├── RefreshToken/               # RefreshTokenRepository — hash-based storage
│   ├── Token/                      # TokenService — JWT generation + refresh token
│   └── User/                       # User entity + interface + implementation
└── Infrastructure/                 # Framework wiring
    └── Container/                  # PHP-DI container definitions
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
    "user": { "id": 1, "username": "admin", "full_name": "Super Admin", "level_id": 1 }
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
| `DUPLICATE_ENTRY` | 409 | Username/email/route_path/nama_level sudah dipakai |
| `RESOURCE_IN_USE` | 409 | Level masih punya user aktif, tidak bisa dihapus |

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

Test bisa dijalankan di **Docker** maupun **local** — konfigurasi database auto-detect berdasarkan environment.

### Via Docker

```bash
# Semua test (192 test, 539 assertion)
docker compose exec app ./vendor/bin/phpunit

# Test spesifik
docker compose exec app ./vendor/bin/phpunit tests/Integration/UserTest.php

# Unit test saja
docker compose exec app ./vendor/bin/phpunit --testsuite Unit

# Integration test saja
docker compose exec app ./vendor/bin/phpunit --testsuite Integration

# Test dengan output detail (testdox)
docker compose exec app ./vendor/bin/phpunit --testdox

# Static analysis
docker compose exec app ./vendor/bin/phpstan analyse src/ --level=5 --memory-limit=256M
```

### Via Local Machine

```bash
# Semua test
./vendor/bin/phpunit

# Test spesifik
./vendor/bin/phpunit tests/Integration/UserTest.php

# Static analysis
./vendor/bin/phpstan analyse src/ --level=5 --memory-limit=256M
```

| Suite | Test | Assertion |
|---|---|---|
| Unit (Validators, Domain logic, pagination, permission resolution) | 114 | 326 |
| Integration (API endpoints) | 78 | 204 |
| **Total** | **192** | **530** |

### Troubleshooting

| Masalah | Solusi |
|---|---|
| `could not find driver` di local | Install `pdo_pgsql`: `sudo apt install php8.x-pgsql` |
| Test gagal 401 `INVALID_TOKEN` | Pastikan database sudah running dan migration sudah dijalankan |
| Connection refused | Cek PostgreSQL running dan credential di `.env` benar |

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
| Runtime | PHP | 8.1 (Docker) / 8.4 (local) |
| Infra | Docker Compose (opsional) | v2 |

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
├── src/                                # Source code (46 file)
│   ├── Application/                    # Actions, middleware, handlers, helpers, validators
│   ├── Domain/                         # Entities, interfaces, repositories, exceptions
│   └── Infrastructure/                 # DI container
├── tests/                              # PHPUnit (17 file)
│   ├── Unit/
│   └── Integration/
├── migrations/                         # 9 migrations + bootstrap + migrate + rollback
│   └── bootstrap.php                   # Shared DB connection CLI
├── seeds/
│   └── seed.php                        # 4 levels, 6 pages, 1 admin + permissions
├── routes/                             # Route + middleware registration
├── postman/
│   └── IMC-Backend-API.postman_collection.json
├── composer.json
├── phpunit.xml
├── phpstan.neon
├── .php-cs-fixer.dist.php
└── .env.example
```
