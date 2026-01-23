<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Services\WorldGenerator;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function index()
    {
        $games = Game::latest()->get();
        return view('game.index', compact('games'));
    }

    public function show(Game $game)
    {
        $game->load(['characters', 'world', 'dmMemories.memory']);
        return view('game.show', compact('game'));
    }

    public function generate()
    {
        $generator = new WorldGenerator();
        $data = $generator->generate();

        // Render the character sheet partial
        $html = view('game.partials.character-sheet', [
            'player' => $data['character'],
            'world' => $data['world'],
            'lore' => $data['world_lore'] ?? [],
        ])->render();

        return response()->json([
            'data' => $data,
            'html' => $html,
        ]);
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'character' => 'required|array',
                'world' => 'required|array',
            ]);

            $game = Game::create([
//                'name' => $data['character']['name'] ?? 'New Game',
//                'status' => 'init',
//                'current_tick' => 0,
//                'global_rules' => $data['world']['universe_rules'] ?? null,
            ]);

            $game->world()->create([
                'time' => $data['world']['time'] ?? null,
                'universe_rules' => $data['world']['universe_rules'] ?? null,
                'environment_description' => $data['world']['environment_description'] ?? null,
            ]);

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
                'redirect' => route('game.show', $game),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Game $game)
    {
        $game->delete();
        return redirect()->route('game.index')->with('success', 'Game deleted!');
    }
}
