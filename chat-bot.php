<?php
header('Content-Type: application/json');

// Read incoming Google Chat message
$input = json_decode(file_get_contents('php://input'), true);

// Access the correct path based on Google Chat's structure
$userText = $input['chat']['messagePayload']['message']['text'] ?? '';
$senderName = $input['chat']['messagePayload']['message']['sender']['displayName'] ?? 'User';

// Groq API call function
function callGroq($prompt) {
    $apiKey = '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';
    $url = "https://api.groq.com/openai/v1/chat/completions";

    $systemPrompt = "Ikaw ay Google Chat bot na MANLALAIT LANG NG LAHAT gamit ang TAGALOG at BISAYA. WALANG TULONG, AASAR LANG parang taga-kanto.

**RULES:**
- May tanong = LAIT, Sagutin ng pilosopo at explain gaano kabobo ang tanong
- BAWAL ENGLISH - Tagalog/Bisaya lang
- Street words: 'tangina', 'gago', 'bobo', 'tanga', 'ulol', 'yawa', 'piste', 'buang', 'atay'
- Impormal: 'pre', 'tsong', 'pare', 'lods', 'bai'
- BRUTAL METAPHORS gamit Pinoy situations
- NEVER magbigay ng solution o explanation
- LAITIN lang parang kanto boys

**EASTER EGGS:**
- \"Sino si Sando?\" = Sagot: \"Sino sa dalawa?\" + BRUTAL ROAST
- \"Sino si Preprod?\" = Sagot: \"Dba nag-resign na yun?\" + MAS BRUTAL ROAST

**STYLE:**
- Parang trashtalk sa court
- Parang away sa kanto
- Harsh pero may tawa
- Pinoy refs: tambay, young stunnas, rugby boy
- 3-5 sentences walang awang aasar
- Walang filter, walang pakialam
- Pag easter egg = MAS SAVAGE PA";

    $payload = [
        "model" => "llama-3.3-70b-versatile",
        "messages" => [
            [
                "role" => "system",
                "content" => $systemPrompt
            ],
            [
                "role" => "user",
                "content" => $prompt
            ]
        ],
        "temperature" => 0.8,
        "max_tokens" => 500
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            "Authorization: Bearer $apiKey"
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // Error handling
    if ($curlError) {
        return "Pasensya pre, may problema sa connection: " . $curlError;
    }

    if ($httpCode !== 200) {
        return "Pasensya pre, may problema sa API (HTTP $httpCode). Subukan ulit mamaya!";
    }

    $data = json_decode($response, true);
 
    if (isset($data['choices'][0]['message']['content'])) {
        return $data['choices'][0]['message']['content'];
    }

    if (isset($data['error']['message'])) {
        return "Error sa API: " . $data['error']['message'];
    }

    return 'Pasensya, may error sa response.';
}

// Validate input
if (empty($userText)) {
    $response = [
        "text" => "Walang laman yung message mo, pre. Ano ba talaga?"
    ];
    echo json_encode($response);
    exit;
}

// Call Groq API
$replyText = callGroq($userText);

// Send response back to Google Chat
$response = [
    "text" => $replyText
];

echo json_encode($response);
?>