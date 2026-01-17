<?php

declare(strict_types=1);

namespace App\Dto;

readonly class LlmResponse
{
    public function __construct(
        public string $text,
        public ?int $promptTokens = null,
        public ?int $completionTokens = null,
        public ?string $finishReason = null,
    ) {}
}
