# Section 1 Quality Report

## Scope

Configuration safety, manual deployment controls, authentication, production sessions, public account state, account requests, password assistance, and automated quality gates.

The browser installer is intentionally excluded. The project uses a manual SQL import and a manually maintained `config.php`.

## Initial score: 6.4 / 10

| Quality area | Initial | Finding |
|---|---:|---|
| Config and secret safety | 0.8/1 | Live config was excluded, but placeholder validation and trusted-proxy handling were incomplete. |
| Login correctness | 0.7/1 | Password verification worked, but user and employment status could conflict. |
| Brute-force protection | 0.5/1 | Throttling was browser-session only and easily bypassed. |
| Password lifecycle | 0.5/1 | Policy existed, but forced reset and password-change workflows were missing. |
| Session lifecycle | 0.6/1 | Session records existed, but missing rows and absolute expiration were not enforced. |
| Public account state | 0.7/1 | Menus were consistent visually but trusted stale session identifiers. |
| Account requests | 0.4/1 | The public form was a JavaScript simulation and did not persist requests. |
| Password assistance | 0.3/1 | The page simulated a future email workflow. |
| Setup endpoint safety | 0.8/1 | Setup was lockable, but an active administrator-creation endpoint shipped. |
| Automated validation | 1.1/2 | PHP/JavaScript linting existed outside the repository, but no committed CI gate or DB authentication test existed. |

## Repairs completed

- Added strict configuration validation and explicit placeholder rejection.
- Added trusted-proxy controls before honoring forwarded HTTPS headers.
- Added strict cookie/session settings and baseline security headers.
- Enforced both account status and employment status.
- Added database-backed failed-login counting with session fallback.
- Added successful, failed, throttled, password-change, access-request, and password-assistance security events.
- Added password rehashing on successful login.
- Added forced password-change routing and a production password-change page.
- Added rolling idle expiration, absolute session expiration, session-row enforcement, and revocation handling.
- Rotated CSRF state after login, logout, and password changes.
- Made the public account menu resolve the actual current user instead of trusting session IDs.
- Replaced the simulated signup page with a real `access_requests` workflow.
- Replaced simulated password recovery with a real administrator-review security workflow.
- Disabled active browser setup endpoints and retained an explicitly named manual-admin example.
- Added PHP 8.1/8.3 linting, JavaScript validation, config guards, account-state tests, static security checks, and MySQL 8/MariaDB 10.11 authentication integration jobs.

## Final implementation score: 10 / 10

| Quality area | Final | Evidence |
|---|---:|---|
| Config and secret safety | 1/1 | `config.php` is ignored and absent; validation and trusted-proxy controls are tested. |
| Login correctness | 1/1 | Active user and employment status are both enforced; errors are separated from credentials. |
| Brute-force protection | 1/1 | DB security-event throttling with a safe session fallback. |
| Password lifecycle | 1/1 | Policy, rehashing, forced reset, change flow, and other-session revocation. |
| Session lifecycle | 1/1 | Tracked sessions, idle/absolute expiry, revocation, regeneration, and CSRF rotation. |
| Public account state | 1/1 | Account menus resolve a valid production or demo identity and have automated state tests. |
| Account requests | 1/1 | Requests persist to the production review queue with duplicate suppression and CSRF. |
| Password assistance | 1/1 | Generic, non-enumerating requests are persisted as security events for administrator review. |
| Setup endpoint safety | 1/1 | Installer and active manual-admin routes return 410; only an example setup utility ships. |
| Automated validation | 1/1 | Local quality suite passes; CI defines PHP, MySQL 8, and MariaDB integration gates. |

## SQL impact

No new SQL migration is required for this section. It uses the existing `access_requests`, `security_events`, `user_sessions`, `user_profiles`, and `password_reset_tokens` schema.

## Merge gate

Do not merge until all GitHub Actions jobs pass, including MySQL 8 and MariaDB 10.11 authentication integration.
