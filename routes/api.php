<?php

use App\Http\Controllers\Api\WhatsAppApiController;
use Illuminate\Support\Facades\Route;

// Public WhatsApp Webhook endpoints
Route::prefix('v1/whatsapp')->group(function () {
    Route::get('/webhook', [WhatsAppApiController::class, 'verifyWebhook']);
    Route::post('/webhook', [WhatsAppApiController::class, 'handleWebhook']);
    Route::post('/webhook/batch', [WhatsAppApiController::class, 'handleBatchWebhook']);
    Route::post('/extract', [WhatsAppApiController::class, 'extract']);
    Route::get('/gateway/status', [WhatsAppApiController::class, 'getGatewayStatus']);
    Route::post('/gateway/sync', [WhatsAppApiController::class, 'triggerGatewaySync']);
    Route::post('/gateway/disconnect', [WhatsAppApiController::class, 'disconnectGateway']);
});

// API Routes for Orders (Today & Date-Wise)
Route::prefix('v1/orders')->group(function () {
    Route::get('/today', [WhatsAppApiController::class, 'getTodayOrders']);
    Route::get('/date-wise', [WhatsAppApiController::class, 'getOrdersByDate']);
});
