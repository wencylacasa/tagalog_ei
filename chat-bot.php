<?php
header('Content-Type: application/json');

// Read the incoming Google Chat message
$input = json_decode(file_get_contents('php://input'), true);
$userText = $input['message']['text'] ?? 'bumibira kaba ng saba';
$senderName = $input['message']['sender']['displayName'] ?? 'User';

// Function to call Gemini 2.5 Flash with SWATTER MODE
function callGemini($prompt) {
    $apiKey = 'AIzaSyCNps-O9_4WRQ3OPFP9L_zgiW8urna221M'; // <-- your key
    
    // ⬇️ USING: Gemini 2.5 Flash (newest model)
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=$apiKey";

    // BISAYA-TAGALOG SWATTER MODE
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

**SWATTER STYLE:**
- Parang trashtalking sa basketball court
- Parang away sa kanto pero verbal lang
- Harsh pero may halong tawa
- Gamitin Pinoy references: jeep, trike, carinderia, sari-sari store, tambay, basketball, inuman
- 3-5 sentences ng walang awang pang-aasar
- Walang pa-cute, walang pa-formal
- Straight to the point na insulto

**TONE:** Parang kausap mo yung tropa na walang modo, walang filter, walang pakialam sa feelings. Pure kanto vibes, swatter energy.

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
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo $response;
    // Better error handling
    if ($httpCode !== 200) {
        error_log("Gemini API Error: $response");
        return "Pasensya pre, may problema sa API. Quota limit ata o may error. Subukan ulit mamaya!";
    }

    $data = json_decode($response, true);
    
    // Check if data exists properly
    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        return $data['candidates'][0]['content']['parts'][0]['text'];
    }
    
    return 'Pasensya, may error sa response.';
}

// Call Gemini with the user's message
$replyText = callGemini($userText);

// Send the response back to Google Chat
echo json_encode([
    "text" => $replyText
]);