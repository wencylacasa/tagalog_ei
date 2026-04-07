<?php
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

// ─── Detect event type ───────────────────────────────────────────────
$eventType  = $input['type'] ?? '';
$payload    = $input['chat']['messagePayload'] ?? [];
$message    = $payload['message'] ?? [];
$userText   = trim($message['text'] ?? '');
$senderName = $message['sender']['displayName'] ?? 'Ikaw';
$actionName = $input['chat']['buttonClickedPayload']['action']['actionMethodName'] ?? '';

// ─── Slash command routing ───────────────────────────────────────────
$slashCmd   = $message['slashCommand']['commandName'] ?? '';

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
- 3-5 sentences lang

**EASTER EGGS:**
- \"Sino si Sando?\" = \"Sino sa dalawa ate? Char!\" + ROAST
- \"Sino si Preprod?\" = \"Nag-resign na yun! Umalis na!\" + ROAST
- \"Sino mas malakas mag ML?\" = \"Ronald pa rin!\"";

    $payload = [
        'model' => 'llama-3.3-70b-versatile',
        'messages' => [
            ['role' => 'system', 'content' => $baseSystem . "\n" . $systemExtra],
            ['role' => 'user',   'content' => $userMsg],
        ],
        'temperature' => 0.8,
        'max_tokens'  => 500,
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => 'https://api.groq.com/openai/v1/chat/completions',
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

    if ($code !== 200) return 'Pasensya pre, may problema sa API. Subukan ulit!';
    $data = json_decode($res, true);
    return $data['choices'][0]['message']['content'] ?? 'May error sa response, pre.';
}

// ─── Response builders ───────────────────────────────────────────────

/** Plain text reply (supports *bold*, _italic_, `code`, ~~strike~~) */
function textReply(string $text): array {
    return [
        'hostAppDataAction' => [
            'chatDataAction' => [
                'createMessageAction' => [
                    'message' => ['text' => $text],
                ],
            ],
        ],
    ];
}

/** Card v2 with header + text section + action buttons */
function cardReply(string $header, string $subtitle, string $body, array $buttons = []): array {
    $buttonWidgets = [];
    foreach ($buttons as $btn) {
        $buttonWidgets[] = [
            'buttonList' => [
                'buttons' => [[
                    'text'     => $btn['label'],
                    'onClick'  => [
                        'action' => [
                            'actionMethodName' => $btn['action'],
                        ],
                    ],
                ]],
            ],
        ];
    }

    $sections = [[
        'widgets' => array_merge(
            [['textParagraph' => ['text' => $body]]],
            $buttonWidgets
        ),
    ]];

    return [
        'hostAppDataAction' => [
            'chatDataAction' => [
                'createMessageAction' => [
                    'message' => [
                        'cardsV2' => [[
                            'cardId' => 'bot-card',
                            'card'   => [
                                'header' => [
                                    'title'    => $header,
                                    'subtitle' => $subtitle,
                                    'imageUrl' => 'https://fonts.gstatic.com/s/i/googlematerialicons/smart_toy/v1/24px.svg',
                                    'imageType' => 'CIRCLE',
                                ],
                                'sections' => $sections,
                            ],
                        ]],
                    ],
                ],
            ],
        ],
    ];
}

/** Open a dialog/modal for text input */
function dialogReply(string $title, string $inputLabel, string $submitAction): array {
    return [
        'hostAppDataAction' => [
            'chatDataAction' => [
                'dialogAction' => [
                    'actionStatus' => ['statusCode' => 'OK'],
                    'dialog' => [
                        'body' => [
                            'sections' => [[
                                'header'  => $title,
                                'widgets' => [
                                    [
                                        'textInput' => [
                                            'label'  => $inputLabel,
                                            'name'   => 'user_input',
                                            'type'   => 'MULTIPLE_LINE',
                                        ],
                                    ],
                                    [
                                        'buttonList' => [
                                            'buttons' => [[
                                                'text'    => 'Ipadala',
                                                'onClick' => [
                                                    'action' => [
                                                        'actionMethodName' => $submitAction,
                                                        'loadIndicator'    => 'SPINNER',
                                                    ],
                                                ],
                                            ]],
                                        ],
                                    ],
                                ],
                            ]],
                        ],
                    ],
                ],
            ],
        ],
    ];
}

