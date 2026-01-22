<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Kirim Firebase Cloud Messaging
 */
function sendFCM($token, $title, $body)
{
    if (!$token) {
        return false;
    }

    $payload = [
        'to' => $token,
        'notification' => [
            'title' => $title,
            'body'  => $body
        ]
    ];

    $ch = curl_init('https://fcm.googleapis.com/fcm/send');

    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: key=' . getenv('FCM_SERVER_KEY'),
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload)
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}
