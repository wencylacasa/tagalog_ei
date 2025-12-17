<?php
// CRITICAL: Must respond within 30 seconds or Google Chat times out
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_log("=== Google Chat Request Received ===");

// Read incoming request
$rawInput = file_get_contents('php://input');
error_log("Raw Input: " . $rawInput);
$input = json_decode($rawInput, true);

// Handle bot being added to space
if (isset($input['type']) && $input['type'] === 'ADDED_TO_SPACE') {
    error_log("Bot added to space");
    echo json_encode([
        "text" => "Ano pre? Nandito na ako! PHP programmer ka ba? 😏"
    ]);
    exit;
}

// Handle bot being removed
if (isset($input['type']) && $input['type'] === 'REMOVED_FROM_SPACE') {
    error_log("Bot removed from space");
    exit;
}

// Handle user messages
if (isset($input['type']) && $input['type'] === 'MESSAGE') {
    $userText = $input['message']['text'] ?? '';
    $userName = $input['message']['sender']['displayName'] ?? 'User';
    
    error_log("Message from $userName: $userText");
    
    // Call Gemini for response
    $response = callGemini($userText);
    
    echo json_encode([
        "text" => $response
    ]);
    exit;
}

// Default response
echo json_encode(["text" => "Unknown event type"]);
exit;

function callGemini($prompt) {
    $apiKey = 'AIzaSyCNps-O9_4WRQ3OPFP9L_zgiW8urna221M';
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=$apiKey";

    // Shortened system prompt for speed
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
            "parts" => [[ "text" => "$systemPrompt\n\nUser: $prompt" ]]
        ]],
        "generationConfig" => [
            "maxOutputTokens" => 150,
            "temperature" => 0.9
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
    curl_close($ch);
   
    if ($httpCode !== 200) {
        error_log("Gemini Error: $response");
        return "Pasensya pre, may problema sa AI!";
    }

    $data = json_decode($response, true);
    
    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        return $data['candidates'][0]['content']['parts'][0]['text'];
    }
    
    return 'Error sa response pre.';
}