# Roadmap: IMC Backend — User Permission Management API

## Overview

Build a Slim PHP REST API backend for managing user access control based on roles (levels) and per-user page permissions. The project progresses from foundation (database, authentication, JWT, clean architecture, Docker) through core CRUD operations (users, levels, pages) with testing, to the permission system (level-based permissions, user overrides, enforcement middleware, rate limiting, token refresh). Each phase delivers a complete, independently verifiable capability.

Beyond the baseline spec, this project demonstrates senior-level engineering: clean architecture, repository pattern, PHPUnit testing, API pagination/filtering/rate-limiting, token refresh, Docker Compose, and Postman collection.

## Phases

**Phase Numbering:**

- Integer phases (1, 2, 3): Planned milestone work
- Decimal phases (2.1, 2.2): Urgent insertions (marked with INSERTED)

Decimal phases appear between their surrounding integers in numeric order.

- [ ] **Phase 1: Foundation & Authentication** — Project skeleton, Docker, database, clean architecture, JWT auth, error handling, pagination
- [ ] **Phase 2: CRUD Operations** — Full CRUD for users/levels/pages, input validation, filtering, PHPUnit tests, Postman collection
- [ ] **Phase 3: Permission System** — Level-based permissions, user overrides, permission matrix, enforcement middleware, rate limiting, token refresh

## Phase Details

### Phase 1: Foundation & Authentication

**Goal**: Production-ready project skeleton with Docker, PostgreSQL, clean architecture, JWT auth, and consistent API responses
**Mode:** mvp
**Depends on**: Nothing (first phase)
**Requirements**: AUTH-01, AUTH-02, AUTH-03, AUTH-04, AUTH-05, AUTH-06, MW-01, MW-02, API-01, API-02, DB-01, DB-02, DB-03, ARCH-01, ARCH-02, ARCH-03, ARCH-04, API-06, DX-01
**Success Criteria** (what must be TRUE):

  1. `docker compose up -d` starts the full app (PHP 8.1 + PostgreSQL 15) without errors
  2. Admin can login with username or email and password, receiving a valid JWT token
  3. Inactive users (is_active = false) are blocked from logging in
  4. Duplicate username or email is rejected
  5. All API responses use consistent JSON format with structured error objects
  6. Protected endpoints reject requests without a valid JWT token (401)
  7. Code follows clean architecture: Application → Domain → Infrastructure with strict types
  8. Repository interfaces defined for all entities
  9. Pagination helper ready (usable by Phase 2 list endpoints)

Plans:
**Wave 1**

- [ ] 01-01: Docker Compose, Slim 4 skeleton, PHP-DI container, .env config
- [ ] 01-02: Database schema, migrations, and seed data (levels, pages, default admin)

**Wave 2** *(blocked on Wave 1 completion)*

- [ ] 01-03: Clean architecture structure (Application/Domain/Infrastructure) + base classes
- [ ] 01-04: Auth endpoints (login), password hashing, JWT generation and validation middleware

**Wave 3** *(blocked on Wave 2 completion)*

- [ ] 01-05: Consistent JSON error format, exception hierarchy, base response helpers
- [ ] 01-06: Pagination utility (usable by all Phase 2 list endpoints)

### Phase 2: CRUD Operations

**Goal**: Admin can fully manage users, levels, and pages with proper validation, filtering, PHPUnit tests, and Postman collection
**Mode:** mvp
**Depends on**: Phase 1
**Requirements**: USER-01, USER-02, USER-03, USER-04, USER-05, USER-06, LEVEL-01, LEVEL-02, LEVEL-03, LEVEL-04, LEVEL-05, LEVEL-06, LEVEL-07, PAGE-01, PAGE-02, PAGE-03, PAGE-04, PAGE-05, PAGE-06, PAGE-07, API-03, API-07, TEST-01, TEST-02, DX-02
**Success Criteria** (what must be TRUE):

  1. Admin can create, view (list/single), update, and delete users with all required fields
  2. Admin can create, view, update, and delete levels; deletion returns clear error when users reference the level
  3. Admin can create, view, update, and delete pages; duplicate routes are rejected
  4. All list endpoints support pagination and filtering (?search=, ?is_active=)
  5. All create/update endpoints reject invalid input with structured validation errors
  6. PHPUnit integration tests cover all CRUD endpoints
  7. PHPUnit unit tests cover domain logic (validation, permission resolution)
  8. Postman collection includes all API endpoints with example requests

Plans:

- [ ] 02-01: Level CRUD endpoints with delete-protection logic + filtering
- [ ] 02-02: User CRUD endpoints with unique username/email constraints + filtering
- [ ] 02-03: Page CRUD endpoints with unique route constraint + filtering
- [ ] 02-04: Input validation middleware applied to all create/update endpoints
- [ ] 02-05: PHPUnit integration tests for all CRUD endpoints
- [ ] 02-06: PHPUnit unit tests for domain logic
- [ ] 02-07: Postman collection (all endpoints)

### Phase 3: Permission System

**Goal**: Admin can control page access per level and per user, API enforces permissions, with rate limiting and token refresh
**Mode:** mvp
**Depends on**: Phase 2
**Requirements**: PERM-01, PERM-02, PERM-03, PERM-04, PERM-05, PERM-06, PERM-07, MW-03, MW-04, API-08, API-09
**Success Criteria** (what must be TRUE):

  1. Admin can assign and remove page access for a level (level-based permissions)
  2. Admin can grant, deny, and remove user-specific permission overrides (beyond their level)
  3. API provides a permission matrix endpoint returning checkbox-style permission data per level/user
  4. Permission middleware denies access (403) when authenticated user lacks page permission
  5. Permission changes take effect on next request (permissions checked in DB, not JWT)
  6. Login endpoint rate-limited: max 5 attempts per minute per IP (429 on exceed)
  7. Token refresh endpoint works: short-lived access token (15min) + refresh token (7d)

Plans:
**Wave 1**

- [ ] 03-01: Level permission endpoints (assign/remove page access per level)

**Wave 2** *(blocked on Wave 1)*

- [ ] 03-02: User permission override endpoints (grant/deny/remove per user)

**Wave 3** *(blocked on Wave 1)*

- [ ] 03-03: Permission matrix endpoint (aggregated view per level/user)

**Wave 4** *(blocked on Wave 1)*

- [ ] 03-04: Permission middleware integrated with route protection

**Wave 5** *(blocked on Wave 1)*

- [ ] 03-05: Rate limiting middleware on login endpoint

**Wave 6** *(blocked on Wave 1)*

- [ ] 03-06: Token refresh endpoint (access + refresh token architecture)

## Progress

**Execution Order:**
Phases execute in numeric order: 1 → 2 → 3

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Foundation & Authentication | 0/6 | Not started | - |
| 2. CRUD Operations | 0/7 | Not started | - |
| 3. Permission System | 0/6 | Not started | - |
