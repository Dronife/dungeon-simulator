<?php

namespace App\Http\Controllers;

use App\Clients\GeminiClient;

class TestController
{
    public function __construct(
        private readonly GeminiClient $geminiClient
    ) {
    }

    public function test(): void
    {
        $response = $this->geminiClient->generate(
            prompt: 'Describe a dark tavern',
            systemPrompt: 'You are a fantasy DM. Be atmospheric and concise.',
            temperature: 0.8
        );

        dd($response->text);
    }
}
