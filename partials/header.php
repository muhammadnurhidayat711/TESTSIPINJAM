<?php // partials/header.php (versi minimal, tanpa menu)
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<nav class="navbar" style="border-bottom: 1px solid var(--border); background: rgba(255,255,255,.75); backdrop-filter: saturate(160%) blur(10px);">
  <a href="/index.php" class="logo" style="font-weight:800; letter-spacing:.2px; text-decoration:none; color:var(--fg);">SIPINJAM</a>
  <span style="flex:1"></span>

  <?php if (!empty($_SESSION['username'])): ?>
    <span class="badge" style="margin-right:8px;">Halo, <?= htmlspecialchars($_SESSION['username']) ?></span>
    <a class="btn" href="/logout.php" data-confirm="Keluar dari sesi?">Keluar</a>
  <?php else: ?>
    <a class="btn" href="/index.php">Masuk</a>
  <?php endif; ?>
</nav>
