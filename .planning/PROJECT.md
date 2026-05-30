# IMC Backend — User Permission Management

## What This Is

A Slim PHP REST API backend for managing user access control based on roles (levels) and per-user page permissions. It provides CRUD operations for users, levels, and pages, plus a permission matrix that determines which pages each user or level can access. Built as a technical test deliverable with PostgreSQL as the database.

## Core Value

Administrators can control exactly which pages each user or role can access, and the API reliably enforces those permissions on every request.

## Requirements

### Validated

(None yet — ship to validate)

### Active

- [ ] **AUTH-01**: User can register/login with username or email and password
- [ ] **AUTH-02**: Password is stored as a hash (never plain text)
- [ ] **AUTH-03**: Login returns a JWT token for subsequent API requests
- [ ] **AUTH-04**: Inactive users cannot login
- [ ] **AUTH-05**: Username and email must be unique across all users
- [ ] **USER-01**: Admin can create, read, update, and delete users
- [ ] **USER-02**: User has fields: nama lengkap, username, email, password, level, status aktif/nonaktif
- [ ] **LEVEL-01**: Admin can create, read, update, and delete levels
- [ ] **LEVEL-02**: Level has fields: nama level, deskripsi, status aktif/nonaktif
- [ ] **LEVEL-03**: Level in use by users cannot be hard-deleted (soft delete or block)
- [ ] **PAGE-01**: Admin can create, read, update, and delete pages/menus
- [ ] **PAGE-02**: Page has fields: nama page, route/path (unique), deskripsi, urutan tampil, status aktif/nonaktif
- [ ] **PERM-01**: Admin can assign page access to a level (level-based permission)
- [ ] **PERM-02**: Admin can assign additional or exception page access to a specific user (user-level override)
- [ ] **PERM-03**: API provides permission matrix endpoint (checkbox-style per page per level/user)
- [ ] **PERM-04**: Permission changes take effect on next login or token refresh
- [ ] **MW-01**: JWT middleware protects all authenticated endpoints
- [ ] **MW-02**: Permission middleware denies API access when user lacks page permission
- [ ] **DB-01**: PostgreSQL schema with proper tables, relations, constraints, and indexes
- [ ] **DB-02**: Seed data for initial levels, pages, and a default admin user
- [ ] **API-01**: All requests and responses use JSON with consistent error format

### Out of Scope

- Angular 20 frontend — backend API only per user request
- UI/UX design — deliverables are source code, DB scripts, and API
- Mobile app support — web API only

## Context

This is a technical test (IMC) deliverable. The full original spec includes an Angular 20 frontend, but the user requested **backend only** (Slim PHP + PostgreSQL). The API must be well-structured with clear middleware for auth and permission checks. Evaluation focuses on code quality, access control logic, validation, and basic security.

## Constraints

- **Tech stack**: Slim PHP (backend), PostgreSQL (database) — mandated by test spec
- **API format**: JSON for all requests and responses with consistent error format
- **Auth**: JWT token-based authentication
- **Deliverable**: Source code + database migration/seed scripts, uploaded to Git

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| JWT for authentication | Stateless, easy to test with API clients, standard for REST | — Pending |
| RESTful JSON API | Matches test spec requirement, simple and testable | — Pending |
| Backend only (no Angular) | User explicitly requested BE-only scope | — Pending |
| Soft delete for levels | Prevents data integrity issues when level has associated users | — Pending |

## Evolution

This document evolves at phase transitions and milestone boundaries.

**After each phase transition** (via `/gsd-transition`):
1. Requirements invalidated? → Move to Out of Scope with reason
2. Requirements validated? → Move to Validated with phase reference
3. New requirements emerged? → Add to Active
4. Decisions to log? → Add to Key Decisions
5. "What This Is" still accurate? → Update if drifted

**After each milestone** (via `/gsd-complete-milestone`):
1. Full review of all sections
2. Core Value check — still the right priority?
3. Audit Out of Scope — reasons still valid?
4. Update Context with current state

---
*Last updated: 2026-05-30 after initialization*
