<?php
header('Content-Type: application/json');

error_reporting(0);

// Start session dan koneksi database
session_start();
include 'koneksi.php';

// Ambil raw input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Log untuk debugging
error_log("Save Token Request: " . print_r($data, true));

// Validasi input
if (!isset($data['user_id']) || !isset($data['token'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Missing user_id or token',
        'received' => $data
    ]);
    exit;
}

$userId = intval($data['user_id']);
$token = mysqli_real_escape_string($conn, $data['token']);

// Validasi user_id
if ($userId <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid user_id'
    ]);
    exit;
}

// Cek apakah token sudah ada untuk user ini
$checkQuery = "SELECT id FROM fcm_tokens WHERE user_id = ?";
$stmt = $conn->prepare($checkQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Update token yang sudah ada
    $updateQuery = "UPDATE fcm_tokens SET token = ?, updated_at = NOW() WHERE user_id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("si", $token, $userId);
} else {
    // Insert token baru
    $insertQuery = "INSERT INTO fcm_tokens (user_id, token, created_at, updated_at) VALUES (?, ?, NOW(), NOW())";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("is", $userId, $token);
}

if ($stmt->execute()) {
    http_response_code(200);
    echo json_encode([
        'success' => true, 
        'message' => 'Token saved successfully',
        'user_id' => $userId
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>
