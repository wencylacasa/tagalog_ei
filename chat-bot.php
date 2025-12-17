<?php
// TEST VERSION - No external calls, instant response
header('Content-Type: application/json; charset=UTF-8');

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

// Log for debugging
error_log("==== GCHAT REQUEST ====");
error_log("Raw: " . $rawInput);
error_log("Type: " . ($input['type'] ?? 'NULL'));

// ADDED_TO_SPACE event
if (isset($input['type']) && $input['type'] === 'ADDED_TO_SPACE') {
    echo json_encode(['text' => 'Kamusta pre! Nandito na ako! 😎']);
    exit;
}

// MESSAGE event
if (isset($input['type']) && $input['type'] === 'MESSAGE') {
    $text = $input['message']['text'] ?? '';
    
    // Remove bot mention
    $text = preg_replace('/@[^\s]+/', '', $text);
    $text = trim($text);
    
    error_log("Message: " . $text);
    
    // INSTANT RESPONSE - No Gemini call yet
    echo json_encode([
        'text' => "Echo: $text (PHP bot working!)"
    ]);
    exit;
}

// REMOVED_FROM_SPACE - must return empty
if (isset($input['type']) && $input['type'] === 'REMOVED_FROM_SPACE') {
    http_response_code(200);
    exit;
}

// Unknown event
error_log("Unknown type: " . ($input['type'] ?? 'NONE'));
echo json_encode(['text' => 'Unknown event']);
exit;