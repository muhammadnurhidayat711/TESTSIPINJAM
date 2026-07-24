<?php
/**
 * FCM V1 API Helper Functions
 * Fungsi untuk mengirim notifikasi menggunakan Firebase Cloud Messaging V1 API
 */

/**
 * Generate dynamic base URL — ganti hardcoded localhost
 * Deteksi path project dari __DIR__ (root = folder fcm_helper.php)
 */
function base_url($path = '') {
    static $base = null;
    if ($base === null) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $projectDir = str_replace('\\', '/', realpath(__DIR__));
        $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
        $projectPath = '/' . ltrim(str_replace($docRoot, '', $projectDir), '/');
        $base = $protocol . '://' . $host . $projectPath;
    }
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

/**
 * Mendapatkan OAuth 2.0 Access Token untuk FCM V1 API
 * Menggunakan Service Account JWT untuk authenticate
 */
function getFCMV1AccessToken() {
    $serviceAccountPath = __DIR__ . '/config/firebase-service-account.json';
    
    // Check if service account file exists
    if (!file_exists($serviceAccountPath)) {
        error_log("FCM Error: Service account file not found at: " . $serviceAccountPath);
        return false;
    }
    
    // Load service account JSON
    $serviceAccountJson = file_get_contents($serviceAccountPath);
    if ($serviceAccountJson === false) {
        error_log("FCM Error: Cannot read service account file");
        return false;
    }
    
    $serviceAccount = json_decode($serviceAccountJson, true);
    
    if (!$serviceAccount) {
        error_log("FCM Error: Invalid JSON in service account file. JSON Error: " . json_last_error_msg());
        return false;
    }
    
    // Validate required fields
    $requiredFields = ['client_email', 'private_key', 'project_id'];
    foreach ($requiredFields as $field) {
        if (!isset($serviceAccount[$field]) || empty($serviceAccount[$field])) {
            error_log("FCM Error: Missing required field '$field' in service account");
            return false;
        }
    }
    
    // Create JWT
    $now = time();
    $expiration = $now + 3600; // Token valid for 1 hour
    
    // JWT Header
    $header = [
        'alg' => 'RS256',
        'typ' => 'JWT'
    ];
    
    // JWT Payload
    $payload = [
        'iss' => $serviceAccount['client_email'],
        'sub' => $serviceAccount['client_email'],
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => $now,
        'exp' => $expiration,
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging'
    ];
    
    // Encode Header and Payload
    $base64UrlHeader = base64UrlEncode(json_encode($header));
    $base64UrlPayload = base64UrlEncode(json_encode($payload));
    
    // Create signature
    $signatureInput = $base64UrlHeader . '.' . $base64UrlPayload;
    
    // Sign with private key
    $privateKey = $serviceAccount['private_key'];
    $signature = '';
    
    // Check if openssl extension is loaded
    if (!function_exists('openssl_sign')) {
        error_log("FCM Error: OpenSSL extension not available");
        return false;
    }
    
    $signResult = openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);
    
    if (!$signResult) {
        $opensslError = '';
        while ($msg = openssl_error_string()) {
            $opensslError .= $msg . '; ';
        }
        error_log("FCM Error: Failed to sign JWT - " . $opensslError);
        return false;
    }
    
    $base64UrlSignature = base64UrlEncode($signature);
    
    // Complete JWT
    $jwt = $signatureInput . '.' . $base64UrlSignature;
    
    // Exchange JWT for access token
    $tokenUrl = 'https://oauth2.googleapis.com/token';
    $postData = [
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ];
    
    // Check if curl extension is loaded
    if (!function_exists('curl_init')) {
        error_log("FCM Error: cURL extension not available");
        return false;
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tokenUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    // SSL Configuration
    // PRODUCTION: Gunakan SSL verification (true)
    // DEVELOPMENT: Jika ada masalah certificate, set ke false
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlErrno = curl_errno($ch);

    if ($curlErrno) {
        error_log("FCM Error: cURL error [$curlErrno] - " . $curlError);
        curl_close($ch);
        return false;
    }

    curl_close($ch);

    // Parse response
    $responseData = json_decode($response, true);

    if ($httpCode !== 200) {
        error_log("FCM Error: Failed to get access token. HTTP Code: " . $httpCode);
        error_log("FCM Error Response: " . $response);
        
        // Log specific error if available
        if (isset($responseData['error'])) {
            error_log("FCM Error Type: " . $responseData['error']);
            if (isset($responseData['error_description'])) {
                error_log("FCM Error Description: " . $responseData['error_description']);
            }
        }
        
        return false;
    }
    
    if (!isset($responseData['access_token'])) {
        error_log("FCM Error: No access_token in response");
        error_log("FCM Response: " . $response);
        return false;
    }
    
    // Success!
    error_log("FCM Success: Access token obtained successfully");
    
    return $responseData['access_token'];
}

/**
 * Base64 URL Encode
 */
function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Kirim notifikasi FCM V1 ke satu device token
 */
