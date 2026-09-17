<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\NotesController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\SlotController;
use App\Http\Controllers\StatsController;
use Illuminate\Support\Facades\Route;

Route::get('/',       fn() => redirect()->route('login'));
Route::get('/login',  [AuthController::class, 'loginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout',[AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // AJAX - harus sebelum {booking} wildcard
    Route::get('/bookings/slots/{dosenId}', [BookingController::class, 'getSlots'])->name('bookings.slots');
    Route::get('/bookings/check-conflict',  [BookingController::class, 'checkConflict'])->name('bookings.check');

    // Bookings
    Route::get('/bookings',          [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/create',   [BookingController::class, 'create'])->name('bookings.create');
    Route::post('/bookings',         [BookingController::class, 'store'])->name('bookings.store');
    Route::post('/bookings/queue',   [BookingController::class, 'joinQueue'])->name('bookings.queue');
    Route::get('/bookings/{booking}',[BookingController::class, 'show'])->name('bookings.show');
    Route::patch('/bookings/{booking}/confirm',  [BookingController::class, 'confirm'])->name('bookings.confirm');
    Route::patch('/bookings/{booking}/complete', [BookingController::class, 'complete'])->name('bookings.complete');
    Route::patch('/bookings/{booking}/cancel',   [BookingController::class, 'cancel'])->name('bookings.cancel');

    // History
    Route::get('/history', [HistoryController::class, 'index'])->name('history.index');

    // Queue
    Route::get('/queue',                        [QueueController::class, 'index'])->name('queue.index');
    Route::delete('/queue/{waitingList}',        [QueueController::class, 'leave'])->name('queue.leave');
    Route::patch('/queue/{waitingList}/promote', [QueueController::class, 'promote'])->name('queue.promote');

    // Notes
    Route::get('/notes',              [NotesController::class, 'index'])->name('notes.index');
    Route::post('/notes/{bookingId}', [NotesController::class, 'store'])->name('notes.store');

    // Stats
    Route::get('/stats', [StatsController::class, 'index'])->name('stats.index');

    // Slots
    Route::get('/slots',           [SlotController::class, 'index'])->name('slots.index');
    Route::post('/slots',          [SlotController::class, 'store'])->name('slots.store');
    Route::delete('/slots/{slot}', [SlotController::class, 'destroy'])->name('slots.destroy');
});
