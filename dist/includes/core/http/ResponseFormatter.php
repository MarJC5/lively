<?php

namespace Lively\Core\Http;

class ResponseFormatter
{
    /**
     * Send JSON response
     * 
     * @param array $data The data to send as JSON
     * @param int $statusCode HTTP status code (default: 200)
     */
    public function sendJsonResponse($data, $statusCode = 200)
    {
        // Override status code if provided in the data
        if (isset($data['status'])) {
            $statusCode = $data['status'];
            unset($data['status']); // Remove from response
        }
        
        // Prevent any output before headers
        if (ob_get_length()) ob_clean();
        
        // Set status code
        http_response_code($statusCode);
        
        // Set headers
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        
        // Send response
        echo json_encode($data);
        exit;
    }
    
    /**
     * Send a success response
     * 
     * @param mixed $data The data to include in the response
     * @param int $statusCode HTTP status code (default: 200)
     */
    public function success($data = null, $statusCode = 200)
    {
        $response = [
            'success' => true
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        $this->sendJsonResponse($response, $statusCode);
    }
    
    /**
     * Send an error response
     * 
     * @param string $message Error message
     * @param int $statusCode HTTP status code (default: 400)
     * @param mixed $additionalData Additional data to include
     */
    public function error($message, $statusCode = 400, $additionalData = null)
    {
        $response = [
            'success' => false,
            'error' => $message
        ];
        
        if ($additionalData !== null) {
            $response['data'] = $additionalData;
        }
        
        $this->sendJsonResponse($response, $statusCode);
    }
} 