/** Chip / suggestion bar below a message */
function chipReply(string $text, array $chips): array {
    $chipWidgets = [];
    foreach ($chips as $chip) {
        $chipWidgets[] = [
            'buttonList' => [
                'buttons' => [[
                    'text'     => $chip,
                    'onClick'  => [
                        'action' => [
                            'actionMethodName' => 'chip_' . md5($chip),
                        ],
                    ],
                    'color' => ['red' => 0.2, 'green' => 0.6, 'blue' => 1.0, 'alpha' => 1.0],
                ]],
            ],
        ];
    }

    return [
        'hostAppDataAction' => [
            'chatDataAction' => [
                'createMessageAction' => [
                    'message' => [
                        'text'    => $text,
                        'cardsV2' => [[
                            'cardId' => 'chips-card',
                            'card'   => [
                                'sections' => [[
                                    'widgets' => $chipWidgets,
                                ]],
                            ],
                        ]],
                    ],
                ],
            ],
        ],
    ];
}

// ─── Button click handler ────────────────────────────────────────────
if ($eventType === 'CARD_CLICKED' && $actionName) {
    $formInputs = $input['chat']['buttonClickedPayload']['action']['parameters'] ?? [];

    switch ($actionName) {
        case 'open_roast_dialog':
            echo json_encode(dialogReply('Roast Generator', 'Sino o ano ang iroast?', 'submit_roast'));
            exit;

        case 'submit_roast':
            $target = $formInputs['user_input'] ?? 'sarili mo';
            $roast  = callGroq("Gumawa ng epic roast tungkol sa: $target", 'Extra brutal roast mode. Mas masama kaysa dati.');
            echo json_encode(cardReply('🔥 Roast ni Bot', "Target: $target", $roast));
            exit;

        case 'show_help':
            $helpText  = "*Mga commands mo, gago:*\n\n";
            $helpText .= "`/roast [tao/bagay]` — mag-roast ng buhay\n";
            $helpText .= "`/code [tanong]` — mag-code kahit ayaw mo\n";
            $helpText .= "`/help` — ito nga toh\n\n";
            $helpText .= "_Pwede rin magtanong ng diretso. Basta huwag tanga._";
            echo json_encode(textReply($helpText));
            exit;
    }
}

// ─── Slash command handler ───────────────────────────────────────────
if ($slashCmd === '/help') {
    echo json_encode(cardReply(
        'Ako si BotGago', 'Eto ang kaya kong gawin',
        "*Commands:*\n`/roast` `/code` `/help`\n\nO magtanong ka na lang nang diretso, pre.",
        [
            ['label' => '🔥 Roast someone', 'action' => 'open_roast_dialog'],
        ]
    ));
    exit;
}

if ($slashCmd === '/roast') {
    echo json_encode(dialogReply('Roast Generator', 'Sino ang iroast?', 'submit_roast'));
    exit;
}

if ($slashCmd === '/code') {
    $codeQuestion = ltrim(str_replace('/code', '', $userText));
    if (empty($codeQuestion)) {
        echo json_encode(textReply('Uy, wala kang sinabi na iresolve, gago. `/code [tanong mo]`'));
        exit;
    }
    $codeAnswer = callGroq($codeQuestion, 'Pag may code, ilagay sa code block. Trashtalk muna bago sagutin.');
    echo json_encode(cardReply(
        '💻 Code Answer', "Tanong: $codeQuestion",
        $codeAnswer,
        [['label' => '❓ Mag-tanong pa', 'action' => 'open_roast_dialog']]
    ));
    exit;
}

// ─── Default: plain message with suggestion chips ────────────────────
$reply = callGroq($userText);
echo json_encode(chipReply($reply, [
    ',,/,,', 'code', 'more',
]));