# Changelog

A brief summary of notable changes, drawn from the git history. Weekly
dependency bumps ("version bumps", "chore: dependency updates") are not listed
individually.

## Unreleased (since v0.2.0)

### 2026-10
- CI now runs Rector and Larastan (PHPStan) alongside Pint (#35).
- Fixed authorization tests for Livewire 4.4.7's 404 handling (#34).
- Added Sign in with Apple via Socialite; off unless enabled in config (#36).

### 2026-09
- Fixed the findings from the Fable 5.1 security review (#31).
- Serve `security.txt` from a route so it cannot silently expire (#27), with the
  contact read from config rather than hard-coded (#33).
- Run Enhance's node installer before the server-side npm build.

### 2026-07
- Added `.well-known/security.txt` and a referrer meta tag on all HTML heads.
- Security-related dependency updates.

### 2026-05
- **Passkeys:** passkey registration, sign-in and a Settings > Passkeys page,
  built on `spatie/laravel-passkeys`.
- Fixed SSO sign-in returning the wrong value for returning users.

### 2026-04
- Upgraded Livewire 3 to 4 and Vite 7 to 8.

### 2026-03
- Upgraded Laravel 12 to 13.
- Fixed a high-severity rollup vulnerability.
- Added Renovate for automated dependency management (Feb).

### 2026-02
- Added Google Analytics and the Nightshift configuration.
- Security-related dependency updates.

### 2026-01
- Hosting moved to FXRM; web server layout relocated.
- Added SEO meta tags and a sitemap.
- Tightened GitHub Actions workflow permissions (code scanning alert #1).

## Late 2025
- Dependency updates, including Pest upgrade and Vite/axios/form-data security
  bumps (Dependabot).

## March 2025 (after v0.2.0)
- Added Discord sign-in.
- **API v1** added (#4); HTML purification of user content (#3).
- Large test suite: architectural/security tests, Livewire component tests
  (TodoList, TaskBackupManager, LocaleMenu, DeleteUserForm, Logout, Appearance).
- Tooling: Pint formatting enforced, Larastan, parallel tests, type coverage,
  `SECURITY.md`.
- Fixed export/backup handling and a 500 error; new icon and logo.

## [v0.2.0] - 2025-03-17
- Replaced WorkOS with our own Socialite-based authentication.
- Added password reset, nullable avatars and dashboard redirect fixes.

## [v0.1.0] - 2025-03-15
- Initial public release: task list with priority filtering and search,
  backup and restore with de-duplication, landing page, responsive UI and
  Flux components, localisation, confirm-delete, home page metrics.
- Zero-downtime deploys, GitHub Actions, Pest tests and project licence.

[v0.2.0]: #v020---2025-03-17
[v0.1.0]: #v010---2025-03-15
