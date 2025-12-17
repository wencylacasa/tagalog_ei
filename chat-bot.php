<?php
ob_start(); // Prevent accidental whitespace leakage
header('Content-Type: application/json; charset=UTF-8');

error_reporting(E_ALL);
ini_set('display_errors', 0);

// 1. Get Input
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

// 2. Fallback for empty/invalid JSON
if (!$input) {
    ob_end_clean();
    echo json_encode(['text' => 'Server active, but no valid JSON received.']);
    exit;
}

// 3. Extract Message Logic
$eventType = $input['type'] ?? 'MESSAGE';
$userText = $input['message']['text'] ?? 'Hello!';
$cleanText = trim(preg_replace('/@[^\s]+/', '', $userText)); // Strip @bot-name
$displayMessage = "Echo: " . $cleanText . " [" . date('H:i:s') . "]";

// 4. Construct the Universal Response
// This includes fields for both standard Chat and Workspace Add-ons
$response = [
    // Format A: Standard Chat Text
    "text" => $displayMessage,

    // Format B: Workspace Add-on / Card V2 structure (Required by your specific error)
    "actionResponse" => [
        "type" => "NEW_MESSAGE"
    ],
    "cardsV2" => [
        [
            "cardId" => "replyCard",
            "card" => [
                "header" => [
                    "title" => "Bot Response",
                    "subtitle" => "Tagalog-EI Bot"
                ],
                "sections" => [
                    [
                        "widgets" => [
                            [
                                "textParagraph" => [
                                    "text" => $displayMessage
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ]
];

// 5. Clean and Send
$jsonOutput = json_encode($response);
ob_end_clean(); // Discard any hidden warnings/notices
echo $jsonOutput;
exit;