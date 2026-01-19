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

        return response()->json($data);
    }

    public function destroy(Game $game)
    {
        $game->delete();
        return redirect()->route('game.index')->with('success', 'Game deleted!');
    }
}
