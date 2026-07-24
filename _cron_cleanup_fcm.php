<?php
/**
 * Cron — Hapus FCM token stale (not registered, expired >30 hari)
 * Jadwalkan: 0 3 * * 0 php /path/_cron_cleanup_fcm.php
 */
require_once __DIR__ . '/koneksi.php';

// Hapus token yg tidak diupdate >30 hari
$stmt = $conn->prepare("DELETE FROM fcm_tokens WHERE updated_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
$stmt->execute();
$hapus = $stmt->affected_rows;
$stmt->close();

echo date('Y-m-d H:i:s') . " — Cleanup: $hapus stale tokens removed.\n";
