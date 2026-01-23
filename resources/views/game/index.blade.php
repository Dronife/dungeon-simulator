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
            <div id="character-sheet-container"></div>

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
                    <h3 class="text-lg font-semibold mb-2">{{ $game->name }}</h3>
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
        const sheetContainer = document.getElementById('character-sheet-container');

        let generatedData = null;

        async function generateWorld() {
            resultDiv.classList.remove('hidden');
            loadingDiv.classList.remove('hidden');
            contentDiv.classList.add('hidden');
            errorDiv.classList.add('hidden');
            generateBtn.disabled = true;

            try {
                const response = await fetch('{{ route("game.generate") }}', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                    },
                });

                const result = await response.json();

                if (response.ok) {
                    generatedData = result.data;
                    sheetContainer.innerHTML = result.html;
                    loadingDiv.classList.add('hidden');
                    contentDiv.classList.remove('hidden');
                } else {
                    throw new Error(result.message || 'Generation failed');
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
