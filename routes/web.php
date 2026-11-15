<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AvatarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\QuestionController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/reports/answers/{question?}', [DashboardController::class, 'answers'])->name('reports.answers');
    Route::resource('players', PlayerController::class)->except('show');
    Route::resource('questions', QuestionController::class)->except('show');
    Route::get('/history', [HistoryController::class, 'index'])->name('history.index');
    Route::view('/profile', 'profile')->name('profile.edit');
    Route::get('/profile/avatar', [AvatarController::class, 'show'])->name('avatar.show');
    Route::post('/profile/avatar', [AvatarController::class, 'update'])->name('avatar.update');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});
