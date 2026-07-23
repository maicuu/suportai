<?php

use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

// Abertura pública de ticket (o "ticket chega") — tenant vem do slug na URL.
Route::post('/t/{tenant:slug}/tickets', [TicketController::class, 'store'])
    ->name('public.tickets.store');

// Área do agente (autenticado).
Route::middleware(['auth'])->group(function () {
    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/reply', [TicketController::class, 'reply'])->name('tickets.reply');
});

require __DIR__.'/settings.php';
