<?php

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
    echo "Example: php register_tracking.php 600527447944 jt\n";
    exit(1);
}

// Tracking information (using v1 API field names)
$trackingData = [
    'tracking_number' => $trackingNumber,
    'courier' => $courier, // v1 uses 'courier', not 'courier_code'
    'order_number' => $orderNumber,
    'customer_name' => 'Test User',
    'customer_email' => 'test@example.com',
    'customer_phone' => '60123456789',
    'parcel_content' => 'Test Product',
    'order_id' => rand(100000, 999999), // Add order_id as integer
    'note' => 'Test tracking registration'
];

// Try to register the tracking
try {
    echo "Attempting to create tracking via v1 API...\n";
    echo "Tracking Number: $trackingNumber\n";
    echo "Courier: $courier\n";
    echo "Order Number: $orderNumber\n\n";
    
    $result = $client->createTracking($trackingData);
    
    echo "Tracking registration result:\n";
    echo "HTTP Code: " . $result['code'] . "\n";
    
    if ($result['code'] >= 200 && $result['code'] < 300) {
        echo "✅ Tracking successfully registered!\n\n";
        
        if (isset($result['data']['tracking'])) {
            $tracking = $result['data']['tracking'];
            echo "Tracking Details:\n";
            echo "ID: " . $tracking['id'] . "\n";
            echo "Tracking Number: " . $tracking['tracking_number'] . "\n";
            echo "Courier: " . $tracking['courier'] . "\n";
            echo "Status: " . $tracking['status'] . "\n";
            echo "Short Link: " . $tracking['short_link'] . "\n";
            echo "Created at: " . $tracking['created_at'] . "\n";
        }
    } else {
        echo "❌ Failed to register tracking.\n";
        echo "Response: " . json_encode($result['data'], JSON_PRETTY_PRINT) . "\n";
    }
    
    echo "\nRaw Response: " . $result['raw'] . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>