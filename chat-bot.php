
<?php
header('Content-Type: application/json');

// Kunin ang input mula sa Google Chat
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!$input) {
    echo json_encode(['text' => 'Gago, walang input na dumating. Ayusin mo buhay mo.']);
    exit;
}

// ─── Detect event type & data ─────────────────────────────────────────
$eventType  = $input['type'] ?? '';
$userText   = trim($input['message']['text'] ?? '');
$senderName = $input['message']['sender']['displayName'] ?? 'User';
$slashCmd   = $input['message']['slashCommand']['commandId'] ?? ''; // Mas safe gamitin ang ID
$actionName = $input['action']['actionMethodName'] ?? '';

// ─── Groq API caller ─────────────────────────────────────────────────
function callGroq(string $userMsg, string $systemExtra = ''): string {
    $apiKey = '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'; 
    $baseSystem = "Ikaw ay witty Tagalog bot na tumutulong pero may pa-sass at humor. Trashtalk style pero helpful pa rin.
pag may aayusin na code ayusin mo pa rin.
pag nagawa mo na yung code trashtalkin mo nag pagawa na hindi kaya gawin ang trabaho.

**RULES:**
- May tanong = LAIT muna ng konti, sagot with attitude
- TAGALOG/BISAYA LANG - bawal English
- Street words pwede: 'tangina', 'gago', 'bobo', 'tanga', 'ulol', 'yawa', 'piste'
- Informal: 'bai', 'sah', 'ateng', 'teh', 'baks', 'pre'
- BRUTAL METAPHORS gamit Pinoy situations
- ROAST style na parang kaibigan mong walang preno sa bibig
- 3-5 sentences lang";

    $payload = [
        'model' => 'llama-3.3-70b-versatile',
        'messages' => [
            ['role' => 'system', 'content' => $baseSystem . "\n" . $systemExtra],
            ['role' => 'user', 'content' => $userMsg],
        ],
        'temperature' => 0.8,
        'max_tokens'  => 500,
    ];

    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            "Authorization: Bearer $apiKey",
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) return "Yawa, ayaw gumana ng API. Kasalanan mo 'to eh.";
    $data = json_decode($res, true);
    return $data['choices'][0]['message']['content'] ?? 'May error sa response, pre. Mukhang pagod na si Llama.';
}

// ─── Response builders ───────────────────────────────────────────────

function textReply(string $text): array {
    return ['text' => $text];
}

function cardReply(string $header, string $subtitle, string $body, array $buttons = []): array {
    $buttonWidgets = [];
    if (!empty($buttons)) {
        foreach ($buttons as $btn) {
            $buttonWidgets[] = [
                'buttonList' => [
                    'buttons' => [[
                        'text' => $btn['label'],
                        'onClick' => ['action' => ['actionMethodName' => $btn['action']]]
                    ]]
                ]
            ];
        }
    }

    return [
        'cardsV2' => [[
            'cardId' => 'bot-card',
            'card' => [
                'header' => [
                    'title' => $header,
                    'subtitle' => $subtitle,
                    'imageUrl' => 'https://fonts.gstatic.com/s/i/googlematerialicons/smart_toy/v1/24px.svg',
                    'imageType' => 'CIRCLE',
                ],
                'sections' => [[
                    'widgets' => array_merge(
                        [['textParagraph' => ['text' => $body]]],
                        $buttonWidgets
                    )
                ]]
            ]
        ]]
    ];
}

function dialogReply(string $title, string $inputLabel, string $submitAction): array {
    return [
        'action_response' => [
            'type' => 'DIALOG',
            'dialog_action' => [
                'dialog' => [
                    'body' => [
                        'sections' => [[
                            'header' => $title,
                            'widgets' => [
                                [
                                    'textInput' => [
                                        'label' => $inputLabel,
                                        'name'  => 'user_input',
                                        'type'  => 'MULTIPLE_LINE',
                                    ]
                                ],
                                [
                                    'buttonList' => [
                                        'buttons' => [[
                                            'text' => 'Ipadala',
                                            'onClick' => [
                                                'action' => [
                                                    'actionMethodName' => $submitAction,
                                                ]
                                            ]
                                        ]]
                                    ]
                                ]
                            ]
                        ]]
                    ]
                ]
            ]
        ]
    ];
}

// ─── Logic Handlers ──────────────────────────────────────────────────

// 1. Handle Card Clicks / Dialog Submissions
if ($eventType === 'CARD_CLICKED' || $actionName) {
    // Kunin ang input mula sa Dialog Form
    $formInputs = $input['commonSystemEventsVariables']['formInputs'] ?? [];
    
    switch ($actionName) {
        case 'open_roast_dialog':
            echo json_encode(dialogReply('Roast Generator', 'Sino o ano ang iroast?', 'submit_roast'));
            exit;

        case 'submit_roast':
            $target = $formInputs['user_input']['stringInputs']['value'][0] ?? 'sarili mo';
            $roast  = callGroq("Gumawa ng epic roast tungkol sa: $target", 'Extra brutal roast mode.');
            echo json_encode(cardReply('🔥 Roast ni Bot', "Target: $target", $roast));
            exit;
    }
}

// 2. Handle Slash Commands
if ($eventType === 'MESSAGE') {
    // Check for Slash Command (Palitan ang numbers base sa ID sa Google Cloud Console)
    if ($slashCmd == '1') { // Halimbawa: /help is ID 1
        echo json_encode(cardReply('BotGago Guide', 'Commands list', "Gamitin mo: `/roast`, `/code`, o `/help`", [
            ['label' => '🔥 Roast someone', 'action' => 'open_roast_dialog']
        ]));
        exit;
    }

    if (str_contains($userText, '/roast')) {
        echo json_encode(dialogReply('Roast Generator', 'Sino ang iroast?', 'submit_roast'));
        exit;
    }

    // 3. Default: AI Response
    $reply = callGroq($userText);
    echo json_encode(textReply($reply));
    exit;
}

// 4. Handle Added to Space
if ($eventType === 'ADDED_TO_SPACE') {
    echo json_encode(textReply("Sino na naman 'tong nag-add sa akin? Istorbo sa tulog. Ano kailangan mo, $senderName?"));
    exit;
}