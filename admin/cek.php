<?php
// === Pastikan koneksi ter-load dengan path absolut berbasis lokasi file ini ===
$koneksiCandidates = [
    __DIR__ . '/../koneksi.php',     // jika cek.php ada di /admin/cek.php
    __DIR__ . '/koneksi.php',        // jika cek.php dipindah satu level
    dirname(__DIR__, 2) . '/koneksi.php', // fallback lain (2 level di atas)
];
$koneksiPath = null;
foreach ($koneksiCandidates as $cand) {
    if (is_file($cand)) { $koneksiPath = $cand; break; }
}
if ($koneksiPath === null) {
    http_response_code(500);
    exit('File koneksi.php tidak ditemukan dari cek.php');
}
require_once $koneksiPath;

// === Start session dgn parameter aman (harus sebelum session_start) ===
if (session_status() === PHP_SESSION_NONE) {
    $secure  = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $domain  = $_SERVER['HTTP_HOST'] ?? '';
    // Hindari set domain manual jika berpotensi subdomain/port; biarkan default bila ragu
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        // 'domain'   => $domain, // opsional; komentar bila ada variasi subdomain/port
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// === Helper: base URL dinamis (mengikuti domain saat ini) ===
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$base   = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? '');

// === Cek login ===
if (empty($_SESSION['username']) || empty($_SESSION['level'])) {
    header("Location: {$base}/index.php?alert=not_logged_in");
    exit();
}

// === Validasi level ===
$allowed_levels = ['admin'];
if (!in_array($_SESSION['level'], $allowed_levels, true)) {
    header("Location: {$base}/index.php?alert=unauthorized_access");
    exit();
}
