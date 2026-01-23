@extends('layouts.app')

@section('title', 'Games')

@section('content')
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-bold">Games</h1>
        <button id="generate-btn" class="btn-primary">
            <i class="fa-solid fa-dice-d20 mr-2"></i>Generate World
        </button>
    </div>

    <!-- Generation Result (hidden initially) -->
    <div id="generation-result" class="card p-6 mb-8 hidden">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold">Generated Character</h2>
            <button id="close-result" class="text-white/40 hover:text-white text-2xl">&times;</button>
        </div>

        <div id="generation-loading" class="text-center py-12 hidden">
            <i class="fa-solid fa-dice-d20 fa-spin text-4xl text-dnd-red"></i>
            <p class="text-white/60 mt-4">Rolling for destiny...</p>
        </div>

        <div id="generation-content" class="hidden">
            <div class="grid gap-6 lg:grid-cols-3">

                <!-- Column 1: Identity + Stats -->
                <div class="space-y-4">
                    <!-- Name & Info -->
                    <div class="bg-black/30 rounded-lg p-4">
                        <h3 id="char-name" class="text-2xl font-bold text-dnd-red mb-1"></h3>
                        <p id="char-job" class="text-white/50 text-sm mb-3"></p>
                        <p id="char-info" class="text-white/80 text-sm leading-relaxed"></p>
                    </div>

                    <!-- Stats Grid -->
                    <div class="bg-black/30 rounded-lg p-4">
                        <h4 class="text-xs font-semibold text-white/40 uppercase tracking-wide mb-3">
                            <i class="fa-solid fa-chart-simple mr-2"></i>Attributes
                        </h4>
                        <div class="grid grid-cols-3 gap-2 text-center">
                            <div class="bg-black/40 rounded p-2">
                                <div class="text-xs text-white/40">STR</div>
                                <div id="stat-str" class="text-xl font-bold"></div>
                                <div id="mod-str" class="text-xs text-dnd-red"></div>
                            </div>
                            <div class="bg-black/40 rounded p-2">
                                <div class="text-xs text-white/40">DEX</div>
                                <div id="stat-dex" class="text-xl font-bold"></div>
                                <div id="mod-dex" class="text-xs text-dnd-red"></div>
                            </div>
                            <div class="bg-black/40 rounded p-2">
                                <div class="text-xs text-white/40">CON</div>
                                <div id="stat-con" class="text-xl font-bold"></div>
                                <div id="mod-con" class="text-xs text-dnd-red"></div>
                            </div>
                            <div class="bg-black/40 rounded p-2">
                                <div class="text-xs text-white/40">INT</div>
                                <div id="stat-int" class="text-xl font-bold"></div>
                                <div id="mod-int" class="text-xs text-dnd-red"></div>
                            </div>
                            <div class="bg-black/40 rounded p-2">
                                <div class="text-xs text-white/40">WIS</div>
                                <div id="stat-wis" class="text-xl font-bold"></div>
                                <div id="mod-wis" class="text-xs text-dnd-red"></div>
                            </div>
                            <div class="bg-black/40 rounded p-2">
                                <div class="text-xs text-white/40">CHA</div>
                                <div id="stat-cha" class="text-xl font-bold"></div>
                                <div id="mod-cha" class="text-xs text-dnd-red"></div>
                            </div>
                        </div>
                    </div>

                    <!-- HP & Temperament -->
                    <div class="bg-black/30 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-xs text-white/40 uppercase tracking-wide">
                                <i class="fa-solid fa-heart mr-1 text-dnd-red"></i>Hit Points
                            </span>
                            <span id="char-hp" class="text-xl font-bold"></span>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between items-center">
                                <span class="text-white/40">
                                    <i class="fa-solid fa-shuffle mr-2"></i>Chaos
                                </span>
                                <span id="char-chaos" class="font-mono"></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-white/40">
                                    <i class="fa-solid fa-sun mr-2"></i>Positivity
                                </span>
                                <span id="char-positive" class="font-mono"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Column 2: Personality -->
                <div class="space-y-4">
                    <div class="bg-black/30 rounded-lg p-4">
                        <h4 class="text-xs font-semibold text-white/40 uppercase tracking-wide mb-3">
                            <i class="fa-solid fa-masks-theater mr-2"></i>Personality
                            <span id="char-personality-sev" class="ml-2 text-dnd-red font-normal"></span>
                        </h4>
                        <p id="char-personality" class="text-white/80 text-sm leading-relaxed"></p>
                    </div>

                    <div class="bg-black/30 rounded-lg p-4">
                        <h4 class="text-xs font-semibold text-white/40 uppercase tracking-wide mb-3">
                            <i class="fa-solid fa-fingerprint mr-2"></i>Traits
                        </h4>
                        <p id="char-traits" class="text-white/80 text-sm leading-relaxed"></p>
                    </div>

                    <div class="bg-black/30 rounded-lg p-4">
                        <h4 class="text-xs font-semibold text-white/40 uppercase tracking-wide mb-3">
                            <i class="fa-solid fa-screwdriver-wrench mr-2"></i>Skills
                        </h4>
                        <p id="char-skills" class="text-white/80 text-sm leading-relaxed"></p>
                    </div>

                    <div class="bg-black/30 rounded-lg p-4">
                        <h4 class="text-xs font-semibold text-white/40 uppercase tracking-wide mb-3">
                            <i class="fa-solid fa-ghost mr-2"></i>Trauma
                            <span id="char-trauma-sev" class="ml-2 text-dnd-red font-normal"></span>
                        </h4>
                        <p id="char-trauma" class="text-white/80 text-sm leading-relaxed"></p>
                    </div>
                </div>

                <!-- Column 3: Motivations -->
                <div class="space-y-4">
                    <div class="bg-black/30 rounded-lg p-4">
                        <h4 class="text-xs font-semibold text-white/40 uppercase tracking-wide mb-3">
                            <i class="fa-solid fa-bullseye mr-2"></i>Goals
                            <span id="char-goal-sev" class="ml-2 text-dnd-red font-normal"></span>
                        </h4>
                        <p id="char-goals" class="text-white/80 text-sm leading-relaxed"></p>
                    </div>

                    <div class="bg-black/30 rounded-lg p-4">
                        <h4 class="text-xs font-semibold text-white/40 uppercase tracking-wide mb-3">
                            <i class="fa-solid fa-compass mr-2"></i>Intentions
                            <span id="char-intention-sev" class="ml-2 text-dnd-red font-normal"></span>
                        </h4>
                        <p id="char-intentions" class="text-white/80 text-sm leading-relaxed"></p>
                    </div>

                    <div class="bg-black/30 rounded-lg p-4">
                        <h4 class="text-xs font-semibold text-white/40 uppercase tracking-wide mb-3">
                            <i class="fa-solid fa-user-secret mr-2"></i>Secrets
                        </h4>
                        <p id="char-secrets" class="text-white/80 text-sm leading-relaxed"></p>
                    </div>

                    <div class="bg-black/30 rounded-lg p-4">
                        <h4 class="text-xs font-semibold text-white/40 uppercase tracking-wide mb-3">
                            <i class="fa-solid fa-ban mr-2"></i>Limits
                        </h4>
                        <p id="char-limits" class="text-white/80 text-sm leading-relaxed"></p>
                    </div>
                </div>
            </div>

            <!-- World Section -->
            <div class="mt-6 bg-black/30 rounded-lg p-4">
                <h4 class="text-xs font-semibold text-white/40 uppercase tracking-wide mb-4">
                    <i class="fa-solid fa-globe mr-2"></i>World
                </h4>
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <span class="text-xs text-white/40 block mb-1">
                            <i class="fa-regular fa-clock mr-1"></i>Time
                        </span>
                        <p id="world-time" class="text-white/80 text-sm"></p>
                    </div>
                    <div>
                        <span class="text-xs text-white/40 block mb-1">
                            <i class="fa-solid fa-scroll mr-1"></i>Universe Rules
                        </span>
                        <p id="world-rules" class="text-white/80 text-sm"></p>
                    </div>
                    <div>
                        <span class="text-xs text-white/40 block mb-1">
                            <i class="fa-solid fa-mountain-sun mr-1"></i>Environment
                        </span>
                        <p id="world-env" class="text-white/80 text-sm"></p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-6 flex gap-4">
                <button id="start-game-btn" class="btn-primary">
                    <i class="fa-solid fa-play mr-2"></i>Start Game
                </button>
                <button id="regenerate-btn" class="px-4 py-2 bg-white/10 rounded-lg hover:bg-white/20 transition">
                    <i class="fa-solid fa-rotate mr-2"></i>Regenerate
                </button>
            </div>
        </div>

        <div id="generation-error" class="hidden text-red-400 py-4"></div>
    </div>

    <!-- Games List -->
    @if($games->isEmpty())
        <div class="card p-12 text-center">
            <i class="fa-solid fa-dice-d20 text-4xl text-white/20 mb-4"></i>
            <p class="text-white/60">No games yet. Generate your first world.</p>
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach($games as $game)
                <a href="{{ route('game.show', $game) }}" class="card p-6 transition hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <i class="fa-solid fa-dice-d20 text-2xl text-dnd-red"></i>
                        <span class="text-white/40 text-sm">#{{ $game->id }}</span>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Game #{{ $game->id }}</h3>
                    <p class="text-white/60 text-sm">Created {{ $game->created_at->diffForHumans() }}</p>
                </a>
            @endforeach
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        const generateBtn = document.getElementById('generate-btn');
        const regenerateBtn = document.getElementById('regenerate-btn');
        const resultDiv = document.getElementById('generation-result');
        const loadingDiv = document.getElementById('generation-loading');
        const contentDiv = document.getElementById('generation-content');
        const errorDiv = document.getElementById('generation-error');
        const closeBtn = document.getElementById('close-result');

        let generatedData = null;

        function calcModifier(stat) {
            const mod = Math.floor((stat - 10) / 2);
            return mod >= 0 ? `+${mod}` : `${mod}`;
        }

        function formatSeverity(val) {
            if (!val && val !== 0) return '';
            return `(${val}/10)`;
        }

        function setText(id, value) {
            const el = document.getElementById(id);
            if (el) el.textContent = value || '-';
        }

        function populateSheet(data) {
            const c = data.character;
            const w = data.world;

            // Basic info
            setText('char-name', c.name);
            setText('char-job', c.job);
            setText('char-info', c.info);

            // Stats
            ['str', 'dex', 'con', 'int', 'wis', 'cha'].forEach(stat => {
                const val = c[stat] || 10;
                setText(`stat-${stat}`, val);
                setText(`mod-${stat}`, calcModifier(val));
            });

            // HP & Temperament
            setText('char-hp', `${c.hp || 10} / ${c.max_hp || 10}`);
            setText('char-chaos', c['chaotic-temperature'] ?? c['chaotic_temperature'] ?? '-');
            setText('char-positive', c['positive-temperature'] ?? c['positive_temperature'] ?? '-');

            // Personality sections
            setText('char-personality', c.personality);
            setText('char-personality-sev', formatSeverity(c.personality_severity));
            setText('char-traits', c.traits);
            setText('char-skills', c.skills);

            setText('char-trauma', c.trauma);
            setText('char-trauma-sev', formatSeverity(c.trauma_severity));

            setText('char-goals', c.goals);
            setText('char-goal-sev', formatSeverity(c.goal_severity));

            setText('char-intentions', c.intentions);
            setText('char-intention-sev', formatSeverity(c.intention_severity));

            setText('char-secrets', c.secrets);
            setText('char-limits', c.limits);

            // World
            setText('world-time', w.time);
            setText('world-rules', w.universe_rules);
            setText('world-env', w.environment_description);
        }

        async function generateWorld() {
            resultDiv.classList.remove('hidden');
            loadingDiv.classList.remove('hidden');
            contentDiv.classList.add('hidden');
            errorDiv.classList.add('hidden');
            generateBtn.disabled = true;

            try {
                const response = await fetch('{{ route("game.generate") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });

                const data = await response.json();

                if (response.ok) {
                    generatedData = data;
                    populateSheet(data);
                    loadingDiv.classList.add('hidden');
                    contentDiv.classList.remove('hidden');
                } else {
                    throw new Error(data.message || 'Generation failed');
                }
            } catch (error) {
                loadingDiv.classList.add('hidden');
                errorDiv.textContent = error.message;
                errorDiv.classList.remove('hidden');
            } finally {
                generateBtn.disabled = false;
            }
        }

        generateBtn.addEventListener('click', generateWorld);
        regenerateBtn.addEventListener('click', generateWorld);
        closeBtn.addEventListener('click', () => {
            resultDiv.classList.add('hidden');
            generatedData = null;
        });

        document.getElementById('start-game-btn').addEventListener('click', async () => {
            if (!generatedData) return;

            const btn = document.getElementById('start-game-btn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Creating...';

            try {
                const response = await fetch('{{ route("game.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(generatedData),
                });

                const data = await response.json();

                if (response.ok && data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    throw new Error(data.message || 'Failed to create game');
                }
            } catch (error) {
                alert('Error: ' + error.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-play mr-2"></i>Start Game';
            }
        });
    </script>
@endpush
