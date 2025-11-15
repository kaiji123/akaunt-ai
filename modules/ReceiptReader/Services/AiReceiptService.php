<?php

namespace Modules\ReceiptReader\Services;

use OpenAI;

class AiReceiptService
{
    public function extractDataFromContents(string $fileContents, string $mimeType = 'image/jpeg'): array
    {
        $client = OpenAI::client(env('OPENAI_API_KEY'));

        $imageData = base64_encode($fileContents);

        // SYSTEM PROMPT FOR STRICT JSON
        $systemPrompt = <<<EOT
You are an accounting receipt parser.

Return ONLY valid JSON, following exactly this structure:

{
  "vendor_name": string | null,
  "vendor_address": string | null,
  "date": string | null,
  "subtotal": string | null,
  "tax": string | null,
  "total": string | null,
  "currency": string | null,
  "expense_category": string | null,
  "line_items": [
    {
      "description": string,
      "qty": number | null,
      "unit_price": string | null
    }
  ]
}

Rules:
- If a field is missing, set it to null.
- DO NOT include comments or explanation.
- DO NOT include markdown.
- DO NOT wrap the JSON in text. Output ONLY JSON.
EOT;

        $response = $client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => "Extract structured receipt data from this image."],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => "data:$mimeType;base64,$imageData"
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $raw = $response->choices[0]->message->content ?? '';

        // 1️⃣ TRY DIRECT JSON FIRST
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return [
                'status' => 'success',
                'ai_data' => $decoded
            ];
        }

        // 2️⃣ TRY TO EXTRACT JSON BLOCK USING REGEX
        if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $raw, $matches)) {
            $possibleJson = $matches[0];
            $decoded = json_decode($possibleJson, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return [
                    'status' => 'success',
                    'ai_data' => $decoded,
                    'warning' => 'JSON recovered from messy output.'
                ];
            }
        }

        // 3️⃣ STILL FAILED — RETURN RAW OUTPUT FOR DEBUGGING
        return [
            'status' => 'error',
            'message' => 'Could not parse AI JSON output.',
            'raw' => $raw,
        ];
    }

    public function extractData(string $imagePath): array
    {
        $client = OpenAI::client(env('OPENAI_API_KEY'));

        // Encode image base64
        $imageData = base64_encode(file_get_contents($imagePath));

        $response = $client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => "Extract the following accounting fields from this receipt:
                                vendor_name,
                                vendor_address,
                                date,
                                subtotal,
                                tax,
                                total,
                                currency,
                                expense_category,
                                line_items (description, qty, unit_price)"
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => "data:image/jpeg;base64,$imageData"
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        return json_decode($response->choices[0]->message->content, true);
    }
}
