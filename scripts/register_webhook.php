<?php

require_once __DIR__ . '/../src/TrackingClient.php';

// Load configuration
$config = require_once __DIR__ . '/../config/config.php';

// Create client with SSL verification disabled for development
$client = new TrackingClient($config['api_key'], false); // Set verifySsl to false

// Webhook events to subscribe to
$events = [
    'trackings/create',
    'trackings/update',
    'trackings/checkpoint_update',
    'trackings/delete',
    'shipments/create',
    'shipments/update',
    'shipments/delete',
    'shipments/cancel',
    'shipments/generated'
];

// Register the webhook
try {
    $result = $client->registerWebhook(
        $config['webhook_url'],
        $events,
        $config['webhook_secret']
    );
    
    echo "Webhook registration result:\n";
    echo "HTTP Code: " . $result['code'] . "\n";
    echo "Response: " . json_encode($result['data'], JSON_PRETTY_PRINT) . "\n";
    
    if ($result['code'] >= 200 && $result['code'] < 300) {
        echo "Webhook successfully registered!\n";
    } else {
        echo "Failed to register webhook.\n";
        echo "Raw response: " . $result['raw'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}