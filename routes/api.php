<?php

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
