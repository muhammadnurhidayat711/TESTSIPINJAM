<?php
session_start();
include 'koneksi.php';

// Hapus FCM token milik user yang logout
if (isset($_SESSION['id'])) {
    $stmt = $conn->prepare("DELETE FROM fcm_tokens WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['id']);
    $stmt->execute();
    $stmt->close();
}

$_SESSION = array();
session_destroy();
header("location:index.php");
exit();
?>
