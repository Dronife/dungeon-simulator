<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Game;
use App\Models\Image;
use App\Services\ImageGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateGameImages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        public Game $game,
    ) {}

    public function handle(ImageGenerator $imageGenerator): void
    {
        Log::info("Starting image generation for game {$this->game->id}");

        // Generate character matrix
        $character = $this->game->characters()->where('is_player', true)->first();
        if ($character) {
            try {
                $path = $imageGenerator->generateCharacterMatrix($character->toArray());
                if ($path) {
                    Image::create([
                        'image_path' => $path,
                        'model' => 'App\Models\Character',
                        'model_id' => $character->id,
                    ]);
                    Log::info("Generated character matrix for {$character->name}");
                }
            } catch (\Exception $e) {
                Log::error("Failed to generate character matrix: " . $e->getMessage());
            }
        }

        // Generate lore images
        $world = $this->game->world;
        if ($world) {
            $loreItems = $world->lore()->get();
            foreach ($loreItems as $index => $lore) {
                if (empty($lore->image_prompt)) {
                    continue;
                }

                try {
                    $path = $imageGenerator->generateLoreImage($lore->toArray());
                    if ($path) {
                        Image::create([
                            'image_path' => $path,
                            'model' => 'App\Models\Lore',
                            'model_id' => $lore->id,
                        ]);
                        Log::info("Generated lore image for: {$lore->name}");
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to generate lore image: " . $e->getMessage());
                }

                // Rate limit
                if ($index < $loreItems->count() - 1) {
                    sleep(2);
                }
            }
        }

        Log::info("Completed image generation for game {$this->game->id}");
    }
}
