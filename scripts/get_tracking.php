<?php
// scripts/get_tracking.php

require_once __DIR__ . '/../src/TrackingClient.php';

// Load configuration
$config = require_once __DIR__ . '/../config/config.php';

// Create client with SSL verification disabled for development
$client = new TrackingClient($config['api_key'], false);

// Get tracking details from command line
$trackingNumber = $argv[1] ?? null; // First argument
$courier = $argv[2] ?? 'jt';        // Second argument, default to 'jt'

if (!$trackingNumber) {
    echo "Usage: php get_tracking.php <tracking_number> [courier]\n";
    exit(1);
}

// Get the tracking information
try {
    $result = $client->getTracking($courier, $trackingNumber);
    
    echo "Tracking information result:\n";
    echo "HTTP Code: " . $result['code'] . "\n";
    
    if ($result['code'] == 200 && isset($result['data']['tracking'])) {
        $tracking = $result['data']['tracking'];
        
        echo "Tracking Number: " . $tracking['tracking_number'] . "\n";
        echo "Courier: " . $tracking['courier'] . "\n";
        echo "Status: " . $tracking['status'] . "\n";
        echo "Short Link: " . $tracking['short_link'] . "\n";
        echo "Customer: " . $tracking['customer_name'] . "\n";
        echo "Order Number: " . $tracking['order_number'] . "\n";
        echo "Created at: " . $tracking['created_at'] . "\n";
        
        if (!empty($tracking['latest_checkpoint'])) {
            $checkpoint = $tracking['latest_checkpoint'];
            echo "\nLatest Checkpoint:\n";
            echo "Time: " . $checkpoint['time'] . "\n";
            echo "Status: " . $checkpoint['status'] . "\n";
            echo "Content: " . $checkpoint['content'] . "\n";
            echo "Location: " . $checkpoint['location'] . "\n";
        } else {
            echo "\nNo checkpoints available yet.\n";
        }
        
        if (!empty($tracking['checkpoints'])) {
            echo "\nAll Checkpoints:\n";
            foreach ($tracking['checkpoints'] as $index => $checkpoint) {
                echo ($index + 1) . ") " . $checkpoint['time'] . " - " 
                     . $checkpoint['status'] . " - " 
                     . $checkpoint['content'] . " - "
                     . $checkpoint['location'] . "\n";
            }
        }
    } else {
        echo "Error or no data returned: " . json_encode($result['data']) . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}