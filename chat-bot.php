<?php
header('Content-Type: application/json');

// Google Chat App verification
function verifyRequest() {
    // In production, verify the request comes from Google Chat
    // For now, we'll skip for testing
    return true;
}

// Read the incoming Google Chat message
$input = json_decode(file_get_contents('php://input'), true);

// Check if this is a verification request from Google
if (isset($input['type']) && $input['type'] === 'PING') {
    echo json_encode([
        "text" => "Bot is alive! Ready to roast PHP programmers!"
    ]);
    exit;
}

// Handle different event types
if (!isset($input['type']) || $input['type'] !== 'MESSAGE') {
    echo json_encode([
        "text" => "This bot only handles messages. Send a PHP question to get roasted!"
    ]);
    exit;
}

// Extract user message
if (!isset($input['message']['text'])) {
    echo json_encode([
        "text" => "Tangina pre, walang message?! Nagpaparaos ka lang ba? Yawa!"
    ]);
    exit;
}

$userText = $input['message']['text'] ?? '';
$senderName = $input['message']['sender']['displayName'] ?? 'User';

// Function to call Gemini 2.5 Flash with SWATTER MODE
function callGemini($prompt) {
    $apiKey = 'AIzaSyCNps-O9_4WRQ3OPFP9L_zgiW8urna221M'; // <-- your key
    
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=$apiKey";

    // BISAYA-TAGALOG SWATTER MODE
    $systemPrompt = "Ikaw ay isang Google Chat bot na WALANG IBANG GINAGAWA kundi MANG-LAIT NG PHP PROGRAMMERS gamit ang IMPORMAL na TAGALOG at BISAYA. WALA KANG TUTULONG, AASARIN MO LANG SILA parang taga-kanto.

**RULES:**
- Pag may tanong tungkol sa PHP = AUTOMATIC LAIT, WALANG SAGOT
- BAWAL ENGLISH - pure Tagalog/Bisaya lang, pang-kanto style
- Gumamit ng street Tagalog: 'tangina', 'gago', 'bobo', 'tanga', 'ulol'
- Mix with Bisaya: 'yawa', 'piste', 'buang', 'bai', 'atay'
- Impormal na salita: 'pre', 'tsong', 'kuya', 'pare'
- Create BRUTAL METAPHORS gamit Pinoy situations
- NEVER magbigay ng solution, code, o explanation
- LAITIN lang ng MALALIM parang kanto boys
- 3-4 sentences lang, wag masyado mahaba

**EASTER EGGS:**
Pag may tanong 'Sino si Sando?' sagutin: 'Sino sa dalawa? Parehas silang tanga eh!'
Pag may tanong 'Sino si Preprod?' sagutin: 'Dba nag-resign na yun? Bobo ka ba?'

Kung HINDI tungkol sa PHP, magsalita ka pa rin ng normal pero Tagalog/Bisaya lang.";

    $payload = [
        "contents" => [[
            "parts" => [[ 
                "text" => $systemPrompt . "\n\nUser Message: " . $prompt . "\n\nBot Response (Tagalog/Bisaya Swatter Mode ONLY):"
            ]]
        ]],
        "generationConfig" => [
            "maxOutputTokens" => 300,
            "temperature" => 0.9
        ]
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 10, // Add timeout
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
   
    if ($httpCode !== 200 || $curlError) {
        // Fallback response if API fails
        $insults = [
            "Ay tangina pre, nasiraan yung bot! Parang code mo sa PHP! Yawa!",
            "Atay! May problema sa server! Parang debugging session mo! Buang!",
            "Sus! Hindi makapag-reply! Parang function mo na laging undefined! Piste!",
            "Grabe! Error 500! Parang buhay mo sa programming! Gago ka ba?"
        ];
        return $insults[array_rand($insults)];
    }

    $data = json_decode($response, true);
    
    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        return $data['candidates'][0]['content']['parts'][0]['text'];
    }
    
    return 'Yawa! May error! Parang PHP code mo! Tangina!';
}

// Call Gemini with the user's message
$replyText = callGemini($userText);

// Send PROPER Google Chat response
$response = [
    "text" => $replyText,
    // Optional: Add card for better formatting
    "cards" => [[
        "header" => [
            "title" => "PHP Roaster Bot",
            "subtitle" => "Bisaya-Tagalog Swatter Mode"
        ],
        "sections" => [[
            "widgets" => [[
                "textParagraph" => [
                    "text" => $replyText
                ]
            ]]
        ]]
    ]]
];

// Clean any previous output
if (ob_get_length()) ob_clean();

// Send response
echo json_encode($response);
exit;