@extends('layouts.app')

@section('title', $game->name)

@section('content')
    @php
        $player = $game->characters->where('is_player', true)->first();
        $playerData = $player ? $player->toArray() : null;
        $worldData = $game->world ? $game->world->toArray() : null;
        $loreData = $game->world?->lore ?? [];
    @endphp

    <div class="flex items-center justify-between mb-8">
        <div>
            <a href="{{ route('game.index') }}" class="text-white/60 hover:text-white text-sm mb-2 inline-block">
                <i class="fa-solid fa-arrow-left mr-1"></i>Back to Games
            </a>
            <h1 class="text-3xl font-bold">{{ $game->name }}</h1>
        </div>
        <form action="{{ route('game.destroy', $game) }}" method="POST" onsubmit="return confirm('Delete this game?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-4 py-2 bg-red-500/20 text-red-400 rounded-lg hover:bg-red-500/30 transition">
                <i class="fa-solid fa-trash mr-2"></i>Delete
            </button>
        </form>
    </div>

    @include('game.partials.character-sheet', [
        'player' => $playerData,
        'world' => $worldData,
        'lore' => $loreData,
    ])

    <!-- Game Console -->
    <div class="card p-6">
        <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
            <i class="fa-solid fa-scroll text-dnd-red"></i> Adventure
        </h2>
        <div id="console" class="bg-black/50 rounded-lg p-4 min-h-[200px] max-h-[400px] overflow-y-auto mb-4 font-mono text-sm text-white/80">
            <p class="text-white/40">The adventure begins...</p>
        </div>
        <div class="flex gap-2">
            <input type="text" id="player-input" placeholder="What do you do?" class="input-dark flex-1">
            <button id="send-btn" class="btn-primary">
                <i class="fa-solid fa-paper-plane mr-2"></i>Send
            </button>
        </div>
    </div>
@endsection
