<?php

// Include configuration
$config = require_once __DIR__ . '/../config/config.php';

// Get the raw POST data
$rawPostData = file_get_contents("php://input");

// Get the signature from headers (try different header formats)
$signature = '';
$headers = getallheaders();
foreach ($headers as $name => $value) {
    if (strtolower($name) === 'tracking-hmac-sha256') {
        $signature = $value;
        break;
    }
}

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
    'headers' => $headers,
    'payload' => json_decode($rawPostData, true),
    'signature_received' => $signature
];

file_put_contents(
    $logDir . '/webhook_' . date('Y-m-d') . '.log',
    json_encode($logData, JSON_PRETTY_PRINT) . PHP_EOL . str_repeat('-', 50) . PHP_EOL,
    FILE_APPEND
);

// Only process POST requests with payload
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($rawPostData)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

// Verify the signature if secret key is provided
if (!empty($secretKey)) {
    $calculatedSignature = base64_encode(hash_hmac('sha256', $rawPostData, $secretKey, true));
    
    // Log signature verification details for debugging
    file_put_contents(
        $logDir . '/signature_check_' . date('Y-m-d') . '.log',
        "Payload: " . $rawPostData . "\n" .
        "Secret Key: " . $secretKey . "\n" .
        "Expected: " . $calculatedSignature . "\n" .
        "Received: " . $signature . "\n" . 
        "Match: " . (hash_equals($calculatedSignature, $signature) ? 'YES' : 'NO') . "\n" .
        str_repeat('-', 50) . PHP_EOL,
        FILE_APPEND
    );

    // Check if signatures match (only if signature is present)
    if (!empty($signature) && !hash_equals($calculatedSignature, $signature)) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid signature']);
        exit;
    }
}

