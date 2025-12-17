<?php
// CRITICAL: Must respond within 30 seconds or Google Chat times out
header('Content-Type: application/json');

// Enable error logging for debugging
ini_set('display_errors', 0);
error_log("=== Google Chat Request Received ===");

// Read the incoming Google Chat message
$rawInput = file_get_contents('php://input');
error_log("Raw Input: " . $rawInput);

$input = json_decode($rawInput, true);

// Handle VERIFICATION request from Google Chat
if (isset($input['type']) && $input['type'] === 'ADDED_TO_SPACE') {
    error_log("Bot added to space");
    echo json_encode([
        "text" => "Ano pre? Nandito na ako! PHP programmer ka ba? 😏"
    ]);
    exit;
}

// Handle REMOVED event
if (isset($input['type']) && $input['type'] === 'REMOVED_FROM_SPACE') {
    error_log("Bot removed from space");
    exit;
}

// Extract message text
$userText = $input['message']['text'] ?? '';
$senderName = $input['message']['sender']['displayName'] ?? 'User';

error_log("User: $senderName | Message: $userText");

// Function to call Gemini 2.5 Flash with optimizations
function callGemini($prompt) {
    $apiKey = 'AIzaSyCNps-O9_4WRQ3OPFP9L_zgiW8urna221M';
    
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=$apiKey";

    // SHORTENED system prompt (less tokens = faster response)
    $systemPrompt = "Ikaw Google Chat bot na MANG-LAIT LANG ng PHP programmers. TAGALOG/BISAYA ONLY, impormal kanto style.

RULES:
- PHP question = LAIT, walang help
- Gamitin: 'tangina', 'gago', 'bobo', 'yawa', 'piste', 'buang'
- Street talk: 'pre', 'tsong', 'bai', 'lods'
- Pinoy refs: jeep, trike, kanto, basketball
- 2-3 sentences lang, brutal pero maikli
- BAWAL English

EASTER EGGS:
- \"Sino si Sando?\" → \"Sino sa dalawa?\" + roast
- \"Sino si Preprod?\" → \"Dba nag-resign na yun?\" + roast

Pag HINDI PHP, normal Tagalog/Bisaya lang.";

    $payload = [
        "contents" => [[
            "parts" => [[ "text" => "$systemPrompt\n\nUser: $prompt" ]]
        ]],
        "generationConfig" => [
            "maxOutputTokens" => 150,  // Limit output for speed
            "temperature" => 0.9,
            "topP" => 0.8
        ]
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 4,              // 4 second timeout
        CURLOPT_CONNECTTIMEOUT => 2,       // 2 second connect timeout
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0  // Use HTTP/2 for speed
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
   
    // Quick error handling
    if ($httpCode !== 200 || $error) {
        error_log("Gemini Error: $response");
        return "Pasensya pre, antay lang sandali!";
    }

    $data = json_decode($response, true);
    
    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        return $data['candidates'][0]['content']['parts'][0]['text'];
    }
    
    return 'Error sa response pre.';
}

// Call Gemini
$replyText = callGemini($userText);

error_log("Response: " . $replyText);

// Send response
echo json_encode([
    "text" => $replyText
]);

// Make sure no extra output
exit;