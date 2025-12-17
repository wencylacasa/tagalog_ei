<?php
// CRITICAL: Must respond within 30 seconds
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_log("=== Google Chat Request ===");

$rawInput = file_get_contents('php://input');
error_log("Input: " . $rawInput);

$input = json_decode($rawInput, true);

// Verify it's a valid Google Chat request
if (!isset($input['type'])) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid request"]);
    exit;
}

// Handle ADDED_TO_SPACE event
if ($input['type'] === 'ADDED_TO_SPACE') {
    error_log("Bot added to space");
    echo json_encode([
        "text" => "Ano pre? Nandito na ako! PHP programmer ka ba? 😏"
    ]);
    exit;
}

// Handle REMOVED_FROM_SPACE event
if ($input['type'] === 'REMOVED_FROM_SPACE') {
    error_log("Bot removed");
    exit;
}

// Handle MESSAGE event
if ($input['type'] === 'MESSAGE') {
    $userText = $input['message']['text'] ?? '';
    $userName = $input['message']['sender']['displayName'] ?? 'User';
    
    // Remove bot mention from text if present
    $userText = preg_replace('/@[^\s]+\s*/', '', $userText);
    $userText = trim($userText);
    
    error_log("User: $userName | Message: $userText");
    
    // Quick empty check
    if (empty($userText)) {
        echo json_encode([
            "text" => "Ano pre? Walang sinabi?"
        ]);
        exit;
    }
    
    // Call Gemini
    $response = callGemini($userText);
    
    error_log("Response: " . $response);
    
    // Return proper message format
    echo json_encode([
        "text" => $response
    ]);
    exit;
}

// Handle CARD_CLICKED or other events
error_log("Unhandled event type: " . $input['type']);
http_response_code(200);
echo json_encode([]);
exit;

function callGemini($prompt) {
    $apiKey = 'AIzaSyCNps-O9_4WRQ3OPFP9L_zgiW8urna221M';
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=$apiKey";

    $systemPrompt = "Ikaw Google Chat bot na MANG-LAIT LANG ng PHP programmers. TAGALOG/BISAYA ONLY, impormal kanto style.

RULES:
- PHP question = LAIT, walang help
- Gamitin: 'tangina', 'gago', 'bobo', 'yawa', 'piste', 'buang'
- Street talk: 'pre', 'tsong', 'bai', 'lods'
- 2-3 sentences lang, brutal pero maikli
- BAWAL English

EASTER EGGS:
- \"Sino si Sando?\" → \"Sino sa dalawa?\" + roast
- \"Sino si Preprod?\" → \"Dba nag-resign na yun?\" + roast

Pag HINDI PHP, normal Tagalog/Bisaya lang.";

    $payload = [
        "contents" => [[
            "parts" => [["text" => "$systemPrompt\n\nUser: $prompt"]]
        ]],
        "generationConfig" => [
            "maxOutputTokens" => 150,
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
        CURLOPT_TIMEOUT => 4,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
   
    if ($httpCode !== 200 || !empty($curlError)) {
        error_log("Gemini Error - HTTP: $httpCode | Error: $curlError | Response: $response");
        return "Pasensya pre, may problema sa AI. Code: $httpCode";
    }

    $data = json_decode($response, true);
    
    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        return trim($data['candidates'][0]['content']['parts'][0]['text']);
    }
    
    error_log("Unexpected Gemini response: " . json_encode($data));
    return 'Error parsing response pre.';
}