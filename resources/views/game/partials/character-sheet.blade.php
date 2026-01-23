{{-- Character Sheet Partial --}}
{{-- Usage: @include('game.partials.character-sheet', ['player' => $player, 'world' => $world, 'lore' => $lore]) --}}

@if($player)
    <!-- Character Sheet -->
    <div class="grid gap-6 lg:grid-cols-3 mb-6">

        <!-- Column 1: Identity + Stats -->
        <div class="space-y-4">
            <div class="bg-black/30 rounded-lg p-4">
                <h3 class="text-2xl font-bold text-dnd-red mb-1">{{ $player['name'] ?? '-' }}</h3>
                <p class="text-white/50 text-sm mb-3">{{ $player['job'] ?? '-' }}</p>
                <p class="text-white/80 text-sm leading-relaxed">{{ $player['info'] ?? '-' }}</p>
            </div>

            <!-- Stats Grid -->
            <div class="bg-black/30 rounded-lg p-4">
                <h4 class="text-xs font-semibold text-white/40 uppercase tracking-wide mb-3">
                    <i class="fa-solid fa-chart-simple mr-2"></i>Attributes
                </h4>
                <div class="grid grid-cols-3 gap-2 text-center">
                    @foreach(['str', 'dex', 'con', 'int', 'wis', 'cha'] as $stat)
                        @php
                            $val = $player[$stat] ?? 10;
                            $mod = floor(($val - 10) / 2);
                        @endphp
                        <div class="bg-black/40 rounded p-2">
                            <div class="text-xs text-white/40">{{ strtoupper($stat) }}</div>
                            <div class="text-xl font-bold">{{ $val }}</div>
                            <div class="text-xs text-dnd-red">{{ $mod >= 0 ? '+' . $mod : $mod }}</div>
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
                    <span class="text-xl font-bold">{{ $player['hp'] ?? 10 }} / {{ $player['max_hp'] ?? 10 }}</span>
                </div>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between items-center">
                        <span class="text-white/40">
                            <i class="fa-solid fa-shuffle mr-2"></i>Chaos
                        </span>
                        <span class="font-mono">{{ $player['chaotic_temperature'] ?? $player['chaotic-temperature'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-white/40">
                            <i class="fa-solid fa-sun mr-2"></i>Positivity
                        </span>
                        <span class="font-mono">{{ $player['positive_temperature'] ?? $player['positive-temperature'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-white/40">
                            <i class="fa-solid fa-fire mr-2"></i>Temperature
                        </span>
                        <span class="font-mono">{{ $player['temperature'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Column 2: Personality -->
        <div class="space-y-4">
            @if(!empty($player['personality']))
                <div class="bg-black/30 rounded-lg p-4">
                    <h4 class="text-xs font-semibold text-white/40 uppercase tracking-wide mb-3">
                        <i class="fa-solid fa-masks-theater mr-2"></i>Personality
                        @if($player['personality_severity'] ?? null)
                            <span class="ml-2 text-dnd-red font-normal">({{ $player['personality_severity'] }}/10)</span>
                        @endif
                    </h4>
                    <p class="text-white/80 text-sm leading-relaxed">{{ $player['personality'] }}</p>
                </div>
            @endif

            @if(!empty($player['traits']))
                <div class="bg-black/30 rounded-lg p-4">
                    <h4 class="text-xs font-semibold text-white/40 uppercase tracking-wide mb-3">
                        <i class="fa-solid fa-fingerprint mr-2"></i>Traits
                    </h4>
                    <p class="text-white/80 text-sm leading-relaxed">{{ $player['traits'] }}</p>
                </div>
            @endif

            @if(!empty($player['skills']))
                <div class="bg-black/30 rounded-lg p-4">
                    <h4 class="text-xs font-semibold text-white/40 uppercase tracking-wide mb-3">
                        <i class="fa-solid fa-screwdriver-wrench mr-2"></i>Skills
                    </h4>
                    <p class="text-white/80 text-sm leading-relaxed">{{ $player['skills'] }}</p>
                </div>
            @endif

            @if(!empty($player['trauma']))
                <div class="bg-black/30 rounded-lg p-4">
                    <h4 class="text-xs font-semibold text-white/40 uppercase tracking-wide mb-3">
                        <i class="fa-solid fa-ghost mr-2"></i>Trauma
                        @if($player['trauma_severity'] ?? null)
                            <span class="ml-2 text-dnd-red font-normal">({{ $player['trauma_severity'] }}/10)</span>
                        @endif
                    </h4>
                    <p class="text-white/80 text-sm leading-relaxed">{{ $player['trauma'] }}</p>
                </div>
            @endif

            @if(!empty($player['hobbies']))
                <div class="bg-black/30 rounded-lg p-4">
                    <h4 class="text-xs font-semibold text-white/40 uppercase tracking-wide mb-3">
                        <i class="fa-solid fa-gamepad mr-2"></i>Hobbies
                    </h4>
                    <p class="text-white/80 text-sm leading-relaxed">{{ $player['hobbies'] }}</p>
                </div>
            @endif

            @if(!empty($player['routines']))
                <div class="bg-black/30 rounded-lg p-4">
                    <h4 class="text-xs font-semibold text-white/40 uppercase tracking-wide mb-3">
                        <i class="fa-solid fa-clock-rotate-left mr-2"></i>Routines
                    </h4>
                    <p class="text-white/80 text-sm leading-relaxed">{{ $player['routines'] }}</p>
                </div>
            @endif

            @if(!empty($player['job']))
                <div class="bg-black/30 rounded-lg p-4">
                    <h4 class="text-xs font-semibold text-white/40 uppercase tracking-wide mb-3">
                        <i class="fa-solid fa-briefcase mr-2"></i>Job
                    </h4>
                    <p class="text-white/80 text-sm leading-relaxed">{{ $player['job'] }}</p>
                </div>
            @endif
        </div>

        <!-- Column 3: Motivations -->
        <div class="space-y-4">
            @if(!empty($player['goals']))
                <div class="bg-black/30 rounded-lg p-4">
                    <h4 class="text-xs font-semibold text-white/40 uppercase tracking-wide mb-3">
                        <i class="fa-solid fa-bullseye mr-2"></i>Goals
                        @if($player['goal_severity'] ?? null)
                            <span class="ml-2 text-dnd-red font-normal">({{ $player['goal_severity'] }}/10)</span>
                        @endif
                    </h4>
                    <p class="text-white/80 text-sm leading-relaxed">{{ $player['goals'] }}</p>
                </div>
            @endif

            @if(!empty($player['intentions']))
                <div class="bg-black/30 rounded-lg p-4">
                    <h4 class="text-xs font-semibold text-white/40 uppercase tracking-wide mb-3">
                        <i class="fa-solid fa-compass mr-2"></i>Intentions
                        @if($player['intention_severity'] ?? null)
                            <span class="ml-2 text-dnd-red font-normal">({{ $player['intention_severity'] }}/10)</span>
                        @endif
                    </h4>
                    <p class="text-white/80 text-sm leading-relaxed">{{ $player['intentions'] }}</p>
                </div>
            @endif

            @if(!empty($player['secrets']))
                <div class="bg-black/30 rounded-lg p-4">
                    <h4 class="text-xs font-semibold text-white/40 uppercase tracking-wide mb-3">
                        <i class="fa-solid fa-user-secret mr-2"></i>Secrets
                    </h4>
                    <p class="text-white/80 text-sm leading-relaxed">{{ $player['secrets'] }}</p>
                </div>
            @endif

            @if(!empty($player['limits']))
                <div class="bg-black/30 rounded-lg p-4">
                    <h4 class="text-xs font-semibold text-white/40 uppercase tracking-wide mb-3">
                        <i class="fa-solid fa-ban mr-2"></i>Limits
                    </h4>
                    <p class="text-white/80 text-sm leading-relaxed">{{ $player['limits'] }}</p>
                </div>
            @endif
        </div>
    </div>
@else
    <div class="card p-6 mb-6 text-center">
        <i class="fa-solid fa-user-slash text-4xl text-white/20 mb-4"></i>
        <p class="text-white/60">No player character found.</p>
    </div>
@endif

<!-- World Section -->
@if($world)
    <div class="bg-black/30 rounded-lg p-4 mb-6">
        <h4 class="text-xs font-semibold text-white/40 uppercase tracking-wide mb-4">
            <i class="fa-solid fa-globe mr-2"></i>World
        </h4>
        <div class="grid gap-4 md:grid-cols-3">
            <div>
                <span class="text-xs text-white/40 block mb-1">
                    <i class="fa-regular fa-clock mr-1"></i>Time
                </span>
                <p class="text-white/80 text-sm">{{ $world['time'] ?? '-' }}</p>
            </div>
            <div>
                <span class="text-xs text-white/40 block mb-1">
                    <i class="fa-solid fa-scroll mr-1"></i>Universe Rules
                </span>
                <p class="text-white/80 text-sm">{{ $world['universe_rules'] ?? '-' }}</p>
            </div>
            <div>
                <span class="text-xs text-white/40 block mb-1">
                    <i class="fa-solid fa-mountain-sun mr-1"></i>Environment
                </span>
                <p class="text-white/80 text-sm">{{ $world['environment_description'] ?? '-' }}</p>
            </div>
        </div>
    </div>
@endif

<!-- World Lore -->
@if($lore && count($lore) > 0)
    <div class="bg-black/30 rounded-lg p-4 mb-6">
        <h4 class="text-xs font-semibold text-white/40 uppercase tracking-wide mb-4">
            <i class="fa-solid fa-book-atlas mr-2"></i>World Lore
        </h4>
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach($lore as $item)
                <div class="bg-black/40 rounded-lg p-3">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-xs px-2 py-0.5 bg-dnd-red/20 text-dnd-red rounded">{{ $item['type'] ?? 'unknown' }}</span>
                        <span class="font-semibold text-white">{{ $item['name'] ?? '-' }}</span>
                    </div>
                    <p class="text-white/70 text-sm mb-2">{{ $item['description'] ?? '-' }}</p>
                    @if(!empty($item['know_how']))
                        <p class="text-white/50 text-xs mb-1"><i class="fa-solid fa-magnifying-glass mr-1"></i>{{ $item['know_how'] }}</p>
                    @endif
                    @if(!empty($item['reason']))
                        <p class="text-white/50 text-xs mb-1"><i class="fa-solid fa-circle-question mr-1"></i>{{ $item['reason'] }}</p>
                    @endif
                    @if(!empty($item['occurrence']))
                        <p class="text-white/50 text-xs"><i class="fa-regular fa-clock mr-1"></i>{{ $item['occurrence'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif
