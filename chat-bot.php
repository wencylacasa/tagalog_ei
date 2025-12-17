<?php
// DIAGNOSTIC VERSION - Maximum logging
// Make sure NO OUTPUT before this line (no spaces, no BOM)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display, only log

// Step 1: Log that script started
error_log("==== SCRIPT START ====");
error_log("Time: " . date('Y-m-d H:i:s'));
error_log("Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Content-Type header: " . ($_SERVER['CONTENT_TYPE'] ?? 'NOT SET'));

// Step 2: Set proper headers FIRST
header('Content-Type: application/json; charset=UTF-8');
http_response_code(200);

// Step 3: Get raw input
$rawInput = file_get_contents('php://input');
error_log("Raw input length: " . strlen($rawInput));
error_log("Raw input: " . substr($rawInput, 0, 500)); // First 500 chars

// Step 4: Try to parse JSON
$input = json_decode($rawInput, true);
$jsonError = json_last_error();

if ($jsonError !== JSON_ERROR_NONE) {
    error_log("JSON PARSE ERROR: " . json_last_error_msg());
    echo json_encode(['text' => 'JSON parse error']);
    exit;
}

error_log("Parsed type: " . ($input['type'] ?? 'NULL'));

// PING endpoint to keep server warm
if ($_SERVER['REQUEST_URI'] === '/ping') {
    error_log("PING received - server is warm");
    echo json_encode(['status' => 'alive', 'time' => date('Y-m-d H:i:s')]);
    exit;
}

// Step 5: Handle events
try {
    // ADDED_TO_SPACE
    if (isset($input['type']) && $input['type'] === 'ADDED_TO_SPACE') {
        error_log("Handling ADDED_TO_SPACE");
        $response = ['text' => 'Hello! Bot is alive! 🚀'];
        $json = json_encode($response);
        error_log("Response JSON: " . $json);
        echo $json;
        error_log("Response sent successfully");
        exit;
    }
    
    // MESSAGE
    if (isset($input['type']) && $input['type'] === 'MESSAGE') {
        error_log("Handling MESSAGE");
        
        $userText = $input['message']['text'] ?? '';
        error_log("Original text: " . $userText);
        
        // Remove @mentions
        $cleanText = preg_replace('/@[^\s]+/', '', $userText);
        $cleanText = trim($cleanText);
        error_log("Clean text: " . $cleanText);
        
        $response = [
            'text' => "Echo: " . $cleanText . " [Bot working at " . date('H:i:s') . "]"
        ];
        
        $json = json_encode($response);
        error_log("Response JSON: " . $json);
        echo $json;
        error_log("Response sent successfully");
        exit;
    }
    
    // REMOVED_FROM_SPACE
    if (isset($input['type']) && $input['type'] === 'REMOVED_FROM_SPACE') {
        error_log("Handling REMOVED_FROM_SPACE - returning empty");
        exit;
    }
    
    // Unknown event
    error_log("UNKNOWN EVENT TYPE: " . ($input['type'] ?? 'NULL'));
    echo json_encode(['text' => 'Unknown event: ' . ($input['type'] ?? 'null')]);
    exit;
    
} catch (Exception $e) {
    error_log("EXCEPTION: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode(['text' => 'Error occurred']);
    exit;
}

error_log("==== SCRIPT END (should not reach here) ====");