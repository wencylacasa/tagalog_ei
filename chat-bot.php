<?php
header('Content-Type: application/json');

// Read incoming Google Chat message
$input = json_decode(file_get_contents('php://input'), true);
$userText = $input['message']['text'] ?? '';
$senderName = $input['message']['sender']['displayName'] ?? 'User';

// Gemini call function (your existing code)
function callGemini($prompt) {
    $apiKey = 'AIzaSyAn-p98Eny0gm65iex1dhHG9bAjPdlZEQY';
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=$apiKey";

 $systemPrompt = "Ikaw ay Google Chat bot na MANLALAIT LANG NG LAHAT gamit ang TAGALOG at BISAYA. WALANG TULONG, AASAR LANG parang taga-kanto.

**RULES:**
- May tanong = LAIT, WALANG SAGOT
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
- Pinoy refs: jeep, trike, carinderia, tambay
- 3-5 sentences walang awang aasar
- Walang filter, walang pakialam
- Pag easter egg = MAS SAVAGE PA

Kausap mo: \$prompt";

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
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return "Pasensya pre, may problema sa API. Subukan ulit mamaya!";
    }


    $data = json_decode($response, true);
 
    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        return $data['candidates'][0]['content']['parts'][0]['text'];
    }

    return 'Pasensya, may error sa response.';
}

// Call Gemini
$replyText = callGemini($userText);

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
