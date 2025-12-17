<?php
ob_start(); 
header('Content-Type: application/json; charset=UTF-8');

error_reporting(E_ALL);
ini_set('display_errors', 0);

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (!$input) {
    ob_end_clean();
    echo json_encode(['text' => 'No input received']);
    exit;
}

// Extract message
$userText = $input['message']['text'] ?? 'Hello';
$cleanText = trim(preg_replace('/@[^\s]+/', '', $userText));

// The absolute simplest valid response for a Chat App
$response = [
    "text" => "Echo: " . $cleanText,
    "actionResponse" => [
        "type" => "NEW_MESSAGE"
    ]
];

$jsonOutput = json_encode($response);
ob_end_clean(); 
echo $jsonOutput;
exit;