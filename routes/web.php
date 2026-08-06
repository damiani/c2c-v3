<?php

use App\Http\Controllers\Auth\SocialiteController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('auth/{provider}/redirect', [SocialiteController::class, 'redirect'])
    ->name('sso.redirect');

Route::get('auth/{provider}/callback', [SocialiteController::class, 'callback'])
    ->name('sso.callback');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