// Parse the webhook payload
$payload = json_decode($rawPostData, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Handle different webhook versions
if (isset($payload['events']) && is_array($payload['events'])) {
    // Webhook v2 format
    foreach ($payload['events'] as $event) {
        processEventV2($event);
    }
} elseif (isset($payload['event'])) {
    // Webhook v1 format
    processEventV1($payload);
} else {
    logEvent('Unknown webhook format', $payload);
}

// Return a success response
http_response_code(200);
echo json_encode(['success' => true]);

/**
 * Process v2 webhook events
 */
function processEventV2($event) {
    if (!isset($event['event'])) {
        logEvent('V2 Event type not found', $event);
        return;
    }
    
    switch ($event['event']) {
        case 'trackings/create':
            handleTrackingCreateV2($event);
            break;
        case 'trackings/update':
            handleTrackingUpdateV2($event);
            break;
        case 'trackings/checkpoint_update':
            handleCheckpointUpdateV2($event);
            break;
        case 'trackings/delete':
            handleTrackingDeleteV2($event);
            break;
        case 'shipments/create':
            handleShipmentCreateV2($event);
            break;
        case 'shipments/update':
            handleShipmentUpdateV2($event);
            break;
        case 'shipments/delete':
            handleShipmentDeleteV2($event);
            break;
        case 'shipments/cancel':
            handleShipmentCancelV2($event);
            break;
        case 'shipments/generated':
            handleShipmentGeneratedV2($event);
            break;
        default:
            logEvent('Unknown V2 event type: ' . $event['event'], $event);
    }
}

/**
 * Process v1 webhook events
 */
function processEventV1($payload) {
    if (!isset($payload['event'])) {
        logEvent('V1 Event type not found', $payload);
        return;
    }
    
    switch ($payload['event']) {
        case 'tracking_create':
            handleTrackingCreateV1($payload);
            break;
        case 'tracking_update':
            handleTrackingUpdateV1($payload);
            break;
        case 'tracking_checkpoint_update':
            handleCheckpointUpdateV1($payload);
            break;
        case 'tracking_delete':
            handleTrackingDeleteV1($payload);
            break;
        case 'shipment_create':
            handleShipmentCreateV1($payload);
            break;
        case 'shipment_update':
            handleShipmentUpdateV1($payload);
            break;
        case 'shipment_delete':
            handleShipmentDeleteV1($payload);
            break;
        case 'shipment_cancel':
            handleShipmentCancelV1($payload);
            break;
        case 'shipment_generated':
            handleShipmentGeneratedV1($payload);
            break;
        default:
            logEvent('Unknown V1 event type: ' . $payload['event'], $payload);
    }
}

// V2 Event Handlers
function handleTrackingCreateV2($event) {
    $tracking = $event['tracking'];
    logEvent('V2 Tracking created: ' . $tracking['tracking_number'], $tracking);
}

function handleTrackingUpdateV2($event) {
    $tracking = $event['tracking'];
    logEvent('V2 Tracking updated: ' . $tracking['tracking_number'], $tracking);
}

function handleCheckpointUpdateV2($event) {
    $tracking = $event['tracking'];
    logEvent('V2 Checkpoint updated: ' . $tracking['tracking_number'], $tracking);
    
    if (isset($tracking['latest_checkpoint'])) {
        $checkpoint = $tracking['latest_checkpoint'];
        logEvent('V2 Latest checkpoint: ' . $checkpoint['status'] . ' at ' . $checkpoint['location'], $checkpoint);
    }
}

function handleTrackingDeleteV2($event) {
    $tracking = $event['tracking'];
    logEvent('V2 Tracking deleted: ' . $tracking['tracking_number'], $tracking);
}

function handleShipmentCreateV2($event) {
    $shipment = $event['shipment'];
    logEvent('V2 Shipment created: ' . $shipment['order_number'], $shipment);
}

function handleShipmentUpdateV2($event) {
    $shipment = $event['shipment'];
    logEvent('V2 Shipment updated: ' . $shipment['order_number'], $shipment);
}

function handleShipmentDeleteV2($event) {
    $shipment = $event['shipment'];
    logEvent('V2 Shipment deleted: ' . $shipment['order_number'], $shipment);
}

function handleShipmentCancelV2($event) {
    $shipment = $event['shipment'];
    logEvent('V2 Shipment cancelled: ' . $shipment['order_number'], $shipment);
}

function handleShipmentGeneratedV2($event) {
    $shipment = $event['shipment'];
    logEvent('V2 Shipment generated: ' . $shipment['order_number'], $shipment);
}

// V1 Event Handlers
function handleTrackingCreateV1($payload) {
    $tracking = $payload['tracking'];
    logEvent('V1 Tracking created: ' . $tracking['tracking_number'], $tracking);
}

function handleTrackingUpdateV1($payload) {
    $tracking = $payload['tracking'];
    logEvent('V1 Tracking updated: ' . $tracking['tracking_number'], $tracking);
}

function handleCheckpointUpdateV1($payload) {
    $tracking = $payload['tracking'];
    logEvent('V1 Checkpoint updated: ' . $tracking['tracking_number'], $tracking);
    
    if (isset($tracking['checkpoints']) && !empty($tracking['checkpoints'])) {
        $latestCheckpoint = $tracking['checkpoints'][0]; // First checkpoint is usually the latest
        logEvent('V1 Latest checkpoint: ' . $latestCheckpoint['status'] . ' at ' . $latestCheckpoint['location'], $latestCheckpoint);
    }
}

function handleTrackingDeleteV1($payload) {
    $tracking = $payload['tracking'];
    logEvent('V1 Tracking deleted: ' . $tracking['tracking_number'], $tracking);
}

function handleShipmentCreateV1($payload) {
    $shipment = $payload['shipment'];
    logEvent('V1 Shipment created: ' . $shipment['order_number'], $shipment);
}

function handleShipmentUpdateV1($payload) {
    $shipment = $payload['shipment'];
    logEvent('V1 Shipment updated: ' . $shipment['order_number'], $shipment);
}

function handleShipmentDeleteV1($payload) {
    $shipment = $payload['shipment'];
    logEvent('V1 Shipment deleted: ' . $shipment['order_number'], $shipment);
}

function handleShipmentCancelV1($payload) {
    $shipment = $payload['shipment'];
    logEvent('V1 Shipment cancelled: ' . $shipment['order_number'], $shipment);
}

function handleShipmentGeneratedV1($payload) {
    $shipment = $payload['shipment'];
    logEvent('V1 Shipment generated: ' . $shipment['order_number'], $shipment);
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