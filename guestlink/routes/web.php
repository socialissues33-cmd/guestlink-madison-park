<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Guest\GuestController;
use App\Http\Controllers\Staff\DashboardController;
use App\Http\Controllers\Staff\FoodOrderController;
use App\Http\Controllers\Staff\RequestController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ─── Root ────────────────────────────────────────────────────────────────────
Route::get('/', [GuestController::class, 'rootRedirect']);

// ─── Guest Demo Quick Launcher & QR Test Scanner ─────────────────────────────
Route::get('/guest-demo', [GuestController::class, 'guestDemo'])->name('guest.demo');
Route::get('/test-qr',    [GuestController::class, 'testQr'])->name('guest.test-qr');

Route::redirect('/qr', '/test-qr');
Route::redirect('/room-201-qr', '/test-qr');
Route::redirect('/room-101-qr', '/test-qr');

// ─── Guest (QR-based, no login required, per-room rate-limited) ───────────────
Route::prefix('guest/{token}')->name('guest.')->middleware(['throttle:guest-read'])->group(function () {
    Route::get('/',              [GuestController::class, 'landing'])            ->name('landing');
    Route::get('/service',       [GuestController::class, 'serviceRequestForm']) ->name('service');
    Route::post('/service',      [GuestController::class, 'serviceRequestStore'])->name('service.store')->middleware('throttle:guest-write');
    Route::get('/maintenance',   [GuestController::class, 'maintenanceForm'])    ->name('maintenance');
    Route::post('/maintenance',  [GuestController::class, 'maintenanceStore'])   ->name('maintenance.store')->middleware('throttle:guest-write');
    Route::post('/maintenance/analyze-photo', [GuestController::class, 'analyzeMaintenancePhoto'])->name('maintenance.analyze')->middleware('throttle:guest-ai');
    Route::get('/food',          [GuestController::class, 'foodMenu'])           ->name('food');
    Route::post('/food/order',   [GuestController::class, 'foodOrderStore'])     ->name('food.order')->middleware('throttle:guest-write');
    Route::post('/orders/{ref}/cancel', [GuestController::class, 'cancelFoodOrder'])->name('food.cancel')->middleware('throttle:guest-write');
    Route::post('/orders/{ref}/confirm-received', [GuestController::class, 'confirmFoodOrderReceived'])->name('food.received')->middleware('throttle:guest-write');
    Route::post('/requests/{ref}/cancel', [GuestController::class, 'cancelServiceRequest'])->name('requests.cancel')->middleware('throttle:guest-write');
    Route::post('/rate',         [GuestController::class, 'submitRating'])       ->name('rate')->middleware('throttle:guest-write');
    Route::get('/events',        [GuestController::class, 'eventsPage'])         ->name('events');
    Route::post('/events',       [GuestController::class, 'eventsStore'])        ->name('events.store')->middleware('throttle:guest-write');
    Route::get('/track',         [GuestController::class, 'track'])              ->name('track');
    Route::get('/poll',          [GuestController::class, 'pollGuestStatus'])    ->name('poll');
    Route::get('/requests/{ref}/messages',  [GuestController::class, 'getTicketMessages'])->name('requests.messages');
    Route::post('/requests/{ref}/messages', [GuestController::class, 'sendTicketMessage'])->name('requests.messages.store')->middleware('throttle:guest-write');
    Route::post('/concierge-ask',           [GuestController::class, 'askAiConcierge'])->name('concierge.ask')->middleware('throttle:guest-ai');
    Route::post('/concierge-ticket',        [GuestController::class, 'createTicketFromConcierge'])->name('concierge.ticket')->middleware('throttle:guest-write');
});

// ─── Auth ─────────────────────────────────────────────────────────────────────
require __DIR__ . '/auth.php';

