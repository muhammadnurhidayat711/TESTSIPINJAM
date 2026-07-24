<?php
include '../koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah pengguna sudah login
if (!isset($_SESSION['username']) || !isset($_SESSION['level'])) {
	header("location:../index.php?alert=not_logged_in");
	exit();
}

// Validasi level pengguna
$allowed_levels = ['user'];
if (!in_array($_SESSION['level'], $allowed_levels)) {
	header("location:../index.php?alert=unauthorized_access");
	exit();
}
