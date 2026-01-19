<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'DND Engine')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --dnd-red: #FF2D20;
            --dnd-red-dark: #cc241a;
        }
        body {
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%);
        }
        .text-dnd-red { color: var(--dnd-red); }
        .bg-dnd-red { background-color: var(--dnd-red); }
        .border-dnd-red { border-color: var(--dnd-red); }
        .hover\:bg-dnd-red:hover { background-color: var(--dnd-red); }
        .selection\:bg-dnd-red::selection { background-color: var(--dnd-red); }

        .card {
            background: rgba(30, 30, 30, 0.8);
            border: 1px solid rgba(255, 45, 32, 0.2);
            border-radius: 0.5rem;
            backdrop-filter: blur(10px);
        }
        .card:hover {
            border-color: rgba(255, 45, 32, 0.5);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--dnd-red) 0%, var(--dnd-red-dark) 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 45, 32, 0.4);
        }

        .input-dark {
            background: rgba(20, 20, 20, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
        }
        .input-dark:focus {
            outline: none;
            border-color: var(--dnd-red);
            box-shadow: 0 0 0 2px rgba(255, 45, 32, 0.2);
        }

        .prose-dark {
            color: rgba(255, 255, 255, 0.8);
        }
        .prose-dark strong {
            color: var(--dnd-red);
        }
    </style>
</head>
<body class="min-h-screen text-white selection:bg-dnd-red selection:text-white">
<nav class="border-b border-white/10 px-6 py-4">
    <div class="max-w-7xl mx-auto flex items-center justify-between">
        <a href="{{ route('game.index') }}" class="flex items-center gap-2">
            <span class="text-2xl">🎲</span>
            <span class="text-xl font-bold text-dnd-red">DND Engine</span>
        </a>
        <div class="flex items-center gap-4">
            <a href="{{ route('game.index') }}" class="text-white/70 hover:text-white transition">Games</a>
            <a href="{{ route('playground') }}" class="text-white/70 hover:text-white transition">Playground</a>
        </div>
    </div>
</nav>

<main class="max-w-7xl mx-auto px-6 py-8">
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-500/20 border border-green-500/50 rounded-lg text-green-400">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 bg-red-500/20 border border-red-500/50 rounded-lg text-red-400">
            {{ session('error') }}
        </div>
    @endif

    @yield('content')
</main>

<footer class="border-t border-white/10 px-6 py-4 mt-auto">
    <div class="max-w-7xl mx-auto text-center text-white/40 text-sm">
        DND Engine &mdash; Built with Laravel & Gemini
    </div>
</footer>

@stack('scripts')
</body>
</html>
