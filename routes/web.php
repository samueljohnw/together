<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\ClosedItemController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItemMessageController;
use App\Http\Controllers\MessageAttachmentController;
use App\Http\Controllers\PushController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/', [ItemController::class, 'workspace'])->name('home');
    Route::get('/account', [AccountController::class, 'show'])->name('account');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/items', [ItemController::class, 'index'])->name('items.index');
    Route::post('/items', [ItemController::class, 'store'])->name('items.store');
    Route::get('/items/{item}', [ItemController::class, 'show'])->name('items.show');
    Route::post('/items/{item}/messages', [ItemMessageController::class, 'store'])->name('items.messages.store');

    Route::post('/closed-items/{item}', [ClosedItemController::class, 'store'])->name('closed-items.store');
    Route::delete('/closed-items/{item}', [ClosedItemController::class, 'destroy'])->name('closed-items.destroy');

    Route::get('/messages/{message}/attachment', [MessageAttachmentController::class, 'show'])->name('messages.attachment');
    Route::get('/messages/{message}/attachment/preview', [MessageAttachmentController::class, 'preview'])->name('messages.attachment.preview');

    Route::post('/push/subscriptions', [PushController::class, 'storeSubscription'])
        ->middleware('throttle:20,1')
        ->name('push.subscriptions.store');
    Route::delete('/push/subscriptions', [PushController::class, 'destroySubscription'])
        ->middleware('throttle:20,1')
        ->name('push.subscriptions.destroy');
});
