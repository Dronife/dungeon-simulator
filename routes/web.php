<?php

use App\Http\Controllers\GameController;
use App\Http\Controllers\PlaygroundController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/test', fn() => Inertia::render('Test'));


Route::get('/testllm', [\App\Http\Controllers\TestController::class, 'test']);

Route::get('/', fn() => redirect()->route('game.index'));

// Games
Route::get('/game', [GameController::class, 'index'])->name('game.index');
Route::post('/game', [GameController::class, 'store'])->name('game.store');
Route::get('/game/generate', [GameController::class, 'generate'])->name('game.generate');
Route::get('/game/{game}', [GameController::class, 'show'])->name('game.show');
Route::delete('/game/{game}', [GameController::class, 'destroy'])->name('game.destroy');

// Playground
Route::get('/playground', [PlaygroundController::class, 'index'])->name('playground');
Route::post('/playground/generate', [PlaygroundController::class, 'generate'])->name('playground.generate');
