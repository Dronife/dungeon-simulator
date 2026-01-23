<?php

declare(strict_types=1);

namespace App\Clients;

use App\Dto\LlmResponse;
use Gemini\Data\Content;
use Gemini\Laravel\Facades\Gemini;

class GeminiClient
{
    public const MODEL_FLASH = 'gemini-3-flash-preview';
    public const MODEL_PRO = 'gemini-3-pro-preview';

    private string $model = self::MODEL_FLASH;

    public function __construct(?string $model = null)
    {
        if ($model) {
            $this->model = $model;
        }
    }

    public function generate(string $prompt, ?string $systemPrompt = null, float $temperature = 0.7): LlmResponse
    {
        $generativeModel = Gemini::generativeModel(model: $this->model);

        if ($systemPrompt) {
            $generativeModel = $generativeModel->withSystemInstruction(
                Content::parse($systemPrompt)
            );
        }

        $generativeModel = $generativeModel->withGenerationConfig(
            new \Gemini\Data\GenerationConfig(temperature: $temperature)
        );

        $result = $generativeModel->generateContent($prompt);

        return new LlmResponse(
            text: $result->text(),
            promptTokens: $result->usageMetadata?->promptTokenCount,
            completionTokens: $result->usageMetadata?->candidatesTokenCount,
            finishReason: $result->candidates[0]?->finishReason?->value,
        );
    }

    public function chat(array $history, string $message, ?string $systemPrompt = null, float $temperature = 0.7): LlmResponse
    {
        $generativeModel = Gemini::generativeModel(model: $this->model);

        if ($systemPrompt) {
            $generativeModel = $generativeModel->withSystemInstruction(
                Content::parse($systemPrompt)
            );
        }

        $generativeModel = $generativeModel->withGenerationConfig(
            new \Gemini\Data\GenerationConfig(temperature: $temperature)
        );

        // Convert history to Content objects
        $formattedHistory = array_map(function ($item) {
            return Content::parse(
                part: $item['content'],
                role: $item['role'] === 'user' ? \Gemini\Enums\Role::USER : \Gemini\Enums\Role::MODEL
            );
        }, $history);

        $chat = $generativeModel->startChat(history: $formattedHistory);
        $result = $chat->sendMessage($message);

        return new LlmResponse(
            text: $result->text(),
            promptTokens: $result->usageMetadata?->promptTokenCount,
            completionTokens: $result->usageMetadata?->candidatesTokenCount,
            finishReason: $result->candidates[0]?->finishReason?->value,
        );
    }

    public function setModel(string $model): self
    {
        $this->model = $model;
        return $this;
    }
}
