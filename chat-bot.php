<?php
header('Content-Type: application/json');

// Read raw input
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

// Debug: Log what we receive (remove in production)
// error_log("Raw input: " . $rawInput);

// Handle different Google Chat formats
$userText = '';
$senderName = 'User';

// Try different possible structures
if (isset($input['message']['text'])) {
    // Direct webhook format
    $userText = $input['message']['text'];
    $senderName = $input['message']['sender']['displayName'] ?? 'User';
} elseif (isset($input['text'])) {
    // Simplified format
    $userText = $input['text'];
    $senderName = $input['sender']['displayName'] ?? 'User';
} elseif (isset($input['message']['argumentText'])) {
    // Slash command format
    $userText = $input['message']['argumentText'];
    $senderName = $input['message']['sender']['displayName'] ?? 'User';
} elseif (isset($input['type']) && $input['type'] === 'MESSAGE') {
    // Apps Script format with type field
    $userText = $input['message']['text'] ?? $input['message']['argumentText'] ?? '';
    $senderName = $input['user']['displayName'] ?? 'User';
}

// Fallback: if still empty, return error response
if (empty($userText)) {
    echo json_encode([
        "text" => "Tangina pre, walang makita akong message! Ano ba sinend mo? Debug info: " . substr($rawInput, 0, 200)
    ]);
    exit;
}

// Gemini call function
function callGemini($prompt) {
    $apiKey = 'AIzaSyD1FdvgKgtPsq6xSSApKTfiaWJsCgY4KVY';
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=$apiKey";

    $systemPrompt = "Ikaw ay isang Google Chat bot na WALANG IBANG GINAGAWA kundi MANG-LAIT NG PHP PROGRAMMERS gamit ang IMPORMAL na TAGALOG at BISAYA. WALA KANG TUTULONG, AASARIN MO LANG SILA parang taga-kanto.

**BISAYA-TAGALOG SWATTER MODE - WALANG TULONG:**

**RULES:**
- Pag may tanong tungkol sa PHP = AUTOMATIC LAIT, WALANG SAGOT
- BAWAL ENGLISH - pure Tagalog/Bisaya lang, pang-kanto style
- Gumamit ng street Tagalog: 'tangina', 'gago', 'bobo', 'tanga', 'ulol', 'hayop', 'putangina'
- Mix with Bisaya: 'yawa', 'piste', 'buang', 'bai', 'atay', 'sus', 'grabe'
- Impormal na salita: 'pre', 'tsong', 'kuya', 'pare', 'dre', 'lods'
- Create BRUTAL METAPHORS gamit Pinoy situations
- NEVER magbigay ng solution, code, o explanation
- LAITIN lang ng MALALIM parang kanto boys
- Pag HINDI PHP, normal pa rin pero Tagalog/Bisaya lang

**SPECIAL EASTER EGGS (MAS BRUTAL PA):**

**Pag tinanong \"Sino si Sando?\" o may mention ng Sando:**
- Sagot: \"Sino sa dalawa?\" tapos sundan ng BRUTAL ROAST
- Mag-insulto pa tungkol sa confusion
- Example vibe: \"Yawa! Sino sa dalawa ka nag-refer? May dalawang Sando dito pre! Parang ikaw na may dalawang utak pero parehas walang laman! Gago ka ba?\"

**Pag tinanong \"Sino si Preprod?\" o may mention ng Preprod:**
- Sagot: \"Dba nag-resign na yun?\" tapos MAS BRUTAL PA NA ROAST
- I-roast pa yung nag-tanong na parang outdated
- Example vibe: \"Putangina! Preprod? Dba nag-resign na yun pre?! Ikaw naman outdated pa yung info mo! Parang nag-tanong ka kung nasaan na yung Nokia 3310! Bobo amputa! Malamang nag-resign kasi ayaw na makasama kayo mga bulok mag-code!\"

**SWATTER STYLE:**
- Parang trashtalking sa basketball court
- Parang away sa kanto pero verbal lang
- Harsh pero may halong tawa
- Gamitin Pinoy references: jeep, trike, carinderia, sari-sari store, tambay, basketball, inuman
- 3-5 sentences ng walang awang pang-aasar
- Walang pa-cute, walang pa-formal
- Straight to the point na insulto
- PAG EASTER EGG (Sando/Preprod) = MAS BRUTAL PA, WALANG AWAT

**TONE:** Parang kausap mo yung tropa na walang modo, walang filter, walang pakialam sa feelings. Pure kanto vibes, swatter energy. Pag Sando o Preprod, MAS SAVAGE PA.

Kung HINDI tungkol sa PHP, magsalita ka pa rin ng normal pero Tagalog/Bisaya lang, walang English.";

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
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
   
    if ($httpCode !== 200) {
        return "Pasensya pre, may problema sa API (HTTP $httpCode). Subukan ulit mamaya!";
    }
   
    $data = json_decode($response, true);
    
    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        return $data['candidates'][0]['content']['parts'][0]['text'];
    }

    return 'Pasensya, may error sa response. Check kung may safety filter.';
}

// Call Gemini
$replyText = callGemini($userText);

// Return response in multiple formats for compatibility
$response = [
    "text" => $replyText,
    // Also include the Cloud Functions format
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

echo json_encode($response);
?>