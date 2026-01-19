@extends('layouts.app')

@section('title', 'Playground')

@section('content')
    <h1 class="text-3xl font-bold mb-8">LLM Playground</h1>

    <div class="grid gap-6 lg:grid-cols-2">
        <!-- Input Panel -->
        <div class="card p-6">
            <h2 class="text-xl font-semibold mb-4">Input</h2>

            <form id="playground-form" class="space-y-4">
                <div>
                    <label class="block text-white/60 text-sm mb-2">System Prompt (optional)</label>
                    <textarea
                        name="system_prompt"
                        rows="3"
                        class="input-dark w-full resize-none"
                        placeholder="You are a fantasy DM. Be atmospheric and concise..."
                    ></textarea>
                </div>

                <div>
                    <label class="block text-white/60 text-sm mb-2">Prompt</label>
                    <textarea
                        name="prompt"
                        rows="5"
                        class="input-dark w-full resize-none"
                        placeholder="Describe a mysterious tavern at midnight..."
                        required
                    ></textarea>
                </div>

                <div>
                    <label class="block text-white/60 text-sm mb-2">
                        Temperature: <span id="temp-value">0.7</span>
                    </label>
                    <input
                        type="range"
                        name="temperature"
                        min="0"
                        max="2"
                        step="0.1"
                        value="0.7"
                        class="w-full accent-dnd-red"
                        oninput="document.getElementById('temp-value').textContent = this.value"
                    >
                    <div class="flex justify-between text-xs text-white/40 mt-1">
                        <span>Focused</span>
                        <span>Creative</span>
                    </div>
                </div>

                <button type="submit" class="btn-primary w-full" id="submit-btn">
                    Generate
                </button>
            </form>
        </div>

        <!-- Output Panel -->
        <div class="card p-6">
            <h2 class="text-xl font-semibold mb-4">Output</h2>

            <div id="output" class="bg-black/50 rounded-lg p-4 min-h-[300px] font-mono text-sm prose-dark whitespace-pre-wrap">
                <span class="text-white/40">// Response will appear here...</span>
            </div>

            <div id="stats" class="mt-4 text-sm text-white/40 hidden">
                <span>Prompt tokens: <span id="prompt-tokens">-</span></span>
                <span class="mx-2">|</span>
                <span>Completion tokens: <span id="completion-tokens">-</span></span>
            </div>
        </div>
    </div>

    <!-- Quick Prompts -->
    <div class="card p-6 mt-6">
        <h2 class="text-xl font-semibold mb-4">Quick Prompts</h2>
        <div class="flex flex-wrap gap-2">
            <button class="quick-prompt px-3 py-1.5 bg-white/5 rounded-lg text-sm hover:bg-white/10 transition" data-prompt="Describe a dark tavern at midnight. Include sounds, smells, and suspicious patrons.">
                🍺 Tavern Scene
            </button>
            <button class="quick-prompt px-3 py-1.5 bg-white/5 rounded-lg text-sm hover:bg-white/10 transition" data-prompt="Generate a mysterious NPC. Include name, appearance, personality, and a secret they're hiding.">
                👤 Random NPC
            </button>
            <button class="quick-prompt px-3 py-1.5 bg-white/5 rounded-lg text-sm hover:bg-white/10 transition" data-prompt="Create a dangerous encounter for a level 3 party. Include enemy stats and tactics.">
                ⚔️ Combat Encounter
            </button>
            <button class="quick-prompt px-3 py-1.5 bg-white/5 rounded-lg text-sm hover:bg-white/10 transition" data-prompt="Generate a magical item with a name, description, stats, and a curse or drawback.">
                ✨ Magic Item
            </button>
            <button class="quick-prompt px-3 py-1.5 bg-white/5 rounded-lg text-sm hover:bg-white/10 transition" data-prompt="The player says: 'I check if the door is locked.' Roll a d20 for investigation and describe what they find.">
                🚪 Door Check
            </button>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const form = document.getElementById('playground-form');
        const output = document.getElementById('output');
        const stats = document.getElementById('stats');
        const submitBtn = document.getElementById('submit-btn');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            submitBtn.disabled = true;
            submitBtn.textContent = 'Generating...';
            output.innerHTML = '<span class="text-white/40">// Generating...</span>';
            stats.classList.add('hidden');

            const formData = new FormData(form);

            try {
                const response = await fetch('{{ route("playground.generate") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        prompt: formData.get('prompt'),
                        system_prompt: formData.get('system_prompt'),
                        temperature: formData.get('temperature'),
                    }),
                });

                const data = await response.json();

                if (response.ok) {
                    output.textContent = data.text;
                    document.getElementById('prompt-tokens').textContent = data.prompt_tokens || '-';
                    document.getElementById('completion-tokens').textContent = data.completion_tokens || '-';
                    stats.classList.remove('hidden');
                } else {
                    output.innerHTML = `<span class="text-red-400">Error: ${data.message || 'Something went wrong'}</span>`;
                }
            } catch (error) {
                output.innerHTML = `<span class="text-red-400">Error: ${error.message}</span>`;
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Generate';
            }
        });

        // Quick prompts
        document.querySelectorAll('.quick-prompt').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelector('textarea[name="prompt"]').value = btn.dataset.prompt;
            });
        });
    </script>
@endpush
