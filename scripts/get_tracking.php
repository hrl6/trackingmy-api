<?php

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
    echo "Example: php get_tracking.php 600527447944 jt\n";
    exit(1);
}

// Get the tracking information
try {
    echo "Fetching tracking information...\n";
    echo "Tracking Number: $trackingNumber\n";
    echo "Courier: $courier\n\n";
    
    $result = $client->getTracking($courier, $trackingNumber);
    
    echo "HTTP Code: " . $result['code'] . "\n";
    
    if ($result['code'] == 200) {
        if (isset($result['data']['tracking'])) {
            $tracking = $result['data']['tracking'];
            
            echo "✅ Tracking Found!\n\n";
            echo "=== Tracking Information ===\n";
            echo "ID: " . $tracking['id'] . "\n";
            echo "Tracking Number: " . $tracking['tracking_number'] . "\n";
            echo "Courier: " . $tracking['courier'] . "\n";
            echo "Status: " . $tracking['status'] . "\n";
            echo "Short Link: " . $tracking['short_link'] . "\n";
            echo "Customer: " . ($tracking['customer_name'] ?? 'N/A') . "\n";
            echo "Email: " . ($tracking['customer_email'] ?? 'N/A') . "\n";
            echo "Phone: " . ($tracking['customer_phone'] ?? 'N/A') . "\n";
            echo "Order Number: " . ($tracking['order_number'] ?? 'N/A') . "\n";
            echo "Parcel Content: " . ($tracking['parcel_content'] ?? 'N/A') . "\n";
            echo "Created at: " . $tracking['created_at'] . "\n";
            echo "Updated at: " . $tracking['updated_at'] . "\n";
            
            if (!empty($tracking['latest_checkpoint'])) {
                $checkpoint = $tracking['latest_checkpoint'];
                echo "\n=== Latest Checkpoint ===\n";
                echo "Time: " . $checkpoint['time'] . "\n";
                echo "Status: " . $checkpoint['status'] . "\n";
                echo "Content: " . $checkpoint['content'] . "\n";
                echo "Location: " . $checkpoint['location'] . "\n";
            } else {
                echo "\n=== Latest Checkpoint ===\n";
                echo "No checkpoints available yet.\n";
            }
            
            if (!empty($tracking['checkpoints'])) {
                echo "\n=== All Checkpoints ===\n";
                foreach ($tracking['checkpoints'] as $index => $checkpoint) {
                    echo ($index + 1) . ") " . $checkpoint['time'] . "\n";
                    echo "   Status: " . $checkpoint['status'] . "\n";
                    echo "   Content: " . $checkpoint['content'] . "\n";
                    echo "   Location: " . $checkpoint['location'] . "\n\n";
                }
            }
            
            if (isset($tracking['note']) && !empty($tracking['note'])) {
                echo "=== Notes ===\n";
                echo $tracking['note'] . "\n";
            }
            
        } elseif (isset($result['data']['trackings']) && is_array($result['data']['trackings'])) {
            // Handle case where multiple trackings are returned
            $trackings = $result['data']['trackings'];
            echo "✅ Found " . count($trackings) . " tracking(s)!\n\n";
            
            foreach ($trackings as $index => $tracking) {
                echo "=== Tracking " . ($index + 1) . " ===\n";
                echo "Tracking Number: " . $tracking['tracking_number'] . "\n";
                echo "Courier: " . $tracking['courier'] . "\n";
                echo "Status: " . $tracking['status'] . "\n";
                echo "Short Link: " . $tracking['short_link'] . "\n\n";
            }
        } else {
            echo "❌ No tracking data found in response.\n";
            echo "Response: " . json_encode($result['data'], JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        echo "❌ Failed to get tracking information.\n";
        echo "Response: " . json_encode($result['data'], JSON_PRETTY_PRINT) . "\n";
    }
    
    echo "\n=== Raw Response ===\n";
    echo $result['raw'] . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>