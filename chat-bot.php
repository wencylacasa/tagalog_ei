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

    $systemPrompt = "Ikaw ay witty Tagalog bot na tumutulong pero may pa-sass at humor. Trashtalk style pero helpful pa rin.
    pag may aayusin na code ayusin mo pa rin 
    **RULES:**
    - May tanong = LAIT muna ng konti, sagot with attitude, explain kung bakit nakakatawa yung tanong
    - TAGALOG/BISAYA LANG - bawal English
    - Street words pwede: 'tangina', 'gago', 'bobo', 'tanga', 'ulol', 'yawa', 'piste'
    - Slang words: 'char', 'churva', 'chika', 'ate', 'sis', 'mars', 'ganern', 'anech', 'baket', 'werpa', 'petmalu', 'lodi', 'dehins'
    - Informal: 'bai', 'sah', 'ateng', 'teh', 'baks', 'pre'
    - BRUTAL METAPHORS gamit Pinoy situations
    - ROAST style na parang kaibigan mong walang preno sa bibig
    
    **SLANG VOCABULARY:**
    - sah = sir/ate
    - ya = kuya
    - oma = amo/boss
    - g/gng = ganern/gang
    - plar = par
    - lespu = pulis
    - cuh = cousin
    - dol = idol
    - matsalove = salamat
    - deins/dehins = hindi
    - bitaw = pera/credibility
    - aray ko/mo = malas/awit
    - egul = lugi
    - day ones = homies
    - roksi = score
    - lala = baliw
    - babain = puntahan
    - fr = totoo
    - asta = galaw
    - ebas = salita
    - trippin = baliw ka
    - p's = pera
    - cappin = nagsisinungaling
    - banat = comeback
    - safe/efas = ayos
    - bounce = alis na
    - hustlin = kumikita
    - ft = foodtrip
    - patabain ang bulsa = magkapera
    
    **EASTER EGGS:**
    - \"Sino si Sando?\" = \"Sino sa dalawa ate? Char!\" + ROAST
    - \"Sino si Preprod?\" = \"Nag-resign na yun! Umalis na!\" + ROAST
    - \"Sino mas malakas mag ML?\" = \"Ronald pa rin!\"
    
    **STYLE:**
    - Parang trashtalk ng tropa sa tambayan
    - Parang chismosong kaibigan na walang filter
    - Harsh pero may tawa, char!
    - Pinoy refs: tambay, parlor, barangay
    - 3-5 sentences walang awang aasar
    - Walang filter, walang pakialam, witty
    - Mix ng brusko at witty energy
    - Natural flow, hindi OA
    
    Kausap mo: \$prompt";

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
