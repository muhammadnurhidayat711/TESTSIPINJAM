# Refactor & Optimization Plan (Safe: no DB rename)

**Principles**
- Keep existing DB name/tables/columns EXACTLY as-is.
- Zero breaking changes to URLs unless explicitly migrated.
- Progressive enhancement: drop-in `partials/` + `templates/` without rewriting everything at once.

**Steps**
1. **Clean repo**: remove `__MACOSX` artifacts & unused assets. Convert heavy JPG/PNG to WebP.
2. **Layout extraction**: Replace inline header/footer with `partials/` and `templates/layout.php`. Start with high-traffic pages (index, login, daftar pinjam).
3. **Prepared statements**: Replace `mysqli_query()` usages flagged in `sql_findings.csv`. Focus first on queries using `$_GET`/`$_POST`.
4. **Pagination & search**: Ensure lists have server-side pagination (limit/offset) and indexed columns.
5. **Asset optimization**: Compress CSS/JS; enable HTTP caching headers for `/assets`.
6. **UX polish**: Use `.card`, `.grid`, and `.table`. Ensure forms have clear labels, required fields, and error messages.
7. **Security**: CSRF tokens for POST, stricter session cookie flags, validation & sanitization.
8. **Accessibility**: Semantic HTML, labels for inputs, focus states visible.
9. **Deploy**: Turn on gzip/Brotli, HTTP/2, and cache-control for static assets.

**Drop-in examples**
- Use `templates/layout.php` with `$content` buffering:
  ```php
  <?php ob_start(); ?>
  <h1>Daftar Peminjaman</h1>
  <div class="card">...</div>
  <?php $content = ob_get_clean(); $page_title='Daftar Peminjaman'; include __DIR__.'/templates/layout.php'; ?>
  ```