@extends('layouts.app')

@section('title', 'Game #' . $game->id)

@section('content')
    <div class="flex items-center justify-between mb-8">
        <div>
            <a href="{{ route('game.index') }}" class="text-white/60 hover:text-white text-sm mb-2 inline-block">&larr; Back to Games</a>
            <h1 class="text-3xl font-bold">Game #{{ $game->id }}</h1>
        </div>
        <form action="{{ route('game.destroy', $game) }}" method="POST" onsubmit="return confirm('Delete this game?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-4 py-2 bg-red-500/20 text-red-400 rounded-lg hover:bg-red-500/30 transition">
                Delete Game
            </button>
        </form>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <!-- World Panel -->
        <div class="card p-6">
            <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
                <span>🌍</span> World
            </h2>
            @if($game->world)
                <div class="space-y-4 text-sm">
                    <div>
                        <span class="text-white/40 block mb-1">Time</span>
                        <span class="text-white">{{ $game->world->time ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-white/40 block mb-1">Universe Rules</span>
                        <span class="text-white">{{ $game->world->universe_rules ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-white/40 block mb-1">Environment</span>
                        <span class="text-white">{{ $game->world->environment_description ?? '-' }}</span>
                    </div>
                </div>
            @else
                <p class="text-white/60 text-sm">No world data.</p>
            @endif
        </div>

        <!-- Player Character Panel -->
        <div class="card p-6">
            <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
                <span>👤</span> Player Character
            </h2>
            @php
                $player = $game->characters->where('is_player', true)->first();
            @endphp
            @if($player)
                <div class="space-y-3 text-sm">
                    <div class="text-lg font-semibold text-dnd-red">{{ $player->name }}</div>
                    <div>
                        <span class="text-white/40">Info:</span>
                        <span class="text-white">{{ $player->info ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-white/40">Job:</span>
                        <span class="text-white">{{ $player->job ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-white/40">Personality:</span>
                        <span class="text-white">{{ $player->personality ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-white/40">Goals:</span>
                        <span class="text-white">{{ $player->goals ?? '-' }}</span>
                    </div>
                </div>
            @else
                <p class="text-white/60 text-sm">No player character.</p>
            @endif
        </div>
    </div>

    <!-- Game Console -->
    <div class="card p-6 mt-6">
        <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
            <span>⚔️</span> Adventure Console
        </h2>
        <div class="bg-black/50 rounded-lg p-4 min-h-[200px] mb-4 font-mono text-sm text-white/80">
            <p class="text-white/40">// Game session ready. Start your adventure...</p>
        </div>
        <div class="flex gap-2">
            <input type="text" placeholder="What do you do?" class="input-dark flex-1">
            <button class="btn-primary">Send</button>
        </div>
    </div>
@endsection
