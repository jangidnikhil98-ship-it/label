<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\MeeshoController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PackingController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\WhatsAppController;
use Illuminate\Support\Facades\Route;

// Authentication Routes
Route::get('login', [AuthController::class, 'showLogin'])->name('login');
Route::post('login', [AuthController::class, 'login']);
Route::get('register', [AuthController::class, 'showRegister'])->name('register');
Route::post('register', [AuthController::class, 'register']);
Route::post('logout', [AuthController::class, 'logout'])->name('logout');

// Protected Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Orders Management
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::post('/orders/bulk-action', [OrderController::class, 'bulkAction'])->name('orders.bulk-action');

    // WhatsApp Module
    Route::get('/whatsapp/chats', [WhatsAppController::class, 'chats'])->name('whatsapp.chats');
    Route::get('/whatsapp/messages', [WhatsAppController::class, 'messages'])->name('whatsapp.messages');
    Route::get('/whatsapp/import', [WhatsAppController::class, 'importForm'])->name('whatsapp.import');
    Route::post('/whatsapp/import', [WhatsAppController::class, 'processImport'])->name('whatsapp.import.process');

    // Labels Module
    Route::get('/labels', [LabelController::class, 'index'])->name('labels.index');
    Route::get('/labels/unmatched', [LabelController::class, 'unmatched'])->name('labels.unmatched');
    Route::get('/labels/import', [LabelController::class, 'importForm'])->name('labels.import');
    Route::post('/labels/import', [LabelController::class, 'processImport'])->name('labels.import.process');
    Route::get('/labels/{label}/view', [LabelController::class, 'viewFile'])->name('labels.view');
    Route::get('/labels/{label}/download', [LabelController::class, 'downloadFile'])->name('labels.download');

    // Packing Mode
    Route::get('/packing', [PackingController::class, 'index'])->name('packing.index');
    Route::post('/packing/{order}/pack', [PackingController::class, 'markPackedAndNext'])->name('packing.mark-packed');

    // Reports & Multi-Format Exports
    Route::get('/reports/orders', [ExportController::class, 'orderReport'])->name('reports.orders');
    Route::get('/reports/labels', [ExportController::class, 'labelReport'])->name('reports.labels');
    Route::get('/reports/orders/export-csv', [ExportController::class, 'exportOrdersCsv'])->name('reports.orders.csv');
    Route::get('/reports/orders/export-excel', [ExportController::class, 'exportOrdersExcel'])->name('reports.orders.excel');
    Route::get('/reports/orders/export-json', [ExportController::class, 'exportOrdersJson'])->name('reports.orders.json');
    Route::get('/reports/orders/export-txt', [ExportController::class, 'exportOrdersTxt'])->name('reports.orders.txt');
    Route::get('/reports/orders/print-pdf', [ExportController::class, 'printOrdersPdf'])->name('reports.orders.print');
    Route::get('/reports/labels/export-csv', [ExportController::class, 'exportLabelsCsv'])->name('reports.labels.csv');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // Meesho RPA & Order Processing
    Route::get('/meesho', [MeeshoController::class, 'index'])->name('meesho.index');
    Route::post('/meesho/connect', [MeeshoController::class, 'connect'])->name('meesho.connect');
    Route::post('/meesho/process-order/{order}', [MeeshoController::class, 'processOrder'])->name('meesho.process-order');
    Route::post('/meesho/logout', [MeeshoController::class, 'logout'])->name('meesho.logout');
});
