@extends('layouts.app')

@section('title', 'Games')

@section('content')
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-bold">Games</h1>
        <button id="generate-btn" class="btn-primary">🎲 Generate World</button>
    </div>

    <!-- Generation Result (hidden initially) -->
    <div id="generation-result" class="card p-6 mb-8 hidden">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold">Generated World</h2>
            <button id="close-result" class="text-white/40 hover:text-white text-2xl">&times;</button>
        </div>

        <div id="generation-loading" class="text-center py-8 hidden">
            <div class="inline-block animate-spin text-4xl">🎲</div>
            <p class="text-white/60 mt-4">Generating world...</p>
        </div>

        <div id="generation-content" class="hidden">
            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Character -->
                <div class="bg-black/30 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-dnd-red mb-3">👤 Character</h3>
                    <pre id="character-json" class="text-sm text-white/80 whitespace-pre-wrap overflow-auto max-h-96"></pre>
                </div>

                <!-- World -->
                <div class="bg-black/30 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-dnd-red mb-3">🌍 World</h3>
                    <pre id="world-json" class="text-sm text-white/80 whitespace-pre-wrap overflow-auto max-h-96"></pre>
                </div>
            </div>

            <div class="mt-6 flex gap-4">
                <button id="start-game-btn" class="btn-primary">Start Game</button>
                <button id="regenerate-btn" class="px-4 py-2 bg-white/10 rounded-lg hover:bg-white/20 transition">Regenerate</button>
            </div>
        </div>

        <div id="generation-error" class="hidden text-red-400 py-4"></div>
    </div>

    <!-- Games List -->
    @if($games->isEmpty())
        <div class="card p-12 text-center">
            <p class="text-white/60 mb-4">No games yet. Generate your first world!</p>
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach($games as $game)
                <a href="{{ route('game.show', $game) }}" class="card p-6 transition hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-2xl">🎲</span>
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
        const characterJson = document.getElementById('character-json');
        const worldJson = document.getElementById('world-json');

        let generatedData = null;

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
                    characterJson.textContent = JSON.stringify(data.character, null, 2);
                    worldJson.textContent = JSON.stringify(data.world, null, 2);
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

        // TODO: Wire up start-game-btn to create game with generatedData
        document.getElementById('start-game-btn').addEventListener('click', () => {
            console.log('Start game with:', generatedData);
            alert('TODO: Create game endpoint');
        });
    </script>
@endpush
