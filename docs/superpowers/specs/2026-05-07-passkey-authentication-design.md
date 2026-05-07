# Passkey Authentication — Design Spec

**Date:** 2026-05-07
**Status:** Approved

## Overview

Add passkey (WebAuthn discoverable credential) authentication to Taskman using `spatie/laravel-passkeys`. Passkeys become the primary login path; passwords remain a fallback. Users can register multiple named passkeys per account, and at login no username entry is required — the browser presents a credential picker automatically.

---

## Architecture & Data Layer

**Package:** `spatie/laravel-passkeys` owns all WebAuthn cryptography — challenge generation, attestation verification, assertion verification. The app owns the UI and flow logic built on top.

**Database — `passkeys` table (added by package migration):**

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `user_id` | bigint FK | references `users.id` |
| `name` | string | user-supplied label, e.g. "Work MacBook" |
| `credential_id` | binary | unique identifier for the key |
| `public_key` | text | COSE-encoded public key |
| `sign_count` | unsignedInt | replay-attack counter |
| `transports` | json | e.g. `["internal"]`, `["usb"]` |
| `created_at`, `updated_at` | timestamps | |

No changes to the `users` table.

**User model:** Gains the `HasPasskeys` trait, which adds a `passkeys()` hasMany relationship.

**Routes (6 total):**

| Method | URI | Auth required | Purpose |
|---|---|---|---|
| POST | `/passkeys/register/options` | Yes | Generate registration challenge |
| POST | `/passkeys/register` | Yes | Verify and store new passkey |
| DELETE | `/passkeys/{passkey}` | Yes | Remove a passkey (scoped to own user) |
| POST | `/passkeys/authenticate/options` | No | Generate authentication challenge |
| POST | `/passkeys/authenticate` | No | Verify assertion, create session |
| GET | `/settings/passkeys` | Yes | Passkey management UI |

---

## Registration Flow (Settings)

**Location:** New "Passkeys" tab in the Settings sidebar alongside Profile, Password, and Appearance.

**UI elements:**
- List of registered passkeys: name, transport icon, date added, "Remove" button
- "Add passkey" button
- Confirmation modal before removal

**Sequence:**
1. User clicks "Add passkey" and is prompted to name it (e.g. "Work MacBook")
2. JS posts to `POST /passkeys/register/options` → receives server challenge
3. JS calls `navigator.credentials.create()` with `residentKey: "required"` (enforces discoverability)
4. OS biometric/PIN prompt shown to user
5. JS posts signed credential + name to `POST /passkeys/register`
6. Livewire refreshes the passkey list

**Edge case — last passkey removal:** If a user attempts to delete their final passkey and `hasPassword()` returns false, the deletion is blocked with an explanatory message. Users with a password set may always delete any passkey.

---

## Login Flow

**What changes:** A "Sign in with a passkey" button is added to the login page below the existing email/password form, alongside the SSO buttons. The email field is not required.

**Sequence:**
1. User clicks "Sign in with a passkey"
2. JS posts to `POST /passkeys/authenticate/options` with empty `allowCredentials` (discoverable flow)
3. Browser shows OS passkey picker listing all site passkeys on the device
4. User selects and authenticates with biometrics/PIN
5. JS posts signed assertion to `POST /passkeys/authenticate`
6. Server verifies signature, increments `sign_count`, resolves user via `credential_id`, creates session
7. User is redirected to `/dashboard`

**Rate limiting:** The authenticate endpoint uses the same 5-attempt throttle as password login, keyed by IP.

**Future enhancement — Conditional UI:** When `mediation: "conditional"` is added to `navigator.credentials.get()` and the email field carries `autocomplete="username webauthn"`, passkeys surface inline in the browser's autofill dropdown without requiring a button tap. This is a progressive enhancement deferred until the core flow is stable.

**Fallback:** Password and SSO login remain unchanged. The passkey button is purely additive.

---

## Livewire Components

### New: `App\Livewire\Settings\Passkeys`

| Method | Purpose |
|---|---|
| `mount()` | Load `auth()->user()->passkeys` ordered by `created_at` desc |
| `addPasskey(string $name)` | Fetch register options, dispatch challenge to JS |
| `confirmPasskey(array $credential)` | Receive signed credential from JS, store, refresh list |
| `removePasskey(int $id)` | Delete with ownership check; block if last passkey and no password |

### Modified: `App\Livewire\Auth\Login`

| Method | Purpose |
|---|---|
| `authenticateWithPasskey()` | Fetch authenticate options, dispatch challenge to JS |
| `confirmPasskeyAuth(array $credential)` | Receive signed assertion, verify, create session, redirect |

---

## JavaScript Layer

**File:** `resources/js/passkeys.js` — a single Alpine component registered globally in `app.js`.

**Responsibilities:**
- Encode/decode between base64url (server) and ArrayBuffer (WebAuthn API)
- `register(challenge)` — wraps `navigator.credentials.create()`, returns encoded credential
- `authenticate(challenge)` — wraps `navigator.credentials.get()` with empty `allowCredentials`, returns encoded assertion
- Handle `NotAllowedError` (user cancelled) and `InvalidStateError` (key already registered) with user-friendly Flux toast messages

Referenced as `x-data="passkeys"` on the relevant form sections. No other file touches ArrayBuffer encoding.

---

## Testing

**File:** `tests/Feature/Auth/PasskeyTest.php`

**Coverage:**
- Registration options endpoint returns a valid challenge when authenticated
- Registration stores a passkey with the correct name and user association
- Registration rejects a duplicate credential ID
- Unauthenticated users cannot access registration endpoints
- Authentication options returns a challenge (public endpoint)
- Authentication with a valid assertion creates a session and redirects to dashboard
- Authentication with an invalid/tampered assertion is rejected
- Deleting a passkey removes it from the database
- Deleting another user's passkey returns 403
- Deleting the last passkey when no password is set is blocked

**Mocking:** Use `Passkeys::fake()` if the package provides it; otherwise mock the underlying service class at the container level. No real crypto in tests. No Dusk tests — the WebAuthn browser API cannot be exercised in headless environments without an authenticator emulator.

---

## Out of Scope

- Enforcing passkey-only login for accounts that have set one up (phase 2 — password removal)
- Conditional UI / autofill passkey surfacing (deferred progressive enhancement)
- Attestation policy enforcement (e.g. requiring platform authenticators only)
