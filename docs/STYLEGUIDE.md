# Style Guide (PHP)

- Follow **PSR-12** coding style; indent with 4 spaces.
- One purpose per file. Extract repeated header/footer into `partials/` and use `templates/layout.php`.
- Avoid global HTML in controller files; set `$page_title` and `$content` then include the layout.
- Use `htmlspecialchars()` when echoing user content.
- Use prepared statements (`mysqli_prepare` / PDO) for all DB queries.
- Use `require_once` for critical includes; guard against direct access if needed.
- Name files in `snake_case` and functions in `camelCase`.
- Group routes by feature: `pinjam_gedung.php`, `pinjam_studio.php`, etc.

CSS:
- Use `assets/css/minimal.css`. Keep it under 10KB gzipped when possible.
- Prefer `.grid`, `.card`, `.table`, `.btn`, `.input` utilities.