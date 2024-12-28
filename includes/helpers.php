<?php
function sendJsonResponse($data, $statusCode = 200) {
    // Clear any previous output
    if (ob_get_length()) ob_clean();
    
    try {
        // Set headers
        header_remove();
        http_response_code($statusCode);
        header('Content-Type: application/json');
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        
        // Encode response
        $json = json_encode($data);
        if ($json === false) {
            throw new Exception(json_last_error_msg());
        }
        
        // Send response
        echo $json;
    } catch (Exception $e) {
        error_log("Error sending JSON response: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Server error: Failed to encode response'
        ]);
    }
    exit;
}

function debugLog($message, $data = null) {
    $logMessage = date('Y-m-d H:i:s') . " - " . $message;
    if ($data !== null) {
        $logMessage .= " - Data: " . json_encode($data);
    }
    error_log($logMessage);
} 