function sendFCMV1Notification($token, $title, $body, $data = [], &$errorMessage = null) {
    $accessToken = getFCMV1AccessToken();
    
    if (!$accessToken) {
        $errorMessage = "Failed to get access token for notification";
        error_log("FCM Error: " . $errorMessage);
        return false;
    }
    
    $serviceAccountPath = __DIR__ . '/config/firebase-service-account.json';
    $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
    $projectId = $serviceAccount['project_id'];
    
    $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
    
    // Convert data array values to strings (FCM requirement)
    $dataFormatted = [];
    foreach ($data as $key => $value) {
        $dataFormatted[$key] = (string)$value;
    }
    
    // Prepare FCM message
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
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ]);
    
    // SSL Configuration
    // PRODUCTION: Gunakan SSL verification (true)
    // DEVELOPMENT: Jika ada masalah certificate, set ke false
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlErrno = curl_errno($ch);

    if ($curlErrno) {
        error_log("FCM Error: cURL error sending notification [$curlErrno] - " . $curlError);
        curl_close($ch);
        return false;
    }

    curl_close($ch);

    if ($httpCode === 200) {
        error_log("FCM Success: Notification sent to token: " . substr($token, 0, 20) . "...");
        return true;
    } else {
        $errorMessage = "HTTP Code: " . $httpCode;
        error_log("FCM Error: Failed to send notification. HTTP Code: " . $httpCode);
        error_log("FCM Response: " . $response);
        
        // Parse error response
        $responseData = json_decode($response, true);
        if (isset($responseData['error']['message'])) {
            $errorMessage .= " - " . $responseData['error']['message'];
            error_log("FCM Error Message: " . $responseData['error']['message']);
        }
        
        if (isset($responseData['error']['details'])) {
            $errorMessage .= " | Details: " . json_encode($responseData['error']['details']);
        }
        
        return false;
    }
}

/**
 * Kirim notifikasi ke multiple device tokens
 */
function sendFCMNotificationToMultiple($recipients, $title, $body, $data = []) {
    $results = [
        'success' => 0,
        'failed' => 0,
        'details' => []
    ];
    
    foreach ($recipients as $recipient) {
        $token = $recipient['token'];
        $username = $recipient['username'] ?? 'Unknown';
        $level = $recipient['level'] ?? 'user';
        
        $sent = sendFCMV1Notification($token, $title, $body, $data);
        
        if ($sent) {
            $results['success']++;
            $results['details'][] = [
                'status' => 'success',
                'username' => $username,
                'level' => $level,
                'token_preview' => substr($token, 0, 40) . '...'
            ];
        } else {
            $results['failed']++;
            $results['details'][] = [
                'status' => 'failed',
                'username' => $username,
                'level' => $level,
                'token_preview' => substr($token, 0, 40) . '...'
            ];
        }
    }
    
    return $results;
}

/**
 * Kirim notifikasi ke semua admin
 */
function sendNotificationToAllAdmins($conn, $title, $body, $data = []) {
    $query = mysqli_query($conn, "
        SELECT u.id, u.username, u.level, f.token
        FROM fcm_tokens f
        INNER JOIN user u ON f.user_id = u.id
        WHERE u.level = 'admin'
    ");
    
    if (!$query) {
        error_log("FCM Error: Database query failed - " . mysqli_error($conn));
        return false;
    }
    
    $recipients = [];
    while ($row = mysqli_fetch_assoc($query)) {
        $recipients[] = $row;
    }
    
    if (empty($recipients)) {
        error_log("FCM Warning: No admin tokens found");
        return false;
    }
    
    return sendFCMNotificationToMultiple($recipients, $title, $body, $data);
}

/**
 * Kirim notifikasi ke user tertentu berdasarkan user_id
 */
function sendNotificationToUser($conn, $userId, $title, $body, $data = []) {
    $userId = mysqli_real_escape_string($conn, $userId);
    
    $query = mysqli_query($conn, "
        SELECT u.id, u.username, u.level, f.token
        FROM fcm_tokens f
        INNER JOIN user u ON f.user_id = u.id
        WHERE u.id = '$userId'
        LIMIT 1
    ");
    
    if (!$query) {
        error_log("FCM Error: Database query failed - " . mysqli_error($conn));
        return false;
    }
    
    $user = mysqli_fetch_assoc($query);
    
    if (!$user) {
        error_log("FCM Warning: No token found for user_id: " . $userId);
        return false;
    }
    
    return sendFCMV1Notification($user['token'], $title, $body, $data);
}

/**
 * Legacy wrapper — dipanggil admin approval files dgn parameter (user_id, title, body, clickAction)
 * Mencari token user di DB, kirim via FCM V1 API
 */
function sendFCMNotification($userId, $title, $body, $data = '', $image = '') {
    global $conn;
    $stmt = $conn->prepare("SELECT token FROM fcm_tokens WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row || empty($row['token'])) {
        error_log("FCM Wrapper: No token for user_id=$userId");
        return ['success' => false, 'message' => 'No token found'];
    }

    // Jika $data string, treat sebagai click_action URL
    $payload = [];
    if (is_string($data) && $data !== '') {
        $payload['click_action'] = $data;
    } elseif (is_array($data)) {
        $payload = $data;
    }

    $sent = sendFCMV1Notification($row['token'], $title, $body, $payload);
    return ['success' => $sent, 'message' => $sent ? 'sent' : 'failed'];
}
?>