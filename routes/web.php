<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
    });

Route::middleware(['auth'])->group(function () {
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('projects/upload', [ProjectController::class, 'create'])->name('projects.create');
    Route::get('projects/{project}/status', [ProjectController::class, 'status'])->name('projects.status');
    Route::post('projects/{project}/retry', [ProjectController::class, 'retry'])->name('projects.retry');
    Route::resource('projects', ProjectController::class)->only(['index', 'store', 'show', 'destroy']);
});

require __DIR__.'/settings.php';
