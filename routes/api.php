<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Tabby Payment Webhook - No auth middleware for external webhook calls
Route::post('/webhooks/tabby', [App\Http\Controllers\Api\TabbyWebhookController::class, 'handleWebhook'])->name('webhooks.tabby');
