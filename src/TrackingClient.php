<?php

class TrackingClient
{
    private $apiKey;
    private $baseUrlV1 = 'https://seller.tracking.my/api/v1';
    private $baseUrlV2 = 'https://seller.tracking.my/api/v2';
    private $verifySsl = true;
    
    public function __construct($apiKey, $verifySsl = true)
    {
        $this->apiKey = $apiKey;
        $this->verifySsl = $verifySsl;
    }
    
    /**
     * Get all trackings (v1)
     */
    public function getTrackings()
    {
        return $this->request('GET', '/trackings', null, 'v1');
    }
    
    /**
     * Create a tracking (v1 - uses POST with JSON body)
     */
    public function createTracking($trackingData)
    {
        return $this->request('POST', '/trackings', $trackingData, 'v1');
    }
    
    /**
     * Get specific tracking information (v1)
     */
    public function getTracking($courier, $trackingNumber)
    {
        return $this->request('GET', "/trackings/{$courier}/{$trackingNumber}", null, 'v1');
    }
    
    /**
     * Delete a tracking (v1)
     */
    public function deleteTracking($courier, $trackingNumber)
    {
        return $this->request('DELETE', "/trackings/{$courier}/{$trackingNumber}", null, 'v1');
    }
    
    /**
     * Register webhook (v2)
     */
    public function registerWebhook($url, $events, $secretKey)
    {
        $data = [
            'url' => $url,
            'events' => $events,
            'secret_key' => $secretKey
        ];
        
        return $this->request('PUT', '/webhook', $data, 'v2');
    }
    
    /**
     * Get webhook configuration (v2)
     */
    public function getWebhookConfig()
    {
        return $this->request('GET', '/webhook', null, 'v2');
    }
    
    /**
     * Get list of supported couriers (v1)
     */
    public function getCouriers()
    {
        return $this->request('GET', '/couriers', null, 'v1');
    }
    
    /**
     * Send request to the API
     */
    private function request($method, $endpoint, $data = null, $version = 'v1')
    {
        $baseUrl = ($version === 'v2') ? $this->baseUrlV2 : $this->baseUrlV1;
        $url = $baseUrl . $endpoint;
        
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
        ];
        
        if ($data !== null && in_array($method, ['POST', 'PUT'])) {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }
        
        curl_setopt_array($ch, $options);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception('cURL error: ' . $error);
        }
        
        curl_close($ch);
        
        $responseData = json_decode($response, true);
        
        return [
            'code' => $httpCode,
            'data' => $responseData,
            'raw' => $response
        ];
    }
}