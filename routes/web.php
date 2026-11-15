<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AvatarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardChartController;
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
    Route::resource('charts', DashboardChartController::class)->except(['index', 'show']);
    Route::get('/reports/answers/{question?}', [DashboardController::class, 'answers'])->name('reports.answers');
    Route::get('/players/{player}/pdf', [PlayerController::class, 'pdf'])->name('players.pdf');
    Route::get('/players/{player}/docx', [PlayerController::class, 'docx'])->name('players.docx');
    Route::resource('players', PlayerController::class);
    Route::put('/questions/blocks/order', [QuestionController::class, 'reorderBlocks'])->name('questions.blocks.order');
    Route::put('/questions/blocks/{block}/order', [QuestionController::class, 'reorderQuestions'])->name('questions.blocks.questions');
    Route::resource('questions', QuestionController::class)->except('show');
    Route::get('/history', [HistoryController::class, 'index'])->name('history.index');
    Route::view('/profile', 'profile')->name('profile.edit');
    Route::get('/profile/avatar', [AvatarController::class, 'show'])->name('avatar.show');
    Route::post('/profile/avatar', [AvatarController::class, 'update'])->name('avatar.update');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});
