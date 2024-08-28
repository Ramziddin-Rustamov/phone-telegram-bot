<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\TelegramBotController;

Route::post('/bot/webhook', [TelegramBotController::class, 'handle']);
Route::get('/', function () {
    return view('welcome');
});
