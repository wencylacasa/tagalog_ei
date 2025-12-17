<?php
header('Content-Type: application/json');

// Read incoming Google Chat message
$input = json_decode(file_get_contents('php://input'), true);
$userText = $input['message']['text'] ?? '';
$senderName = $input['message']['sender']['displayName'] ?? 'User';

// Call Groq
$replyText = callGroq($input);

// Wrap response for Google Chat 2nd-gen (Cloud Functions)
$response = [
    "hostAppDataAction" => [
        "chatDataAction" => [
            "createMessageAction" => [
                "message" => [
                    "text" => $replyText
                ]
            ]
        ]
    ]
];


// Send JSON back
echo json_encode($response);