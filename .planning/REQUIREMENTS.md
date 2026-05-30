# Requirements: IMC Backend — User Permission Management

**Defined:** 2026-05-30
**Core Value:** Administrators can control exactly which pages each user or role can access, and the API reliably enforces those permissions on every request.

## v1 Requirements

### Authentication

- [ ] **AUTH-01**: User can login with username or email and password
- [ ] **AUTH-02**: Login returns a JWT token for subsequent API requests
- [ ] **AUTH-03**: Password is stored as a hash (bcrypt or argon2id), never plain text
- [ ] **AUTH-04**: Inactive users (is_active = false) cannot login
- [ ] **AUTH-05**: Username must be unique across all users
- [ ] **AUTH-06**: Email must be unique across all users

### User Management

- [ ] **USER-01**: Admin can create a new user with all required fields
- [ ] **USER-02**: Admin can view a list of all users
- [ ] **USER-03**: Admin can view a single user by ID
- [ ] **USER-04**: Admin can update user details (name, email, level, status)
- [ ] **USER-05**: Admin can delete a user
- [ ] **USER-06**: User fields include: nama lengkap, username, email, password, level_id, is_active

### Level Management

- [ ] **LEVEL-01**: Admin can create a new level
- [ ] **LEVEL-02**: Admin can view a list of all levels
- [ ] **LEVEL-03**: Admin can view a single level by ID
- [ ] **LEVEL-04**: Admin can update level details (name, description, status)
- [ ] **LEVEL-05**: Admin can delete a level
- [ ] **LEVEL-06**: Level cannot be deleted if still referenced by users (return clear error)
- [ ] **LEVEL-07**: Level fields include: nama level, deskripsi, is_active

### Page Management

- [ ] **PAGE-01**: Admin can create a new page/menu
- [ ] **PAGE-02**: Admin can view a list of all pages
- [ ] **PAGE-03**: Admin can view a single page by ID
- [ ] **PAGE-04**: Admin can update page details (name, route, description, sort order, status)
- [ ] **PAGE-05**: Admin can delete a page
- [ ] **PAGE-06**: Page route/path must be unique
- [ ] **PAGE-07**: Page fields include: nama page, route/path, deskripsi, urutan tampil, is_active

### Permission Management

- [ ] **PERM-01**: Admin can assign page access to a level (level-based permission)
- [ ] **PERM-02**: Admin can remove page access from a level
- [ ] **PERM-03**: Admin can grant additional page access to a specific user (beyond their level)
- [ ] **PERM-04**: Admin can deny page access to a specific user (override their level's access)
- [ ] **PERM-05**: Admin can remove user-specific permission override
- [ ] **PERM-06**: API provides permission matrix endpoint (all pages with checkbox status per level/user)
- [ ] **PERM-07**: Permission changes take effect on next login or token refresh

### Middleware & Security

- [ ] **MW-01**: JWT middleware validates token on all protected endpoints
- [ ] **MW-02**: JWT middleware rejects expired, invalid, or missing tokens with 401
- [ ] **MW-03**: Permission middleware checks user's effective permission before allowing page access
- [ ] **MW-04**: Permission middleware returns 403 when user lacks permission
- [ ] **API-01**: All requests and responses use JSON format
- [ ] **API-02**: Consistent error response format: {statusCode, error: {type, description}}
- [ ] **API-03**: Input validation on all create/update endpoints with structured error responses

### Database

- [ ] **DB-01**: PostgreSQL schema with proper tables, relations, constraints, and indexes
- [ ] **DB-02**: Migration scripts to create all tables
- [ ] **DB-03**: Seed data: default levels (Super Admin, Manager, Staff, Viewer), sample pages, default admin user

## v2 Requirements

(Deferred — not in current scope)

### Notifications

- **NOTF-01**: Audit logging for permission changes
- **NOTF-02**: Login attempt logging (success/failure)

### Advanced Features

- **ADV-01**: Token refresh endpoint
- **ADV-02**: Password reset via email link
- **ADV-03**: Soft delete for users and levels

## Out of Scope

| Feature | Reason |
|---------|--------|
| Angular 20 frontend | User requested backend API only |
| OAuth/Social login | Not in spec, username/email + password sufficient |
| Real-time features (WebSockets) | Not required by spec |
| File uploads | Not mentioned in spec |
| Mobile app support | Web API only |
| Multi-tenant support | Not in spec, adds significant complexity |

## Traceability

| Requirement | Phase | Status |
|-------------|-------|--------|
| AUTH-01 | Phase 1 | Pending |
| AUTH-02 | Phase 1 | Pending |
| AUTH-03 | Phase 1 | Pending |
| AUTH-04 | Phase 1 | Pending |
| AUTH-05 | Phase 1 | Pending |
| AUTH-06 | Phase 1 | Pending |
| USER-01 | Phase 2 | Pending |
| USER-02 | Phase 2 | Pending |
| USER-03 | Phase 2 | Pending |
| USER-04 | Phase 2 | Pending |
| USER-05 | Phase 2 | Pending |
| USER-06 | Phase 2 | Pending |
| LEVEL-01 | Phase 2 | Pending |
| LEVEL-02 | Phase 2 | Pending |
| LEVEL-03 | Phase 2 | Pending |
| LEVEL-04 | Phase 2 | Pending |
| LEVEL-05 | Phase 2 | Pending |
| LEVEL-06 | Phase 2 | Pending |
| LEVEL-07 | Phase 2 | Pending |
| PAGE-01 | Phase 2 | Pending |
| PAGE-02 | Phase 2 | Pending |
| PAGE-03 | Phase 2 | Pending |
| PAGE-04 | Phase 2 | Pending |
| PAGE-05 | Phase 2 | Pending |
| PAGE-06 | Phase 2 | Pending |
| PAGE-07 | Phase 2 | Pending |
| PERM-01 | Phase 3 | Pending |
| PERM-02 | Phase 3 | Pending |
| PERM-03 | Phase 3 | Pending |
| PERM-04 | Phase 3 | Pending |
| PERM-05 | Phase 3 | Pending |
| PERM-06 | Phase 3 | Pending |
| PERM-07 | Phase 3 | Pending |
| MW-01 | Phase 1 | Pending |
| MW-02 | Phase 1 | Pending |
| MW-03 | Phase 3 | Pending |
| MW-04 | Phase 3 | Pending |
| API-01 | Phase 1 | Pending |
| API-02 | Phase 1 | Pending |
| API-03 | Phase 2 | Pending |
| DB-01 | Phase 1 | Pending |
| DB-02 | Phase 1 | Pending |
| DB-03 | Phase 1 | Pending |

**Coverage:**
- v1 requirements: 43 total
- Mapped to phases: 43
- Unmapped: 0 ✓

---
*Requirements defined: 2026-05-30*
*Last updated: 2026-05-30 after initial definition*
