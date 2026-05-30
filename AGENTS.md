<!-- GSD:project-start source:PROJECT.md -->
## Project

**IMC Backend — User Permission Management**

A Slim PHP REST API backend for managing user access control based on roles (levels) and per-user page permissions. It provides CRUD operations for users, levels, and pages, plus a permission matrix that determines which pages each user or level can access. Built as a technical test deliverable with PostgreSQL as the database.

**Core Value:** Administrators can control exactly which pages each user or role can access, and the API reliably enforces those permissions on every request.

### Constraints

- **Tech stack**: Slim PHP (backend), PostgreSQL (database) — mandated by test spec
- **API format**: JSON for all requests and responses with consistent error format
- **Auth**: JWT token-based authentication
- **Deliverable**: Source code + database migration/seed scripts, uploaded to Git
<!-- GSD:project-end -->

<!-- GSD:stack-start source:research/STACK.md -->
## Technology Stack

## Recommended Stack
| Component | Library | Version | Confidence |
|-----------|---------|---------|------------|
| Framework | Slim Framework | 4.x | HIGH |
| DI Container | PHP-DI | 7.x | HIGH |
| JWT Library | firebase/php-jwt | 6.x | HIGH |
| Database | PostgreSQL | 15+ | HIGH |
| DBAL/ORM | illuminate/database (Eloquent) | 10.x or 11.x | MEDIUM |
| Password Hashing | PHP password_hash (bcrypt/argon2) | Built-in PHP 8.x | HIGH |
| PSR-7 Implementation | slim/psr7 | 1.x | HIGH |
| CORS | tuupola/cors-middleware | 1.x | MEDIUM |
| Validation | respect/validation | 2.x | MEDIUM |
## Key Decisions & Rationale
### Slim Framework 4.x
- **Why**: Mature, PSR-15 middleware support, excellent for REST APIs
- **Not Slim 3**: Deprecated, lacks modern PSR standards
- **Not Slim 5**: Not yet stable/released
### firebase/php-jwt vs lcobucci/jwt
- **firebase/php-jwt**: Simpler API, widely used, good for basic JWT needs
- **lcobucci/jwt**: More modern API, stricter type safety, better for complex scenarios
- **Recommendation**: firebase/php-jwt for simplicity in this test project
### illuminate/database (Eloquent)
- **Why**: Provides migrations, seeders, and query builder in one package
- **Alternative**: Raw PDO (more control, but requires building migration/seed tooling)
- **Note**: For a test project, Eloquent's migrations + seeders save significant time
### PHP-DI Container
- **Why**: Officially recommended by Slim docs, PSR-11 compliant
- **Alternative**: Container-Interop or custom container
- **Note**: Slim 4 doesn't ship with a container — must provide your own
## What NOT to Use
| Library | Why Avoid |
|---------|-----------|
| Slim 3 | Deprecated, no longer maintained |
| medz/cors | Archived repository (Feb 2023) |
| Respect/Validation | Unmaintained, consider illuminate/validation instead |
## Setup Notes
<!-- GSD:stack-end -->

<!-- GSD:conventions-start source:CONVENTIONS.md -->
## Conventions

Conventions not yet established. Will populate as patterns emerge during development.
<!-- GSD:conventions-end -->

<!-- GSD:architecture-start source:ARCHITECTURE.md -->
## Architecture

Architecture not yet mapped. Follow existing patterns found in the codebase.
<!-- GSD:architecture-end -->

<!-- GSD:skills-start source:skills/ -->
## Project Skills

No project skills found. Add skills to any of: `.claude/skills/`, `.agents/skills/`, `.cursor/skills/`, `.github/skills/`, or `.codex/skills/` with a `SKILL.md` index file.
<!-- GSD:skills-end -->

<!-- GSD:workflow-start source:GSD defaults -->
## GSD Workflow Enforcement

Before using Edit, Write, or other file-changing tools, start work through a GSD command so planning artifacts and execution context stay in sync.

Use these entry points:
- `/gsd-quick` for small fixes, doc updates, and ad-hoc tasks
- `/gsd-debug` for investigation and bug fixing
- `/gsd-execute-phase` for planned phase work

Do not make direct repo edits outside a GSD workflow unless the user explicitly asks to bypass it.
<!-- GSD:workflow-end -->



<!-- GSD:profile-start -->
## Developer Profile

> Profile not yet configured. Run `/gsd-profile-user` to generate your developer profile.
> This section is managed by `generate-claude-profile` -- do not edit manually.
<!-- GSD:profile-end -->
