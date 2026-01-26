<?php
/**
 * Test FCM V1 API - Improved Version
 * Dengan opsi manual test menggunakan token custom
 */

include 'koneksi.php';
require_once 'fcm_helper.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test FCM V1 API</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { 
            font-size: 28px; 
            margin-bottom: 10px;
            font-weight: 700;
        }
        .header p { 
            opacity: 0.9; 
            font-size: 14px;
        }
        .content { padding: 30px; }
        .section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .section h3 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 18px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label {
            font-weight: 600;
            color: #495057;
        }
        .info-value {
            color: #6c757d;
            text-align: right;
            max-width: 60%;
            word-break: break-all;
        }
        .status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        .status.success {
            background: #d4edda;
            color: #155724;
        }
        .status.error {
            background: #f8d7da;
            color: #721c24;
        }
        .status.warning {
            background: #fff3cd;
            color: #856404;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: transform 0.2s;
            margin: 5px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .result-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 4px;
            margin-top: 15px;
        }
        .token-item {
            background: white;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 10px;
            border: 1px solid #e0e0e0;
        }
        .token-item:last-child { margin-bottom: 0; }
        pre {
            background: #263238;
            color: #aed581;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
            font-size: 12px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #495057;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-group textarea {
            min-height: 80px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 Test FCM V1 API</h1>
            <p>Firebase Cloud Messaging V1 API Testing Tool</p>
        </div>

        <div class="content">
            <?php
            // Test 1: Check service account file
            echo '<div class="section">';
            echo '<h3>📁 Step 1: Service Account File</h3>';

            $serviceAccountPath = __DIR__ . '/config/firebase-service-account.json';

            if (file_exists($serviceAccountPath)) {
                echo '<div class="info-row">';
                echo '<span class="info-label">Status:</span>';
                echo '<span class="info-value"><span class="status success">✅ File Found</span></span>';
                echo '</div>';

                $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);

                if ($serviceAccount) {
                    echo '<div class="info-row">';
                    echo '<span class="info-label">Project ID:</span>';
                    echo '<span class="info-value">' . ($serviceAccount['project_id'] ?? 'N/A') . '</span>';
                    echo '</div>';

                    echo '<div class="info-row">';
                    echo '<span class="info-label">Client Email:</span>';
                    echo '<span class="info-value">' . ($serviceAccount['client_email'] ?? 'N/A') . '</span>';
                    echo '</div>';
                }
            } else {
                echo '<div class="info-row">';
                echo '<span class="info-label">Status:</span>';
                echo '<span class="info-value"><span class="status error">❌ File Not Found</span></span>';
                echo '</div>';
            }

            echo '</div>';

            // Test 2: Get Access Token
            echo '<div class="section">';
            echo '<h3>🔑 Step 2: OAuth 2.0 Access Token</h3>';

            if (file_exists($serviceAccountPath)) {
                $accessToken = getFCMV1AccessToken();

                if ($accessToken) {
                    echo '<div class="info-row">';
                    echo '<span class="info-label">Status:</span>';
                    echo '<span class="info-value"><span class="status success">✅ Token Obtained</span></span>';
                    echo '</div>';

                    echo '<div class="info-row">';
                    echo '<span class="info-label">Token Length:</span>';
                    echo '<span class="info-value">' . strlen($accessToken) . ' characters</span>';
                    echo '</div>';

                    echo '<div class="info-row">';
                    echo '<span class="info-label">Token Preview:</span>';
                    echo '<span class="info-value">' . substr($accessToken, 0, 50) . '...</span>';
                    echo '</div>';
                } else {
                    echo '<div class="info-row">';
                    echo '<span class="info-label">Status:</span>';
                    echo '<span class="info-value"><span class="status error">❌ Failed to Get Token</span></span>';
                    echo '</div>';
                }
            }

            echo '</div>';

            // Test 3: Get Admin Tokens from Database
            echo '<div class="section">';
            echo '<h3>👥 Step 3: Admin FCM Tokens</h3>';

            $query_admins = mysqli_query($conn, "
                SELECT u.id, u.username, u.level, f.token, f.updated_at
                FROM fcm_tokens f
                INNER JOIN user u ON f.user_id = u.id
                WHERE u.level = 'admin'
                ORDER BY f.updated_at DESC
            ");

            $admin_count = mysqli_num_rows($query_admins);

            echo '<div class="info-row">';
            echo '<span class="info-label">Total Admin Tokens:</span>';
            echo '<span class="info-value"><strong>' . $admin_count . '</strong></span>';
            echo '</div>';

            if ($admin_count > 0) {
                echo '<div style="margin-top: 15px;">';
                while ($admin = mysqli_fetch_assoc($query_admins)) {
                    echo '<div class="token-item">';
                    echo '<strong>' . $admin['username'] . '</strong> (' . $admin['level'] . ')';
                    echo '<br><small style="color: #6c757d;">User ID: ' . $admin['id'] . ' | Updated: ' . $admin['updated_at'] . '</small>';
                    echo '<br><small style="color: #6c757d;">Token: ' . substr($admin['token'], 0, 40) . '...</small>';
                    echo '</div>';
                }
                echo '</div>';
            } else {
                echo '<div class="result-box" style="border-color: #ffc107; background: #fff3cd;">';
                echo '<p style="color: #856404; margin: 0;">⚠️ <strong>Belum ada admin yang memiliki FCM token.</strong></p>';
                echo '<p style="color: #856404; margin: 10px 0 0 0;">Admin harus login di aplikasi mobile terlebih dahulu untuk mendapatkan FCM token.</p>';
                echo '</div>';
            }

            echo '</div>';

            // Test 4: Send Test Notification
            if (file_exists($serviceAccountPath) && $accessToken) {
                echo '<div class="section">';
                echo '<h3>🔔 Step 4: Send Test Notification</h3>';

                // Check if test was sent
                if (isset($_POST['send_to_admins']) && $admin_count > 0) {
                    // Send to admins from database
                    mysqli_data_seek($query_admins, 0);
                    $admin_tokens = [];

                    while ($admin = mysqli_fetch_assoc($query_admins)) {
                        $admin_tokens[] = [
                            'token' => $admin['token'],
                            'username' => $admin['username'],
                            'level' => $admin['level']
                        ];
                    }

                    $title = "🧪 Test Notification";
                    $body = "This is a test notification from FCM V1 API at " . date('H:i:s');
                    $data = [
                        'test' => 'true',
                        'timestamp' => date('Y-m-d H:i:s'),
                        'type' => 'test'
                    ];

                    $results = sendFCMNotificationToMultiple($admin_tokens, $title, $body, $data);

                    echo '<div class="result-box" style="border-color: #28a745; background: #d4edda;">';
                    echo '<h4 style="color: #155724; margin-top: 0;">📊 Test Results (To Admins)</h4>';
                    echo '<p style="color: #155724;"><strong>Success:</strong> ' . $results['success'] . '</p>';
                    echo '<p style="color: #155724;"><strong>Failed:</strong> ' . $results['failed'] . '</p>';
                    echo '</div>';

                    if (!empty($results['details'])) {
                        echo '<div style="margin-top: 15px;">';
                        echo '<strong>Details:</strong>';
                        foreach ($results['details'] as $detail) {
                            $statusClass = $detail['status'] === 'success' ? 'success' : 'error';
                            $statusIcon = $detail['status'] === 'success' ? '✅' : '❌';

                            echo '<div class="token-item">';
                            echo '<span class="status ' . $statusClass . '">' . $statusIcon . ' ' . strtoupper($detail['status']) . '</span> ';
                            echo '<strong>' . $detail['username'] . '</strong> (' . $detail['level'] . ')';
                            echo '<br><small style="color: #6c757d;">Token: ' . $detail['token_preview'] . '</small>';
                            echo '</div>';
                        }
                        echo '</div>';
                    }
                } elseif (isset($_POST['send_custom'])) {
                    // Send to custom token
                    $customToken = trim($_POST['custom_token']);
                    $title = $_POST['title'] ?: "🧪 Test Notification";
                    $body = $_POST['body'] ?: "This is a test notification";

                    if (!empty($customToken)) {
                        $errorMessage = null;
                        $result = sendFCMV1Notification($customToken, $title, $body, [
                            'test' => 'true',
                            'timestamp' => date('Y-m-d H:i:s')
                        ], $errorMessage);

                        if ($result) {
                            echo '<div class="result-box" style="border-color: #28a745; background: #d4edda;">';
                            echo '<p style="color: #155724; margin: 0;"><strong>✅ Notification sent successfully!</strong></p>';
                            echo '<p style="color: #155724; margin: 5px 0 0 0;">Token: ' . substr($customToken, 0, 40) . '...</p>';
                            echo '</div>';
                        } else {
                            echo '<div class="result-box" style="border-color: #dc3545; background: #f8d7da;">';
                            echo '<p style="color: #721c24; margin: 0;"><strong>❌ Failed to send notification</strong></p>';
                            if ($errorMessage) {
                                echo '<p style="color: #721c24; margin: 10px 0 0 0;"><strong>Error Details:</strong></p>';
                                echo '<pre style="background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px; margin: 5px 0;">' . htmlspecialchars($errorMessage) . '</pre>';
                            }
                            echo '<p style="color: #721c24; margin: 5px 0 0 0;"><small>Check PHP error logs for more details.</small></p>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="result-box" style="border-color: #ffc107; background: #fff3cd;">';
                        echo '<p style="color: #856404; margin: 0;">⚠️ Please enter a valid FCM token</p>';
                        echo '</div>';
                    }
                }

                // Show forms
                if ($admin_count > 0) {
                    echo '<div style="margin-bottom: 20px;">';
                    echo '<h4 style="margin-bottom: 10px;">Option 1: Send to All Admins</h4>';
                    echo '<form method="POST">';
                    echo '<button type="submit" name="send_to_admins" class="btn">🚀 Send to ' . $admin_count . ' Admin(s)</button>';
                    echo '</form>';
                    echo '</div>';
                }

                echo '<div>';
                echo '<h4 style="margin-bottom: 10px;">Option 2: Send to Custom Token</h4>';
                echo '<form method="POST">';
                echo '<div class="form-group">';
                echo '<label>FCM Token:</label>';
                echo '<textarea name="custom_token" placeholder="Paste FCM device token here..." required></textarea>';
                echo '</div>';
                echo '<div class="form-group">';
                echo '<label>Title:</label>';
                echo '<input type="text" name="title" value="🧪 Test Notification" required>';
                echo '</div>';
                echo '<div class="form-group">';
                echo '<label>Body:</label>';
                echo '<input type="text" name="body" value="This is a test notification from FCM V1 API" required>';
                echo '</div>';
                echo '<button type="submit" name="send_custom" class="btn btn-secondary">📤 Send Custom Notification</button>';
                echo '</form>';
                echo '</div>';

                echo '</div>';
            }
            ?>

            <div class="section">
                <h3>📚 Documentation</h3>
                <p><strong>FCM V1 API Endpoint:</strong></p>
                <pre>https://fcm.googleapis.com/v1/projects/<?php echo $serviceAccount['project_id'] ?? 'YOUR-PROJECT-ID'; ?>/messages:send</pre>

                <p style="margin-top: 15px;"><strong>Authentication:</strong> OAuth 2.0 Bearer Token</p>
                <p><strong>Migration Status:</strong> <span class="status success">✅ Completed</span></p>
                
                <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
                    <p style="color: #856404; margin: 0;"><strong>💡 Tips:</strong></p>
                    <ul style="color: #856404; margin: 10px 0 0 20px; line-height: 1.8;">
                        <li>Untuk mendapatkan FCM token, admin harus login di aplikasi mobile</li>
                        <li>Gunakan "Option 2" jika Anda sudah punya FCM token dari device untuk testing</li>
                        <li>FCM token bisa didapat dari log aplikasi mobile saat pertama kali dijalankan</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>