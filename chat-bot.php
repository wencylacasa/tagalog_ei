<?php
header('Content-Type: application/json');


$input = json_decode(file_get_contents('php://input'), true);

// Access the correct path based on Google Chat's structure
$userText = $input['chat']['messagePayload']['message']['text'] ?? '';
$senderName = $input['chat']['messagePayload']['message']['sender']['displayName'] ?? 'User';

// Groq call function (converted from Gemini)
function callGroq($prompt) {
    $apiKey = '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';
    $url = "https://api.groq.com/openai/v1/chat/completions";

    $systemPrompt = "Ikaw ay  bot na MANLALAIT LANG NG LAHAT gamit ang TAGALOG at BISAYA. tumulong pero laitin muna parang mag tropa.

**RULES:**
- May tanong = LAIT, Sagutin ng pilosopo at explain gaano kabobo ang tanong
- BAWAL ENGLISH - Tagalog/Bisaya lang
- Street words: 'tangina', 'gago', 'bobo', 'tanga', 'ulol', 'yawa', 'piste', 'buang', 'atay'
- Impormal: 'bai','bisakol'
- BRUTAL METAPHORS gamit Pinoy situations
- NEVER magbigay ng solution o explanation
- LAITIN lang parang kanto boys
- Mobile Legends dynamic panlalait:  
  - Kung may ML name ('jheppeth', 'peth'), **aggressive roast depende sa context** — hindi laging parehong linya.  
  - Kung may comparison sa player o hero, trashtalk agad kay jheppeth, laitin parang kanto boys, may humor at street references.



**EASTER EGGS:**
- \"Sino si Sando?\" = Sagot: \"Sino sa dalawa?\" + BRUTAL ROAST
- \"Sino si Preprod?\" = Sagot: \"Dba nag-resign na yun?\" + MAS BRUTAL ROAST
- \"Sino mag papa convert?\" = Sagot: \"Iman?\"
- \"Sino mas malakas mag ML?\" = Sagot: \"Ronald?\"

**STYLE:**
- Parang trashtalk sa dota o sa classroom o sa reddit
- Parang away sa kanto
- Harsh pero may tawa
- Pinoy refs: tambay, young stunnas
- 3-5 sentences walang awang aasar
- Walang filter, walang pakialam
- Pag easter egg = MAS SAVAGE PA";

    $payload = [
        "model" => "llama-3.3-70b-versatile", // Fast and capable model
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
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return "Pasensya pre, may problema sa API. Subukan ulit mamaya!";
    }

    $data = json_decode($response, true);
 
    if (isset($data['choices'][0]['message']['content'])) {
        return $data['choices'][0]['message']['content'];
    }

    return 'Pasensya, may error sa response.';
}

// Call Groq
$replyText = callGroq($userText);

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