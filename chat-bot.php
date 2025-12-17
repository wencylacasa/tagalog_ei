<?php
// 1. MUST be the very first line. No spaces or empty lines above this.
ob_start(); // Start output buffering to catch accidental echo/whitespace

error_reporting(E_ALL);
ini_set('display_errors', 0); // Log errors to file, never to the response body

// 2. Set headers immediately
header('Content-Type: application/json; charset=UTF-8');

// Configuration/Logging
$logFile = 'chat_bot_debug.log'; // Ensure your server has write access
function debug_log($msg) {
    global $logFile;
    error_log("[" . date('Y-m-d H:i:s') . "] " . $msg . "\n", 3, $logFile);
}

debug_log("==== NEW REQUEST RECEIVED ====");

// 3. Get and validate input
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    debug_log("JSON ERROR: " . json_last_error_msg());
    ob_end_clean(); // Discard anything in buffer
    http_response_code(400);
    echo json_encode(['text' => 'Invalid JSON received']);
    exit;
}

$response = [];
$eventType = $input['type'] ?? 'UNKNOWN';
debug_log("Event Type: " . $eventType);

// 4. Handle Logic
try {
    switch ($eventType) {
        case 'ADDED_TO_SPACE':
            $response = ['text' => 'Thanks for adding me! 🚀'];
            break;

        case 'MESSAGE':
            $userText = $input['message']['text'] ?? '';
            // Clean up the @mention
            $cleanText = trim(preg_replace('/@[^\s]+/', '', $userText));
            $response = [
                'text' => "Echo: " . $cleanText . " [Processed at " . date('H:i:s') . "]"
            ];
            break;

        case 'REMOVED_FROM_SPACE':
            debug_log("Bot removed from space.");
            ob_end_clean();
            exit;

        default:
            $response = ['text' => 'Event received: ' . $eventType];
            break;
    }
} catch (Exception $e) {
    debug_log("EXCEPTION: " . $e->getMessage());
    $response = ['text' => 'Internal server error occurred.'];
}

// 5. Final Output Cleanup
$jsonResponse = json_encode($response);

// Clear the buffer to ensure NO whitespace or warnings are sent
ob_end_clean(); 

// Send the pure JSON
echo $jsonResponse;
debug_log("Sent Response: " . $jsonResponse);
exit;