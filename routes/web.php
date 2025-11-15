<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShipmentController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\CustomerController;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Shipments Routes
    Route::resource('shipments', ShipmentController::class);
});

Route::middleware('auth')->group(function () {
    // ... routes yang sudah ada ...
    
    // Finance Routes
    Route::resource('finances', FinanceController::class);
});

Route::middleware('auth')->group(function () {
    // ... routes yang sudah ada ...
    
    // Customer Routes
    Route::resource('customers', CustomerController::class);
    
});



require __DIR__.'/auth.php';