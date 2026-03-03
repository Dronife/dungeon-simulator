<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\ImageGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GenerateWorldImages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        public string $cacheKey,
        public array $worldData,
    ) {}

    public function handle(ImageGenerator $imageGenerator): void
    {
        Log::info("Starting world image generation for cache key {$this->cacheKey}");
        try {
            $boxExplanation = [];
            for ($i = 0; $i < 3; $i++) {
                $boxExplanation[] = 'box ' . ($i + 1) . ' - ' . ($this->worldData['world_lore'][$i]['image_prompt'] ?? 'Keep empty');
            }

            $timeParts = explode(', ', $this->worldData['world']['time'] ?? '');
            $timeDescription = $timeParts[0] ?: '[Not specified]';
            $boxExplanation[3] = sprintf(
                'box 4 - Portray town. Theme: %s, %s. %s',
                $timeParts[0] ?? '',
                $timeParts[1] ?? '',
                $this->worldData['world']['universe_rules'] ?? ''
            );

            $lorePath = $imageGenerator->generateLoreImage($boxExplanation, $timeDescription);

            if ($lorePath) {
                Cache::put($this->cacheKey, ['status' => 'done', 'world_lore_image_path' => $lorePath], now()->addHours(1));
                Log::info("World image generation completed for {$this->cacheKey}");
            } else {
                Cache::put($this->cacheKey, ['status' => 'failed'], now()->addHours(1));
                Log::warning("World image generation returned null for {$this->cacheKey}");
            }
        } catch (\Exception $e) {
            Cache::put($this->cacheKey, ['status' => 'failed'], now()->addHours(1));
            Log::error("World image generation failed for {$this->cacheKey}: " . $e->getMessage());
        }
    }
}
