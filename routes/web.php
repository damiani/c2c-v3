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
    Route::view('transactions', 'pages.placeholder', [
        'title' => 'Transactions',
        'description' => 'Transaction workspaces, lifecycle status, and active deal files will land here in Phase 6.',
    ])->name('transactions.index');
    Route::view('documents', 'pages.placeholder', [
        'title' => 'Documents',
        'description' => 'Single-upload document review, approval status, and signing workflows will land here in Phases 4 and 7.',
    ])->name('documents.index');
    Route::view('forms', 'pages.placeholder', [
        'title' => 'Forms',
        'description' => 'Searchable MLS and tenant form libraries will land here in Phase 9.',
    ])->name('forms.index');
    Route::view('contacts', 'pages.placeholder', [
        'title' => 'Contacts',
        'description' => 'CRM-capable contacts, dropdown autofill, and team sharing will land here in Phase 10.',
    ])->name('contacts.index');
    Route::view('teams', 'pages.placeholder', [
        'title' => 'Teams',
        'description' => 'Brokerage team management, role assignment, and aggregate reporting will land here in Phase 10.',
    ])->name('teams.index');
    Route::view('reports', 'pages.placeholder', [
        'title' => 'Reports',
        'description' => 'Brokerage, back-office, commission, and transaction volume dashboards will land in later phases.',
    ])->name('reports.index');
});

require __DIR__.'/settings.php';
