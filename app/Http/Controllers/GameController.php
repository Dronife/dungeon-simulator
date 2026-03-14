<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateWorldImages;
use App\Models\Game;
use App\Models\Image;
use App\Clients\GeminiClient;
use App\Services\ImageGenerator;
use App\Services\WorldGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Inertia\Inertia;

class GameController extends Controller
{
    public function index()
    {
        $games = Game::latest()->get();
        return Inertia::render('Games/Index', [
            'games' => $games,
        ]);
    }

    public function show(Game $game)
    {
        $game->load(['characters', 'world', 'dmMemories.memory']);
        return view('game.show', compact('game'));
    }

    public function generateWorld()
    {
        $generator = new WorldGenerator();
        $data = $generator->generateWorld();

        $cacheKey = 'world_images_' . Str::random(16);
        Cache::put($cacheKey, ['status' => 'pending'], now()->addHours(1));

        GenerateWorldImages::dispatch($cacheKey, $data);

        return response()->json([
            'world' => $data['world'],
            'world_lore' => $data['world_lore'],
            'world_hooks' => $data['world_hooks'],
            'world_explanation' => $data['world_explanation'],
            'imagesCacheKey' => $cacheKey,
        ]);
    }

    public function worldImages(string $cacheKey)
    {
        return response()->json(Cache::get($cacheKey, ['status' => 'pending']));
    }

    public function generate(ImageGenerator $imageGenerator)
    {
        $generator = new WorldGenerator();
        $data = $generator->generate();
        // Generate character matrix image
        if (!empty($data['character'])) {
            $imagePath = $imageGenerator->generateCharacterMatrix($data['character']);
            if ($imagePath) {
                $data['character']['image_path'] = $imagePath;
            }
        }

//        // Generate lore images
        if (!empty($data['world_lore'])) {
            $boxExplanation = [];
            for ($i = 0; $i < 3; $i++) {
                $boxExplanation[] = 'box '.($i+1).' - '.($data['world_lore'][$i]['image_prompt'] ?? 'Keep empty');
            }

            $timeParts = explode(", ", $data['world']['time'] ?? []);
            $timeDescription = $timeParts[0] ?? '[Not specified]';
            $boxExplanation[3] = sprintf('box 4 - Portray town. Theme: %s, %s. %s', $timeParts[0] ?? '', $timeParts[1] ?? '', $data['world']['universe_rules']);

            $lorePath = $imageGenerator->generateLoreImage($boxExplanation, $timeDescription);
            if ($lorePath) {
                $data['world_lore_image_path'] = $lorePath;
            }
        }

        return response()->json($data);
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'character' => 'required|array',
                'world' => 'required|array',
                'world_explanation' => 'required|array',
                'world_hooks' => '|array',
                'world_lore' => '|array',
                'world_lore_image_path' => 'string',
            ]);


            $game = Game::create();

            $world = $game->world()->create([
                'time' => $data['world']['time'] ?? null,
                'universe_rules' => $data['world']['universe_rules'] ?? null,
                'environment_description' => $data['world']['environment_description'] ?? null,
            ]);

            // Create lore entries
            foreach ($request->input('world_lore', []) as $loreItem) {
                $world->lore()->create($loreItem);
            }

            // Create world hooks
            foreach ($request->input('world_hooks', []) as $index => $hookItem) {
                $world->hooks()->create([
                    'name' => $hookItem['name'],
                    'type' => $hookItem['type'],
                    'brief' => $hookItem['brief'],
                    'situation' => $hookItem['situation'] ?? null,
                    'stakes' => $hookItem['stakes'] ?? null,
                    'clue' => $hookItem['clue'] ?? null,
                    'image_prompt' => $hookItem['image_prompt'] ?? null,
                    'image_path' => $request->input('world_lore_image_path', []) ?? null,
                    'image_cell_index' => $index,
                ]);
            }

            $char = $data['character'];
            $game->characters()->create([
                'is_player' => true,
                'name' => $char['name'] ?? 'Unknown',
                'info' => $char['info'] ?? null,
                'personality' => $char['personality'] ?? null,
                'traits' => $char['traits'] ?? null,
                'trauma' => $char['trauma'] ?? null,
                'hobbies' => $char['hobbies'] ?? null,
                'routines' => $char['routines'] ?? null,
                'job' => $char['job'] ?? null,
                'skills' => $char['skills'] ?? null,
                'goals' => $char['goals'] ?? null,
                'secrets' => $char['secrets'] ?? null,
                'limits' => $char['limits'] ?? null,
                'intentions' => $char['intentions'] ?? null,
                'temperature' => $char['temperature'] ?? 0.7,
                'str' => $char['str'] ?? 10,
                'dex' => $char['dex'] ?? 10,
                'con' => $char['con'] ?? 10,
                'int' => $char['int'] ?? 10,
                'wis' => $char['wis'] ?? 10,
                'cha' => $char['cha'] ?? 10,
                'hp' => $char['hp'] ?? 20,
                'max_hp' => $char['max_hp'] ?? 20,
                'trauma_severity' => $char['trauma_severity'] ?? 1,
                'goal_severity' => $char['goal_severity'] ?? 1,
                'intention_severity' => $char['intention_severity'] ?? 1,
                'personality_severity' => $char['personality_severity'] ?? 1,
                'chaotic_temperature' => $char['chaotic-temperature'] ?? $char['chaotic_temperature'] ?? 0,
                'positive_temperature' => $char['positive-temperature'] ?? $char['positive_temperature'] ?? 0,
            ]);

            return response()->json([
                'success' => true,
                'game_id' => $game->id,
                'redirect' => route('game.play', $game),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function generateTrait(Request $request)
    {
        $request->validate([
            'field' => 'required|string|in:name,surname,personality,traits,trauma,hobbies,routines,job,skills,goals,secrets,limits,intentions',
            'existing_traits' => 'nullable|array',
        ]);

        $field = $request->input('field');
        $existingTraits = $request->input('existing_traits', []);

        $context = collect($existingTraits)
            ->filter()
            ->map(fn($v, $k) => ucfirst($k) . ': ' . $v)
            ->implode("\n");

        if (in_array($field, ['name', 'surname'])) {
            $race = $existingTraits['race'] ?? 'human';
            $gender = $existingTraits['gender'] ?? 'male';
            $prompt = "Generate a single fantasy RPG {$field} for a {$gender} {$race} character.";
            if ($context) {
                $prompt .= "\n\nExisting character details:\n{$context}";
            }
            $prompt .= "\n\nRespond with ONLY the {$field}, nothing else. One word only.";
        } else {
            $prompt = "Generate a short, creative {$field} for a fantasy RPG character.";
            if ($context) {
                $prompt .= "\n\nExisting character traits for context:\n{$context}";
            }
            $prompt .= "\n\nRespond with ONLY the trait value, no labels or explanation. Keep it to 1-2 sentences.";
        }

        $client = new GeminiClient();
        $response = $client->generate($prompt, 'You are a creative fantasy RPG character designer.', 0.9);

        return response()->json([
            'field' => $field,
            'value' => trim($response->text),
        ]);
    }

    public function destroy(Game $game)
    {
        $game->delete();
        return redirect()->route('game.index')->with('success', 'Game deleted!');
    }
}
