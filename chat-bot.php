<?php
// Ensure NO spaces or lines exist above the <?php tag
ob_start(); 

// 1. Set Headers
header('Content-Type: application/json; charset=UTF-8');

// 2. Silence Errors (They break JSON)
error_reporting(0); 
ini_set('display_errors', 0);

// 3. Get Input
$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['type'])) {
    $userText = $input['message']['text'] ?? 'Hi!';
    // Clean @mention
    $cleanText = trim(preg_replace('/@[^\s]+/', '', $userText));
    
    // 4. The strict "Just Text" format for Add-on configured Apps
    $response = [
        "text" => "Echo: " . $cleanText,
        "actionResponse" => [
            "type" => "NEW_MESSAGE"
        ]
    ];
} else {
    $response = ["text" => "Service is running."];
}

// 5. Final Output Safety
$output = json_encode($response);

// Clear any accidental output (notices, spaces, etc.)
ob_end_clean(); 

// Send exactly the JSON and nothing else
echo $output;
exit;