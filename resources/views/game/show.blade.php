@extends('layouts.app')

@section('title', $game->name)

@section('content')
    @php
        $player = $game->characters->where('is_player', true)->first();
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

    @if($player)
        <!-- Character Sheet -->
        <div class="grid gap-6 lg:grid-cols-3 mb-6">

            <!-- Column 1: Identity + Stats -->
            <div class="space-y-4">
                <div class="bg-black/30 rounded-lg p-4">
                    <h3 class="text-2xl font-bold text-dnd-red mb-1">{{ $player->name }}</h3>
                    <p class="text-white/50 text-sm mb-3">{{ $player->job ?? '-' }}</p>
                    <p class="text-white/80 text-sm leading-relaxed">{{ $player->info ?? '-' }}</p>
                </div>

                <!-- Stats Grid -->
                <div class="bg-black/30 rounded-lg p-4">
                    <h4 class="text-xs font-semibold text-white/40 uppercase tracking-wide mb-3">
                        <i class="fa-solid fa-chart-simple mr-2"></i>Attributes
                    </h4>
                    <div class="grid grid-cols-3 gap-2 text-center">
                        @foreach(['str', 'dex', 'con', 'int', 'wis', 'cha'] as $stat)
                            <div class="bg-black/40 rounded p-2">
                                <div class="text-xs text-white/40">{{ strtoupper($stat) }}</div>
                                <div class="text-xl font-bold">{{ $player->$stat }}</div>
                                <div class="text-xs text-dnd-red">
                                    @php $mod = floor(($player->$stat - 10) / 2); @endphp
                                    {{ $mod >= 0 ? '+' . $mod : $mod }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- HP & Temperament -->
                <div class="bg-black/30 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs text-white/40 uppercase tracking-wide">
                            <i class="fa-solid fa-heart mr-1 text-dnd-red"></i>Hit Points
                        </span>
                        <span class="text-xl font-bold">{{ $player->hp }} / {{ $player->max_hp }}</span>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between items-center">
                            <span class="text-white/40">
                                <i class="fa-solid fa-shuffle mr-2"></i>Chaos
                            </span>
                            <span class="font-mono">{{ $player->chaotic_temperature ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-white/40">
                                <i class="fa-solid fa-sun mr-2"></i>Positivity
                            </span>
                            <span class="font-mono">{{ $player->positive_temperature ?? 0 }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Column 2: Personality -->
            <div class="space-y-4">
                <div class="bg-black/30 rounded-lg p-4">
                    <h4 class="text-xs font-semibold text-white/40 uppercase tracking-wide mb-3">
                        <i class="fa-solid fa-masks-theater mr-2"></i>Personality
                        @if($player->personality_severity)
                            <span class="ml-2 text-dnd-red font-normal">({{ $player->personality_severity }}/10)</span>
                        @endif
                    </h4>
                    <p class="text-white/80 text-sm leading-relaxed">{{ $player->personality ?? '-' }}</p>
                </div>

                <div class="bg-black/30 rounded-lg p-4">
                    <h4 class="text-xs font-semibold text-white/40 uppercase tracking-wide mb-3">
                        <i class="fa-solid fa-fingerprint mr-2"></i>Traits
                    </h4>
                    <p class="text-white/80 text-sm leading-relaxed">{{ $player->traits ?? '-' }}</p>
                </div>

                <div class="bg-black/30 rounded-lg p-4">
                    <h4 class="text-xs font-semibold text-white/40 uppercase tracking-wide mb-3">
                        <i class="fa-solid fa-screwdriver-wrench mr-2"></i>Skills
                    </h4>
                    <p class="text-white/80 text-sm leading-relaxed">{{ $player->skills ?? '-' }}</p>
                </div>

                <div class="bg-black/30 rounded-lg p-4">
                    <h4 class="text-xs font-semibold text-white/40 uppercase tracking-wide mb-3">
                        <i class="fa-solid fa-ghost mr-2"></i>Trauma
                        @if($player->trauma_severity)
                            <span class="ml-2 text-dnd-red font-normal">({{ $player->trauma_severity }}/10)</span>
                        @endif
                    </h4>
                    <p class="text-white/80 text-sm leading-relaxed">{{ $player->trauma ?? '-' }}</p>
                </div>
            </div>

            <!-- Column 3: Motivations -->
            <div class="space-y-4">
                <div class="bg-black/30 rounded-lg p-4">
                    <h4 class="text-xs font-semibold text-white/40 uppercase tracking-wide mb-3">
                        <i class="fa-solid fa-bullseye mr-2"></i>Goals
                        @if($player->goal_severity)
                            <span class="ml-2 text-dnd-red font-normal">({{ $player->goal_severity }}/10)</span>
                        @endif
                    </h4>
                    <p class="text-white/80 text-sm leading-relaxed">{{ $player->goals ?? '-' }}</p>
                </div>

                <div class="bg-black/30 rounded-lg p-4">
                    <h4 class="text-xs font-semibold text-white/40 uppercase tracking-wide mb-3">
                        <i class="fa-solid fa-compass mr-2"></i>Intentions
                        @if($player->intention_severity)
                            <span class="ml-2 text-dnd-red font-normal">({{ $player->intention_severity }}/10)</span>
                        @endif
                    </h4>
                    <p class="text-white/80 text-sm leading-relaxed">{{ $player->intentions ?? '-' }}</p>
                </div>

                <div class="bg-black/30 rounded-lg p-4">
                    <h4 class="text-xs font-semibold text-white/40 uppercase tracking-wide mb-3">
                        <i class="fa-solid fa-user-secret mr-2"></i>Secrets
                    </h4>
                    <p class="text-white/80 text-sm leading-relaxed">{{ $player->secrets ?? '-' }}</p>
                </div>

                <div class="bg-black/30 rounded-lg p-4">
                    <h4 class="text-xs font-semibold text-white/40 uppercase tracking-wide mb-3">
                        <i class="fa-solid fa-ban mr-2"></i>Limits
                    </h4>
                    <p class="text-white/80 text-sm leading-relaxed">{{ $player->limits ?? '-' }}</p>
                </div>
            </div>
        </div>
    @else
        <div class="card p-6 mb-6 text-center">
            <i class="fa-solid fa-user-slash text-4xl text-white/20 mb-4"></i>
            <p class="text-white/60">No player character found.</p>
        </div>
    @endif

    <!-- World Section -->
    @if($game->world)
        <div class="bg-black/30 rounded-lg p-4 mb-6">
            <h4 class="text-xs font-semibold text-white/40 uppercase tracking-wide mb-4">
                <i class="fa-solid fa-globe mr-2"></i>World
            </h4>
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <span class="text-xs text-white/40 block mb-1">
                        <i class="fa-regular fa-clock mr-1"></i>Time
                    </span>
                    <p class="text-white/80 text-sm">{{ $game->world->time ?? '-' }}</p>
                </div>
                <div>
                    <span class="text-xs text-white/40 block mb-1">
                        <i class="fa-solid fa-scroll mr-1"></i>Universe Rules
                    </span>
                    <p class="text-white/80 text-sm">{{ $game->world->universe_rules ?? '-' }}</p>
                </div>
                <div>
                    <span class="text-xs text-white/40 block mb-1">
                        <i class="fa-solid fa-mountain-sun mr-1"></i>Environment
                    </span>
                    <p class="text-white/80 text-sm">{{ $game->world->environment_description ?? '-' }}</p>
                </div>
            </div>
        </div>
    @endif

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
