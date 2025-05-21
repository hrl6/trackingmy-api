<?php

class TrackingClient
{
    private $apiKey;
    private $baseUrl = 'https://seller.tracking.my/api/v2';
    private $verifySsl = true;
    
    public function __construct($apiKey, $verifySsl = true)
    {
        $this->apiKey = $apiKey;
        $this->verifySsl = $verifySsl;
    }
    
    /**
     * Get all trackings
     */
    public function getTrackings()
    {
        return $this->request('GET', '/trackings');
    }
    
    /**
     * Create a tracking (use the correct method based on API error)
     * Let's try using GET with query parameters instead of POST with JSON body
     */
    public function createTracking($trackingData)
    {
        // Convert tracking data to query string
        $queryString = http_build_query($trackingData);
        return $this->request('GET', '/trackings/create?' . $queryString);
    }
    
    /**
     * Get tracking information
     */
    public function getTracking($courier, $trackingNumber)
    {
        return $this->request('GET', "/trackings/{$courier}/{$trackingNumber}");
    }
    
    /**
     * Delete a tracking
     */
    public function deleteTracking($courier, $trackingNumber)
    {
        return $this->request('DELETE', "/trackings/{$courier}/{$trackingNumber}");
    }
    
    /**
     * Register webhook
     */
    public function registerWebhook($url, $events, $secretKey)
    {
        $data = [
            'url' => $url,
            'events' => $events,
            'secret_key' => $secretKey
        ];
        
        return $this->request('PUT', '/webhook', $data);
    }
    
    /**
     * Get webhook configuration
     */
    public function getWebhookConfig()
    {
        return $this->request('GET', '/webhook');
    }
    
    /**
     * Get list of supported couriers
     */
    public function getCouriers()
    {
        return $this->request('GET', '/couriers');
    }
    
    /**
     * Send request to the API
     */
    private function request($method, $endpoint, $data = null)
    {
        $url = $this->baseUrl . $endpoint;
        
        // For GET requests with data, append as query parameters
        if ($method === 'GET' && $data !== null && !strpos($endpoint, '?')) {
            $url .= '?' . http_build_query($data);
            $data = null; // Clear data since we've added it to the URL
        }
        
        $ch = curl_init($url);
        
        $headers = [
            'Accept: application/json',
            'Tracking-Api-Key: ' . $this->apiKey
        ];
        
        if ($data !== null && in_array($method, ['POST', 'PUT'])) {
            $headers[] = 'Content-Type: application/json';
        }
        
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => $this->verifySsl,
            CURLOPT_SSL_VERIFYHOST => $this->verifySsl ? 2 : 0,
            CURLOPT_VERBOSE => true // Enable verbose output for debugging
        ];
        
        if ($data !== null && in_array($method, ['POST', 'PUT'])) {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }
        
        curl_setopt_array($ch, $options);
        
        // Create a file to store verbose information
        $verbose = fopen('php://temp', 'w+');
        curl_setopt($ch, CURLOPT_STDERR, $verbose);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            rewind($verbose);
            $verboseLog = stream_get_contents($verbose);
            fclose($verbose);
            
            throw new \Exception('cURL error: ' . curl_error($ch) . "\nVerbose log: " . $verboseLog);
        }
        
        rewind($verbose);
        $verboseLog = stream_get_contents($verbose);
        fclose($verbose);
        
        curl_close($ch);
        
        $responseData = json_decode($response, true);
        
        return [
            'code' => $httpCode,
            'data' => $responseData,
            'raw' => $response,
            'verbose' => $verboseLog
        ];
    }
}