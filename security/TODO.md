# Security TODOs
- Replace all `mysqli_query()` with prepared statements.
- Validate and cast all `$_GET['id']` to integer before use.
- Add CSRF token to all forms:
  - Generate token in session.
  - Include hidden input.
  - Verify on POST; rotate token on login.
- Use `password_hash()` / `password_verify()` for passwords.
- Set session cookie flags: `session.cookie_httponly=1`, `session.cookie_secure=1` (if HTTPS), `SameSite=Lax`.
- Deny direct access to `partials/` and `templates/` via `.htaccess` (if using Apache).