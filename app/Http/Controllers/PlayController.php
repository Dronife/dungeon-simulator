<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Services\NarrationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PlayController extends Controller
{
    public function __construct(
        private readonly NarrationService $narration,
    ) {}

    public function show(Game $game)
    {
        $game->load(['world', 'characters', 'gameChats']);

        return Inertia::render('Games/Play', [
            'game' => $game,
        ]);
    }

    public function message(Request $request, Game $game)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $content = $this->narration->respond($game, $request->input('message'));

        return response()->json(['content' => $content]);
    }

    public function init(Game $game)
    {
        if ($game->gameChats()->exists()) {
            return response()->json([
                'content' => $game->gameChats()->where('type', 'llm')->first()->content,
            ]);
        }

        $content = $this->narration->initOpening($game);

        return response()->json(['content' => $content]);
    }
}
