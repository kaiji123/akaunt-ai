<?php

namespace Modules\AiReceiptReader\Services;

use OpenAI;

class AiReceiptService
{
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
