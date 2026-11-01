<?php

use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('webhooks')->name('webhooks.')->group(function () {
    Route::get('{driver}', [WhatsAppWebhookController::class, 'verify'])
        ->whereIn('driver', ['meta', 'evolution', 'twilio'])
        ->name('verify');

    Route::post('{driver}', [WhatsAppWebhookController::class, 'handle'])
        ->whereIn('driver', ['meta', 'evolution', 'twilio'])
        ->name('handle');
});

Route::prefix('chat')->name('chat.')->group(function () {
    Route::post('/', [ChatController::class, 'start'])
        ->middleware('throttle:10,1')
        ->name('start');

    Route::get('{chatSession:token}/messages', [ChatController::class, 'messages'])
        ->middleware('throttle:120,1')
        ->name('messages');

    Route::post('{chatSession:token}/messages', [ChatController::class, 'send'])
        ->middleware('throttle:30,1')
        ->name('send');
});
