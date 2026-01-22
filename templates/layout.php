<?php
// templates/layout.php
// Simple base layout to reduce duplication; include header/footer once.
if(!isset($page_title)) $page_title = "SIPINJAM";
if(!isset($content)) $content = "";
  if (session_status()===PHP_SESSION_NONE) session_start();
  if (empty($_SESSION['id_user'])) { header('Location: login.php?alert=not_logged_in'); exit; }
  $view = preg_replace('~[^a-z0-9_]~i','', $_GET['view'] ?? 'dashboard');
  $file = __DIR__."/views/{$view}.php";
  if (is_file($file)) include $file; else echo "<div class='page-inner'>Halaman tidak ditemukan.</div>";
?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($page_title) ?></title>
  <link rel="stylesheet" href="/assets/css/minimal.css">
</head>
<body>
  <?php include __DIR__ . '/../partials/header.php'; ?>
  <main class="container">
    <?= $content ?>
  </main>
  <?php include __DIR__ . '/../partials/footer.php'; ?>
  <script src="/assets/js/app.js"></script>
</body>
</html>
