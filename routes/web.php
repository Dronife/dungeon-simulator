<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/testllm', [\App\Http\Controllers\TestController::class, 'test']);


