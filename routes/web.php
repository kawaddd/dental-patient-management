<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('dashboard'));

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');

    Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations.index');

    Route::get('/import', [ImportController::class, 'index'])->name('import.index');
    Route::post('/import/customers', [ImportController::class, 'importCustomers'])->name('import.customers');
    Route::post('/import/reservations', [ImportController::class, 'importReservations'])->name('import.reservations');
    Route::get('/import/history', [ImportController::class, 'history'])->name('import.history');
    Route::get('/import/history/{importJob}/errors', [ImportController::class, 'errors'])->name('import.errors');
    Route::delete('/import/history/{importJob}', [ImportController::class, 'destroy'])->name('import.destroy');

    Route::resource('users', UserController::class)->except(['show']);
});

require __DIR__.'/auth.php';
