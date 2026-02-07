<?php

declare(strict_types=1);

namespace App\Clients;

use App\Dto\LlmResponse;
use Gemini\Data\Content;
use Gemini\Data\GenerationConfig;
use Gemini\Data\ImageConfig;
use Gemini\Data\ThinkingConfig;
use Gemini\Enums\ResponseModality;
use Gemini\Laravel\Facades\Gemini;

class GeminiClient
{
    public const MODEL_FLASH = 'gemini-3-flash-preview';
    public const MODEL_PRO = 'gemini-3-pro-preview';
    public const MODEL_IMAGE = 'gemini-2.5-flash-image';
    public const MODEL_IMAGE_PRO = 'models/gemini-3-pro-image-preview';

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
            new GenerationConfig(temperature: $temperature)
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
            new GenerationConfig(temperature: $temperature)
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

    public function generateImage(string $prompt, string $aspectRatio = '1:1', ?string $imagePath = null, ?string $model = self::MODEL_IMAGE, ?float $temperature = 0.15): ?string
    {
        $imageConfig = new ImageConfig(aspectRatio: $aspectRatio);
        $generationConfig = new GenerationConfig(
            maxOutputTokens: 32768,
            temperature: $temperature,
            topP: 0.95,
            topK: 64,
            responseModalities: [ResponseModality::TEXT, ResponseModality::IMAGE],
            enableEnhancedCivicAnswers: true,
            thinkingConfig: ThinkingConfig::from(['includeThoughts' => true]),
            imageConfig: $imageConfig,
        );

        $contents = [];

        // Add input image if provided
        if ($imagePath && file_exists($imagePath)) {
            $contents[] = new \Gemini\Data\Blob(
                mimeType: \Gemini\Enums\MimeType::IMAGE_PNG,
                data: base64_encode(file_get_contents($imagePath))
            );
        }
        $contents[] = $prompt;

        $response = Gemini::generativeModel(model: $model)
            ->withGenerationConfig($generationConfig)
            ->generateContent($contents);

        $parts = $response->parts();

        if (empty($parts)) {
            return null;
        }

        foreach ($parts as $part) {
            if (isset($part->inlineData) && $part->inlineData !== null) {
                return $part->inlineData->data;
            }
        }

        return null;
    }

    public function setModel(string $model): self
    {
        $this->model = $model;
        return $this;
    }
}
