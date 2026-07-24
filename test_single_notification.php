<?php
/**
 * Test Single FCM Notification with Full Debug
 */

require_once 'fcm_helper.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Single FCM Notification</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Consolas', 'Monaco', monospace;
            background: #1a1a2e;
            color: #eee;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: #16213e;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }
        h1 { 
            color: #0f3460; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
            font-size: 28px;
        }
        .form-section {
            background: #0f3460;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            color: #53a8b6;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            background: #1a1a2e;
            border: 2px solid #533483;
            border-radius: 6px;
            color: #eee;
            font-family: 'Consolas', monospace;
            font-size: 14px;
        }
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .debug-section {
            background: #0f3460;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            border-left: 4px solid #53a8b6;
        }
        .debug-section h2 {
            color: #53a8b6;
            margin-bottom: 15px;
            font-size: 20px;
        }
        .success-box {
            background: rgba(72, 187, 120, 0.1);
            border: 2px solid #48bb78;
            color: #48bb78;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }
        .error-box {
            background: rgba(245, 101, 101, 0.1);
            border: 2px solid #f56565;
            color: #f56565;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }
        pre {
            background: #1a1a2e;
            border: 1px solid #533483;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
            color: #a8dadc;
            font-size: 13px;
            margin: 10px 0;
        }
        .info-item {
            padding: 10px;
            margin: 5px 0;
            background: #1a1a2e;
            border-radius: 4px;
        }
        .info-item strong {
            color: #53a8b6;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Test Single FCM Notification - Full Debug Mode</h1>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_send'])) {
            $token = trim($_POST['fcm_token']);
            $title = trim($_POST['title']);
            $body = trim($_POST['body']);

            echo '<div class="debug-section">';
            echo '<h2>📊 Test Execution Results</h2>';

            // Step 1: Validate inputs
            echo '<div class="info-item">';
            echo '<strong>Step 1:</strong> Input Validation<br>';
            echo 'Token Length: ' . strlen($token) . ' characters<br>';
            echo 'Token Preview: ' . substr($token, 0, 50) . '...';
            echo '</div>';

            // Step 2: Get Access Token
            echo '<div class="info-item">';
            echo '<strong>Step 2:</strong> Getting OAuth 2.0 Access Token<br>';
            $accessToken = getFCMV1AccessToken();
            if ($accessToken) {
                echo '<span style="color: #48bb78;">✅ Access token obtained successfully</span><br>';
                echo 'Token Length: ' . strlen($accessToken) . ' characters<br>';
                echo 'Token Preview: ' . substr($accessToken, 0, 50) . '...';
            } else {
                echo '<span style="color: #f56565;">❌ Failed to get access token</span>';
            }
            echo '</div>';

            if ($accessToken) {
                // Step 3: Prepare FCM Message
                echo '<div class="info-item">';
                echo '<strong>Step 3:</strong> Preparing FCM Message<br>';

                $serviceAccountPath = __DIR__ . '/config/firebase-service-account.json';
                $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
                $projectId = $serviceAccount['project_id'];

                $fcmUrl = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

                $dataFormatted = [
                    'test' => 'true',
                    'timestamp' => date('Y-m-d H:i:s')
                ];

                $message = [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $title,
                            'body' => $body
                        ],
                        'data' => $dataFormatted,
                        'android' => [
                            'priority' => 'high',
                            'notification' => [
                                'sound' => 'default',
                                'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
                            ]
                        ],
                        'apns' => [
                            'payload' => [
                                'aps' => [
                                    'sound' => 'default',
                                    'badge' => 1
                                ]
                            ]
                        ]
                    ]
                ];

                echo 'FCM Endpoint: ' . $fcmUrl . '<br>';
                echo 'Project ID: ' . $projectId . '<br>';
                echo '<strong>Message Payload:</strong>';
                echo '<pre>' . json_encode($message, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</pre>';
                echo '</div>';

                // Step 4: Send Request
                echo '<div class="info-item">';
                echo '<strong>Step 4:</strong> Sending HTTP Request to FCM<br>';

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $fcmUrl);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $accessToken,
                    'Content-Type: application/json'
                ]);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_VERBOSE, true);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                $curlErrno = curl_errno($ch);

                echo 'HTTP Status Code: <strong>' . $httpCode . '</strong><br>';

                if ($curlErrno) {
                    echo '<span style="color: #f56565;">cURL Error [' . $curlErrno . ']: ' . $curlError . '</span><br>';
                }

                curl_close($ch);

                echo '<strong>Response Body:</strong>';
                echo '<pre>' . htmlspecialchars($response) . '</pre>';

                // Try to decode response
                $responseData = json_decode($response, true);
                if ($responseData) {
                    echo '<strong>Decoded Response:</strong>';
                    echo '<pre>' . json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</pre>';
                }

                echo '</div>';

                // Step 5: Result
                if ($httpCode === 200) {
                    echo '<div class="success-box">';
                    echo '<strong>✅ SUCCESS!</strong><br>';
                    echo 'Notification has been sent successfully to FCM servers.<br>';
                    echo 'The device should receive the notification if:<br>';
                    echo '- The token is valid and registered<br>';
                    echo '- The app has proper FCM configuration<br>';
                    echo '- The device is connected to the internet<br>';
                    echo '- Notification permissions are granted';
                    echo '</div>';
                } else {
                    echo '<div class="error-box">';
                    echo '<strong>❌ FAILED</strong><br>';
                    echo '<strong>HTTP Code:</strong> ' . $httpCode . '<br>';

                    if (isset($responseData['error'])) {
                        echo '<strong>Error Type:</strong> ' . ($responseData['error']['status'] ?? 'Unknown') . '<br>';
                        echo '<strong>Error Message:</strong> ' . ($responseData['error']['message'] ?? 'No message') . '<br>';

                        if (isset($responseData['error']['details'])) {
                            echo '<strong>Details:</strong><br>';
                            echo '<pre>' . json_encode($responseData['error']['details'], JSON_PRETTY_PRINT) . '</pre>';
                        }

                        // Common error solutions
                        echo '<br><strong>Common Solutions:</strong><br>';
                        if (strpos($responseData['error']['message'], 'not found') !== false) {
                            echo '- The FCM token might be invalid or expired<br>';
                            echo '- Re-register the device to get a new token<br>';
                        }
                        if (strpos($responseData['error']['message'], 'permission') !== false) {
                            echo '- Check Firebase project permissions<br>';
                            echo '- Verify service account has FCM Admin role<br>';
                        }
                    }
                    echo '</div>';
                }
            }

            echo '</div>';
        }
        ?>

        <div class="form-section">
            <h2 style="color: #53a8b6; margin-bottom: 15px;">📝 Test Form</h2>
            <form method="POST">
                <div class="form-group">
                    <label>FCM Device Token:</label>
                    <textarea name="fcm_token" required placeholder="Paste your FCM device token here (e.g., dXXXXXXXXXX:APA91bXXXX...)"><?php echo isset($_POST['fcm_token']) ? htmlspecialchars($_POST['fcm_token']) : ''; ?></textarea>
                    <small style="color: #999;">Get this from your Flutter app console or database</small>
                </div>

                <div class="form-group">
                    <label>Notification Title:</label>
                    <input type="text" name="title" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '🧪 Test Notification'; ?>" required>
                </div>

                <div class="form-group">
                    <label>Notification Body:</label>
                    <input type="text" name="body" value="<?php echo isset($_POST['body']) ? htmlspecialchars($_POST['body']) : 'This is a test notification from FCM V1 API'; ?>" required>
                </div>

                <button type="submit" name="test_send" class="btn">🚀 Send Test Notification</button>
            </form>
        </div>

        <div class="form-section">
            <h2 style="color: #53a8b6; margin-bottom: 10px;">💡 How to Get FCM Token</h2>
            <p style="margin-bottom: 10px;">Add this code to your Flutter app to get the token:</p>
            <pre style="font-size: 12px;">import 'package:firebase_messaging/firebase_messaging.dart';

void getFCMToken() async {
  FirebaseMessaging messaging = FirebaseMessaging.instance;
  String? token = await messaging.getToken();
  print("FCM Token: $token");
  // Save to your database
}</pre>
            <p style="margin-top: 10px; color: #999;">Or check your database table <code>fcm_tokens</code> for existing tokens.</p>
        </div>
    </div>
</body>
</html>
