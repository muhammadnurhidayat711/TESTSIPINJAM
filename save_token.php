<?php
session_start();
require_once "koneksi.php";

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    exit;
}

if (!isset($_POST['token']) || empty($_POST['token'])) {
    http_response_code(400);
    exit;
}

$user_id = $_SESSION['id'];
$token   = $_POST['token'];

mysqli_query($conn, "DELETE FROM user_tokens WHERE user_id='$user_id'");

mysqli_query(
    $conn,
    "INSERT INTO user_tokens (user_id, token, created_at)
     VALUES ('$user_id', '$token', NOW())"
);

echo "OK";
