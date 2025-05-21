<?php
// public/webhook.php

// Include configuration
$config = require_once __DIR__ . '/../config/config.php';

// Get the raw POST data
$rawPostData = file_get_contents("php://input");

// Get the signature from headers
$signature = isset($_SERVER['HTTP_TRACKING_HMAC_SHA256']) ? $_SERVER['HTTP_TRACKING_HMAC_SHA256'] : '';

// Check if this is a browser request (GET with no payload)
$isBrowserRequest = empty($rawPostData) && $_SERVER['REQUEST_METHOD'] === 'GET';

if ($isBrowserRequest) {
    // Display a friendly message for browser requests
    header('Content-Type: text/html');
    echo "<h1>Tracking.my Webhook Endpoint</h1>";
    echo "<p>This is a webhook endpoint for tracking.my integration.</p>";
    echo "<p>It's designed to receive POST requests from tracking.my, not browser visits.</p>";
    exit;
}

// Your webhook secret key
$secretKey = $config['webhook_secret'];

// Log request for debugging
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

$logData = [
    'time' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers' => getallheaders(),
    'payload' => json_decode($rawPostData, true)
];

file_put_contents(
    $logDir . '/webhook_' . date('Y-m-d') . '.log',
    json_encode($logData, JSON_PRETTY_PRINT) . PHP_EOL . str_repeat('-', 50) . PHP_EOL,
    FILE_APPEND
);

// If this is a POST request with payload but no signature, log the issue
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($rawPostData) && empty($signature)) {
    file_put_contents(
        $logDir . '/missing_signature_' . date('Y-m-d') . '.log',
        "Received POST without signature header\nPayload: " . $rawPostData . "\n" . str_repeat('-', 50) . PHP_EOL,
        FILE_APPEND
    );
    
    http_response_code(401);
    echo json_encode(['error' => 'Missing signature header']);
    exit;
}

// Only verify signature for non-browser requests with payload
if (!empty($rawPostData)) {
    // Verify the signature
    $calculatedSignature = base64_encode(hash_hmac('sha256', $rawPostData, $secretKey, true));
    
    // Log signature verification details for debugging
    file_put_contents(
        $logDir . '/signature_check_' . date('Y-m-d') . '.log',
        "Payload: " . $rawPostData . "\n" .
        "Secret Key: " . $secretKey . "\n" .
        "Expected: " . $calculatedSignature . "\n" .
        "Received: " . $signature . "\n" . 
        str_repeat('-', 50) . PHP_EOL,
        FILE_APPEND
    );

    // Check if signatures match (only if signature is present)
    if (!empty($signature) && hash_equals($calculatedSignature, $signature)) {
        // Parse the webhook payload
        $payload = json_decode($rawPostData, true);
        
        // Process the events
        if (isset($payload['events']) && is_array($payload['events'])) {
            foreach ($payload['events'] as $event) {
                // Handle different event types
                switch ($event['event']) {
                    case 'trackings/create':
                        // Handle tracking creation
                        logEvent('Tracking created', $event);
                        break;
                    
                    case 'trackings/update':
                        // Handle tracking update
                        logEvent('Tracking updated', $event);
                        break;
                    
                    case 'trackings/checkpoint_update':
                        // Handle checkpoint update
                        logEvent('Checkpoint updated', $event);
                        break;
                    
                    // Add more event handlers as needed
                    default:
                        logEvent('Unknown event: ' . $event['event'], $event);
                }
            }
        }
        
        // Return a success response
        http_response_code(200);
        echo json_encode(['success' => true]);
    } else {
        // Signature verification failed
        http_response_code(401);
        echo json_encode(['error' => 'Invalid signature']);
    }
} else {
    // No payload
    http_response_code(400);
    echo json_encode(['error' => 'No payload received']);
}

// Simple logging function
function logEvent($message, $data) {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/events_' . date('Y-m-d') . '.log';
    $logData = date('Y-m-d H:i:s') . ' - ' . $message . ': ' . json_encode($data, JSON_PRETTY_PRINT) . PHP_EOL;
    
    file_put_contents($logFile, $logData . str_repeat('-', 50) . PHP_EOL, FILE_APPEND);
}

/**
 * Polyfill for getallheaders() for servers where it's not available
 */
if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}
?>