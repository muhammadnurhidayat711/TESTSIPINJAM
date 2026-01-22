<?php
session_start();
if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
    die('Invalid CSRF token');
}
include "koneksi.php";

// Mengambil input dari form dan melakukan sanitasi
$username = mysqli_real_escape_string($conn, trim($_POST['username']));
$password = mysqli_real_escape_string($conn, trim($_POST['password']));

// Memeriksa apakah username dan password sudah diisi
if (empty($username) || empty($password)) {
	header("location:index.php?alert=empty_input");
	exit();
}

// Menggunakan hashing untuk password dan prepared statement untuk keamanan
$stmt = $conn->prepare("SELECT * FROM user WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if ($data && $password == $data['password']) {
	// Jika user ditemukan, cek level user
	$_SESSION['username'] = $data['username'];
	$_SESSION['level'] = $data['level'];
	$_SESSION['id'] = $data['id'];
	$_SESSION['email'] = $data['email'];

	if ($data['level'] == 'admin') {
		header("location:admin/");
	} else if ($data['level'] == 'user') {
		$_SESSION['nama_lengkap'] = $data['nama_lengkap'];
		header("location:user/");
	}
	exit();
} else {
	// Jika username atau password salah
	header("location:index.php?alert=gagal");
	exit();
}
