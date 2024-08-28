<?php

use App\Http\Controllers\TelegramBotController;
use Illuminate\Support\Facades\Route;

Route::post('/bot/webhook', [TelegramBotController::class, 'handle']);
Route::get('/', function () {
    return view('welcome');
});
