<?php
// scripts/test_webhook.php

// Load configuration
$config = require_once __DIR__ . '/../config/config.php';

// Create a sample webhook payload
$payload = [
    'events' => [
        [
            'time' => time(),
            'event' => 'trackings/update',
            'domain' => 'example.com',
            'tracking' => [
                'id' => 50,
                'tracking_number' => '600527447944',
                'courier' => 'jt',
                'status' => 'delivered',
                'customer_name' => 'Test User',
                'customer_email' => 'test@example.com',
                'customer_phone' => '60123456789',
                'parcel_content' => 'Test Product',
                'order_number' => '9456652',
                'created_at' => date('Y-m-d\TH:i:s+08:00', time() - 86400),
                'updated_at' => date('Y-m-d\TH:i:s+08:00'),
                'latest_checkpoint' => [
                    'time' => date('Y-m-d\TH:i:s+08:00'),
                    'status' => 'delivered',
                    'content' => 'Delivered',
                    'location' => 'Test Location'
                ]
            ]
        ]
    ]
];

// Convert to JSON
$payloadJson = json_encode($payload);

// Print details for debugging
echo "Webhook URL: " . $config['webhook_url'] . "\n";
echo "Secret Key: " . $config['webhook_secret'] . "\n";
echo "Payload: " . $payloadJson . "\n\n";

// Generate the signature
$signature = base64_encode(hash_hmac('sha256', $payloadJson, $config['webhook_secret'], true));
echo "Generated Signature: " . $signature . "\n\n";

// Send a request to your webhook endpoint
// $ch = curl_init($config['webhook_url']);
// curl_setopt_array($ch, [
//     CURLOPT_RETURNTRANSFER => true,
//     CURLOPT_POST => true,
//     CURLOPT_POSTFIELDS => $payloadJson,
//     CURLOPT_HTTPHEADER => [
//         'Content-Type: application/json',
//         'Tracking-Hmac-Sha256: ' . $signature
//     ]
// ]);

// Send a request to your webhook endpoint
$ch = curl_init($config['webhook_url']);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payloadJson,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Tracking-Hmac-Sha256: ' . $signature
    ],
    CURLOPT_SSL_VERIFYPEER => false, // Disable SSL verification
    CURLOPT_SSL_VERIFYHOST => 0      // Disable host verification
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "Test webhook response:\n";
echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";

if (curl_errno($ch)) {
    echo "Error: " . curl_error($ch) . "\n";
}

curl_close($ch);