// ─── Staff (authenticated & active) ──────────────────────────────────────────
Route::middleware(['auth', \App\Http\Middleware\EnsureUserIsActive::class])->group(function () {

    // Dashboard & Real-time Live Notifications
    Route::get('/dashboard',          [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/notifications/poll', [DashboardController::class, 'pollNotifications'])->name('notifications.poll');
    Route::post('/account/preferences', [\App\Http\Controllers\Staff\UserPreferenceController::class, 'update'])
        ->name('account.preferences.update')
        ->middleware('throttle:staff-write');

    // Service & Maintenance Requests
    Route::prefix('requests')->name('requests.')->group(function () {
        Route::get('/',                              [RequestController::class, 'index'])                    ->name('index');
        Route::get('/{serviceRequest}',              [RequestController::class, 'show'])                     ->name('show');
        Route::post('/{serviceRequest}/status',      [RequestController::class, 'updateStatus'])             ->name('status')->middleware('throttle:staff-write');
        Route::post('/{serviceRequest}/feedback',    [RequestController::class, 'submitTechnicianFeedback']) ->name('feedback')->middleware('throttle:staff-write');
        Route::post('/{serviceRequest}/ai-review',   [RequestController::class, 'reviewAi'])                 ->name('ai-review')->middleware('throttle:staff-write');
        Route::get('/{serviceRequest}/messages',         [RequestController::class, 'getMessages'])         ->name('messages');
        Route::post('/{serviceRequest}/messages',        [RequestController::class, 'sendMessage'])         ->name('messages.store')->middleware('throttle:staff-write');
        Route::post('/{serviceRequest}/ai-draft',        [RequestController::class, 'draftAiReply'])        ->name('messages.ai-draft')->middleware('throttle:staff-write');
        Route::post('/{serviceRequest}/ai-auto-reply',   [RequestController::class, 'autoReplyAi'])         ->name('messages.ai-auto-reply')->middleware('throttle:staff-write');
        Route::post('/{serviceRequest}/toggle-bot',      [RequestController::class, 'toggleAutoReplyBot'])   ->name('toggle-bot')->middleware('throttle:staff-write');
        Route::delete('/{serviceRequest}/messages',      [RequestController::class, 'deleteConversation'])   ->name('messages.delete')->middleware('throttle:staff-write');
    });

    // Food Orders
    Route::prefix('food')->name('food.')->group(function () {
        Route::get('/orders',                [FoodOrderController::class, 'index'])        ->name('orders');
        Route::get('/orders/{foodOrder}',    [FoodOrderController::class, 'show'])         ->name('orders.show');
        Route::post('/orders/{foodOrder}/status', [FoodOrderController::class, 'updateStatus'])->name('orders.status')->middleware('throttle:staff-write');
        Route::get('/menu',                  [FoodOrderController::class, 'menuIndex'])    ->name('menu');
        Route::post('/menu',                 [FoodOrderController::class, 'menuStore'])    ->name('menu.store')->middleware('throttle:staff-write');
        Route::put('/menu/{foodItem}',       [FoodOrderController::class, 'menuUpdate'])   ->name('menu.update')->middleware('throttle:staff-write');
        Route::post('/menu/{foodItem}/toggle',[FoodOrderController::class, 'menuToggle'])  ->name('menu.toggle')->middleware('throttle:staff-write');
        Route::delete('/menu/{foodItem}',    [FoodOrderController::class, 'menuDestroy']) ->name('menu.destroy')->middleware('throttle:staff-write');
    });

    // Admin & Operational Management Routes
    Route::prefix('admin')->name('admin.')->group(function () {
        // Room & QR Code Operations (Accessible by Admin, Owner, and Front Desk for Check-In/Check-Out QR control)
        Route::middleware('role:admin|owner|front_desk')->group(function () {
            Route::get('/rooms',                    [AdminController::class, 'rooms'])          ->name('rooms');
            Route::post('/rooms/wifi/reset',        [AdminController::class, 'resetWifi'])      ->name('rooms.wifi.reset');
            Route::post('/rooms/qr-mode',           [AdminController::class, 'switchQrMode'])   ->name('rooms.qr.mode');
            Route::get('/rooms/qr/defense-sheet',   [AdminController::class, 'defenseQrSheet']) ->name('rooms.qr.sheet');
            Route::post('/rooms/{room}/toggle',     [AdminController::class, 'toggleRoom'])     ->name('rooms.toggle');
            Route::post('/rooms/{room}/qr',         [AdminController::class, 'regenerateQr'])  ->name('rooms.qr');
            Route::get('/rooms/{room}/qr/download', [AdminController::class, 'downloadQr'])    ->name('rooms.qr.download');
            Route::get('/rooms/{room}/folio',       [AdminController::class, 'roomFolio'])     ->name('rooms.folio');
        });

        // Executive Reports & Analytics (Accessible by Admin and Hotel Owner/Manager)
        Route::middleware('role:admin|owner')->group(function () {
            Route::get('/',               [AdminController::class, 'dashboard'])      ->name('dashboard');
            Route::post('/settings',      [AdminController::class, 'updateSettings']) ->name('settings.update');
            Route::get('/reports',        [AdminController::class, 'reports'])        ->name('reports');
            Route::get('/reports/export', [AdminController::class, 'exportReports'])  ->name('reports.export');
        });

        // System Administration CRUD (Admin Only)
        Route::middleware('role:admin')->group(function () {
            Route::get('/backup/database', [AdminController::class, 'downloadDatabaseBackup'])->name('backup.download');

            // Users Management CRUD
            Route::get('/users',                 [AdminController::class, 'users'])         ->name('users');
            Route::post('/users',                [AdminController::class, 'storeUser'])     ->name('users.store');
            Route::put('/users/{user}',          [AdminController::class, 'updateUser'])    ->name('users.update');
            Route::post('/users/{user}/toggle',  [AdminController::class, 'toggleUser'])    ->name('users.toggle');
            Route::delete('/users/{user}',       [AdminController::class, 'destroyUser'])   ->name('users.destroy');

            // Structural Room CRUD
            Route::post('/rooms',                [AdminController::class, 'storeRoom'])     ->name('rooms.store');
            Route::put('/rooms/{room}',          [AdminController::class, 'updateRoom'])    ->name('rooms.update');
            Route::delete('/rooms/{room}',       [AdminController::class, 'destroyRoom'])   ->name('rooms.destroy');

            // Categories Management CRUD
            Route::get('/categories',                    [AdminController::class, 'categories'])    ->name('categories');
            Route::post('/categories',                   [AdminController::class, 'storeCategory']) ->name('categories.store');
            Route::put('/categories/{category}',         [AdminController::class, 'updateCategory'])->name('categories.update');
            Route::post('/categories/{category}/toggle', [AdminController::class, 'toggleCategory'])->name('categories.toggle');
            Route::delete('/categories/{category}',      [AdminController::class, 'destroyCategory'])->name('categories.destroy');
        });
    });
});
