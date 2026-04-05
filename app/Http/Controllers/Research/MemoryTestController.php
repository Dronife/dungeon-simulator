<?php

namespace App\Http\Controllers\Research;

use App\Clients\GeminiClient;
use App\Enum\MemoryType;
use App\Models\Character;
use App\Models\Game;
use App\Models\Memory;
use App\Services\CharacterPromptMapper;
use App\Services\DmPromptMapper;

class MemoryTestController
{
    public function __construct(
        private readonly GeminiClient $geminiClient,
        private readonly CharacterPromptMapper $characterMapper,
        private readonly DmPromptMapper $dmPromptMapper,
    ) {
    }

    public function testMemory()
    {
        $npc1 = Character::find(4); // Alfrom
        $npc2 = Character::find(5); // Dessra


        $npc1Sheet = $this->characterMapper->constructSheet($npc1, $npc2);
        $npc1Intent = $this->npcIntent($npc1Sheet);
//        dd($npc1Intent);
        $intent1WithContext = $this->characterMapper->buildIntentWithContext($npc1, $npc1Intent);

        $npc2Sheet = $this->characterMapper->constructSheet($npc2);
        $npc2Intent = $this->npcIntent($npc2Sheet);
        $intent2WithContext = $this->characterMapper->buildIntentWithContext($npc2, $npc2Intent);
        $dmPrompt = $this->dmPromptMapper->composeDmPrompt([$intent1WithContext, $intent2WithContext]);

        dd($this->dmNarration($dmPrompt), $dmPrompt,  $intent1WithContext, $intent2WithContext);

    }

    private function dmNarration(string $characterSheet): array
    {
        $payload = json_encode([
            'context' => $characterSheet,
            'rule' => 'return as json. Do not write json markdown (```json) No questions, no additional content. There is provided return_format how you need to return your response. ',
            'return_format' => [
                'answer' => 'your-answer-here',
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $this->geminiClient->setModel(GeminiClient::MODEL_25_FLASH);

        $text = $this->geminiClient->generate(
            prompt: $payload,
            temperature: 0
        );

        $raw = $text->text;

        $array = $this->parseJsonResponse($raw);
        return $array;
    }

    private function npcIntent(string $characterSheet): array
    {
        $payload = json_encode([
            'context' => $characterSheet,
            'rule' => 'return as json. Do not write json markdown (```json) No questions, no additional content. There is provided return_format how you need to return your response. ',
            'return_format' => [
                'answer' => 'your-answer-here',
                'check' => '<dnd-roll-check>',
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $this->geminiClient->setModel(GeminiClient::MODEL_25_FLASH_LITE);

        $text = $this->geminiClient->generate(
            prompt: $payload,
            temperature: 0
        );

        $raw = $text->text;

        $array = $this->parseJsonResponse($raw);
        return $array ?? [];
    }

    private function parseJsonResponse(string $raw): array
    {
        $cleaned = trim($raw);

        // Strip opening ```json or ```
        if (str_starts_with($cleaned, '```')) {
            $cleaned = preg_replace('/^```[a-z]*\n?/i', '', $cleaned);
        }

        // Strip closing ```
        if (str_ends_with($cleaned, '```')) {
            $cleaned = substr($cleaned, 0, -3);
        }

        $cleaned = trim($cleaned);
        $data = json_decode($cleaned, true);

        // Try removing trailing duplicate brace
        if (json_last_error() !== JSON_ERROR_NONE) {
            $cleaned = preg_replace('/\}\s*\}\s*$/', '}', $cleaned);
            $cleaned = preg_replace('/\]\s*\]\s*$/', ']', $cleaned);
            $data = json_decode($cleaned, true);
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('JSON parse failed: ' . json_last_error_msg());
        }

        return $data;
    }
}
