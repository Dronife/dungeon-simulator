<?php

use App\Http\Controllers\GameController;
use App\Http\Controllers\PlayController;
use App\Http\Controllers\PlaygroundController;
use App\Http\Controllers\SimulationController;
use App\Models\Game;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/test', fn() => Inertia::render('Test'));
Route::get('/generate/magic', fn() => Inertia::render('Games/MagicGenerator'));


Route::get('/testllm', [\App\Http\Controllers\Research\TestController::class, 'test']);
Route::get('/test-memory', [\App\Http\Controllers\Research\MemoryTestController::class, 'testMemory']);

Route::get('/', fn() => redirect()->route('game.index'));

// Games
Route::get('/game', [GameController::class, 'index'])->name('game.index');
Route::post('/game', [GameController::class, 'store'])->name('game.store');
Route::get('/game/create', fn() => Inertia::render('Games/Create'))->name('game.create');
Route::get('/game/generate', [GameController::class, 'generate'])->name('game.generate');
Route::get('/game/world-generate', [GameController::class, 'generateWorld'])->name('game.world-generate');
Route::get('/game/world-images/{cacheKey}', [GameController::class, 'worldImages'])->name('game.world-images');
Route::get('/game/character-builder', fn() => Inertia::render('Games/CharacterBuilder'))->name('game.character-builder');
Route::post('/api/character/generate-trait', [GameController::class, 'generateTrait'])->name('character.generate-trait');
Route::get('/game/{game}/inventory', fn(Game $game) => Inertia::render('Games/Inventory', ['game' => $game->load(['characters'])]))->name('game.inventory');
Route::get('/game/{game}/play', [PlayController::class, 'show'])->name('game.play');
Route::post('/game/{game}/play', [PlayController::class, 'message'])->name('game.play.message');
Route::post('/game/{game}/play/init', [PlayController::class, 'init'])->name('game.play.init');
Route::get('/game/{game}', [GameController::class, 'show'])->name('game.show');
Route::delete('/game/{game}', [GameController::class, 'destroy'])->name('game.destroy');

// Playground
Route::get('/playground', [PlaygroundController::class, 'index'])->name('playground');
Route::post('/playground/generate', [PlaygroundController::class, 'generate'])->name('playground.generate');

// Simulation
Route::get('/simulation', [SimulationController::class, 'index'])->name('simulation.index');
Route::post('/simulation/tick', [SimulationController::class, 'tick'])->name('simulation.tick');
Route::post('/simulation/reset', [SimulationController::class, 'reset'])->name('simulation.reset');
Route::get('/simulation/npc/{npc}', [SimulationController::class, 'npc'])->name('simulation.npc');
Route::get('/simulation/object/{object}', [SimulationController::class, 'object'])->name('simulation.object');
Route::get('/simulation/place/{place}', [SimulationController::class, 'place'])->name('simulation.place');
