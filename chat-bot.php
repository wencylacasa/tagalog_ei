<?php
header('Content-Type: application/json');

// Read the incoming Google Chat message
$input = json_decode(file_get_contents('php://input'), true);
$userText = $input['message']['text'] ?? '';
$senderName = $input['message']['sender']['displayName'] ?? 'User';

// Function to call Gemini Flash 2.5
function callGemini($prompt) {
    $apiKey = 'AIzaSyCZ0BFk1m0axKLyd_ZUBaFD62Wxdl7BBYQ'; // <-- replace with your key
    $url = "https://generativelanguage.googleapis.com/v1beta/models/flash-2.5:generateContent?key=$apiKey";

    // Force Gemini to respond in Tagalog, even if input is English or informal
    $systemPrompt = "Ikaw ay isang helpful na Google Chat bot. Sumagot lamang sa Tagalog, malinaw at natural kahit mag-barubal o maghalo ang wika.";

    $payload = [
        "contents" => [[
            "parts" => [[ "text" => "$systemPrompt\n\nUser: $prompt" ]]
        ]]
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return data;
    return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Pasensya, may error.';
}

// Call Gemini directly with whatever the user typed
$replyText = callGemini($userText);

// Send the response back to Google Chat
echo json_encode([
    "text" => $replyText
]);
