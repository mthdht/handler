<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\OrganisationController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    $organisations = Auth::user()->organisations;
    $organisations->load('etablissements');
    $etablissements = Auth::user()->etablissements();
    return Inertia::render('Dashboard', ['etablissements' => $etablissements,]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function(){
    Route::resource('organisations', OrganisationController::class);
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
