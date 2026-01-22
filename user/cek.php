<?php
include '../koneksi.php';
session_start();

// Cek apakah pengguna sudah login
if (!isset($_SESSION['username']) || !isset($_SESSION['level'])) {
	// Jika belum login, redirect ke halaman login dengan pesan alert
	header("location:https://sipinjam.pelitacemerlangschool.sch.id/index.php?alert=not_logged_in");
	exit();
}

// Validasi level pengguna
$allowed_levels = ['user'];
if (!in_array($_SESSION['level'], $allowed_levels)) {
	// Jika level tidak sesuai, redirect ke halaman login dengan pesan alert
	header("location:https://sipinjam.pelitacemerlangschool.sch.id/index.php?alert=unauthorized_access");
	exit();
}
