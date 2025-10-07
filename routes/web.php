<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    $organisations = Auth::user()->organisations;
    $organisations->load('etablissements');
    return Inertia::render('Dashboard', ['organisations' => $organisations,]);
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
