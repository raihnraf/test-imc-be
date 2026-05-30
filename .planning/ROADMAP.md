# Roadmap: IMC Backend — User Permission Management API

## Overview

Build a Slim PHP REST API backend for managing user access control based on roles (levels) and per-user page permissions. The project progresses from foundation (database, authentication, JWT) through core CRUD operations (users, levels, pages) to the permission system (level-based permissions, user overrides, enforcement middleware). Each phase delivers a complete, independently verifiable capability.

## Phases

**Phase Numbering:**
- Integer phases (1, 2, 3): Planned milestone work
- Decimal phases (2.1, 2.2): Urgent insertions (marked with INSERTED)

Decimal phases appear between their surrounding integers in numeric order.

- [ ] **Phase 1: Foundation & Authentication** - Database setup, user login with JWT, and protected API skeleton
- [ ] **Phase 2: CRUD Operations** - Full create, read, update, delete for users, levels, and pages
- [ ] **Phase 3: Permission System** - Level-based permissions, user overrides, permission matrix, and enforcement middleware

## Phase Details

### Phase 1: Foundation & Authentication
**Goal**: Project skeleton with PostgreSQL database, user authentication via JWT, and consistent API error handling
**Mode:** mvp
**Depends on**: Nothing (first phase)
**Requirements**: AUTH-01, AUTH-02, AUTH-03, AUTH-04, AUTH-05, AUTH-06, MW-01, MW-02, API-01, API-02, DB-01, DB-02, DB-03
**Success Criteria** (what must be TRUE):
  1. Admin can login with username or email and password, receiving a valid JWT token
  2. Inactive users (is_active = false) are blocked from logging in
  3. Duplicate username or email is rejected during login/registration
  4. All API responses use consistent JSON format with structured error objects
  5. Protected endpoints reject requests without a valid JWT token (401)
**Plans**: TBD

Plans:
- [ ] 01-01: Project skeleton, Slim setup, PostgreSQL connection, migration/seed infrastructure
- [ ] 01-02: Database schema, migrations, and seed data (levels, pages, default admin)
- [ ] 01-03: Auth endpoints (login), password hashing, JWT generation, JWT middleware
- [ ] 01-04: Consistent JSON error format and input validation middleware

### Phase 2: CRUD Operations
**Goal**: Admin can fully manage users, levels, and pages with proper validation and delete protection
**Mode:** mvp
**Depends on**: Phase 1
**Requirements**: USER-01, USER-02, USER-03, USER-04, USER-05, USER-06, LEVEL-01, LEVEL-02, LEVEL-03, LEVEL-04, LEVEL-05, LEVEL-06, LEVEL-07, PAGE-01, PAGE-02, PAGE-03, PAGE-04, PAGE-05, PAGE-06, PAGE-07, API-03
**Success Criteria** (what must be TRUE):
  1. Admin can create, view (list/single), update, and delete users with all required fields
  2. Admin can create, view, update, and delete levels; deletion returns clear error when users reference the level
  3. Admin can create, view, update, and delete pages; duplicate routes are rejected
  4. All create/update endpoints reject invalid or missing input with structured validation errors
**Plans**: TBD

Plans:
- [ ] 02-01: Level CRUD endpoints with delete-protection logic
- [ ] 02-02: User CRUD endpoints with unique username/email constraints
- [ ] 02-03: Page CRUD endpoints with unique route constraint
- [ ] 02-04: Input validation middleware applied to all create/update endpoints

### Phase 3: Permission System
**Goal**: Admin can control page access per level and per user, and the API enforces permissions on every request
**Mode:** mvp
**Depends on**: Phase 2
**Requirements**: PERM-01, PERM-02, PERM-03, PERM-04, PERM-05, PERM-06, PERM-07, MW-03, MW-04
**Success Criteria** (what must be TRUE):
  1. Admin can assign and remove page access for a level (level-based permissions)
  2. Admin can grant, deny, and remove user-specific permission overrides (beyond their level)
  3. API provides a permission matrix endpoint returning checkbox-style permission data per level/user
  4. Permission middleware denies access (403) when authenticated user lacks page permission
  5. Permission changes take effect on next login or token refresh (permissions not baked into JWT)
**Plans**: TBD

Plans:
- [ ] 03-01: Level permission endpoints (assign/remove page access per level)
- [ ] 03-02: User permission override endpoints (grant/deny/remove per user)
- [ ] 03-03: Permission matrix endpoint (aggregated view per level/user)
- [ ] 03-04: Permission middleware integrated with route protection

## Progress

**Execution Order:**
Phases execute in numeric order: 1 → 2 → 3

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Foundation & Authentication | 0/4 | Not started | - |
| 2. CRUD Operations | 0/4 | Not started | - |
| 3. Permission System | 0/4 | Not started | - |
