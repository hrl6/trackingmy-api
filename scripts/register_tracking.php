<?php
// scripts/register_tracking.php

require_once __DIR__ . '/../src/TrackingClient.php';

// Load configuration
$config = require_once __DIR__ . '/../config/config.php';

// Create client with SSL verification disabled for development
$client = new TrackingClient($config['api_key'], false);

// Get tracking details from command line
$trackingNumber = $argv[1] ?? null; // First argument
$courier = $argv[2] ?? 'jt';        // Second argument, default to 'jt'
$orderNumber = $argv[3] ?? rand(1000000, 9999999); // Generate random order number if not provided

if (!$trackingNumber) {
    echo "Usage: php register_tracking.php <tracking_number> [courier] [order_number]\n";
    exit(1);
}

// Tracking information
$trackingData = [
    'tracking_number' => $trackingNumber,
    'courier' => $courier,
    'order_number' => $orderNumber,
    'customer_name' => 'Test User',
    'customer_email' => 'test@example.com',
    'customer_phone' => '60123456789',
    'parcel_content' => 'Test Product'
];

// Try to register with the updated method
try {
    echo "Attempting to create tracking with modified approach...\n";
    $result = $client->createTracking($trackingData);
    
    echo "Tracking registration result:\n";
    echo "HTTP Code: " . $result['code'] . "\n";
    echo "Response: " . json_encode($result['data'], JSON_PRETTY_PRINT) . "\n";
    echo "Raw Response: " . $result['raw'] . "\n";
    echo "Verbose Log: " . $result['verbose'] . "\n";
    
    if ($result['code'] >= 200 && $result['code'] < 300) {
        echo "Tracking successfully registered!\n";
    } else {
        echo "Failed to register tracking.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}