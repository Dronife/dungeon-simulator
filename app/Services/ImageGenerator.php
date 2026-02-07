<?php

declare(strict_types=1);

namespace App\Services;

use App\Clients\GeminiClient;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageGenerator
{
    public function __construct(private readonly GeminiClient $client)
    {
    }

    /**
     * Generate character portrait matrix (4x4 grid with emotions)
     */
    public function generateCharacterMatrix(array $character): ?string
    {

        $characterDescription = $character['image_prompt'] ?? 'No data provided. [In each box write error in red letters]';

        $prompt = <<<PROMPT

            Character description: {$characterDescription}
            VERY IMPORTANT 1: the character matrix layout picture MUST be same as reference image. YOU ARE NOT ALLOWERD TO MOVE BOXES. IF FUCKING ZXC BOX IS IN RIGHT TOP YOU KEEP THAT BOX THERE AND JUST PAINT THE CHARACTER.
            VERY IMPORTANT 2: NO TEXT FROM REFERENCE PHOTO
            Generate character matrix in a style of Digital Painting . In the 'zxc' box it must be character concept art It can be with somekind of background. Boxes from 1-12 it is portrait(to the shoulders) of that same character slightly looking at right side. Boxes 1-12 are white background.
            1 box is neautral, 2 box character has cuts and sweating and bruses, 3 box character has bruses, 4 box is a little bit dirty, cuts, bruses visually extremly tired. 5 box sweating and tired, 6 box is smiling, 7 box is bad mood, 8 box is scared, 9 box is confident. the rest of the boxes keep empty for now.
            All emotions not extreme they are at 40% intensity
            IMPORTANT: Numbers from reference picture must not be written.
            PROMPT;

        $templatePath = public_path('images/character_matrix.png');

        if (!file_exists($templatePath)) {
            throw new \RuntimeException("Template not found: {$templatePath}");
        }

        $imageData = $this->client->generateImage($prompt, '1:1', $templatePath, GeminiClient::MODEL_IMAGE_PRO, 1.0);

        if (!$imageData) {
            return null;
        }

        return $this->saveImage($imageData, 'characters', random_bytes(12));
    }

    /**
     * Generate lore element image
     */
    public function generateLoreImage(array $loreItem, string $timeDescription): ?string
    {

        if($loreItem === []) {
            return null;
        }

        $loreItemsImploded = implode("\n", $loreItem);

        $imagePrompt = <<<PROMPT

        Generate concept painting/key art in a style of Digital Painting. for each individual boxes of reference image. Time is: {$timeDescription}

        {$loreItemsImploded}

        Numbers from reference picture must not be written.
        PROMPT;


        $templatePath = public_path('images/world_lore_matrix.png');

        if (!file_exists($templatePath)) {
            throw new \RuntimeException("Template not found: {$templatePath}");
        }

        $imageData = $this->client->generateImage($imagePrompt, '1:1', $templatePath, GeminiClient::MODEL_IMAGE_PRO, 1.0);;

        if (!$imageData) {
            return null;
        }

        return $this->saveImage($imageData, 'lore', random_bytes(16));
    }

    private function saveImage(string $base64Data, string $folder, string $name): string
    {
        $filename = Str::slug($name) . '_' . Str::random(8) . '.png';
        $path = "images/{$folder}/{$filename}";

        $imageContent = base64_decode($base64Data);
        Storage::disk('public')->put($path, $imageContent);

        return $path;
    }
}
