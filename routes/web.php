<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Projects\IssueController;
use App\Http\Controllers\Projects\ProjectController;
use App\Http\Controllers\Projects\ProjectKeyController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        Route::scopeBindings()->group(function () {
            Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
            Route::post('projects', [ProjectController::class, 'store'])->name('projects.store');
            Route::post('projects/{project}/keys', [ProjectKeyController::class, 'store'])->name('projects.keys.store');
            Route::patch('projects/{project}/keys/{projectKey}', [ProjectKeyController::class, 'update'])->name('projects.keys.update');

            Route::get('projects/{project}/issues', [IssueController::class, 'index'])->name('projects.issues.index');
            Route::get('projects/{project}/issues/{issue}', [IssueController::class, 'show'])->name('projects.issues.show');
            Route::post('projects/{project}/issues/{issue}/resolve', [IssueController::class, 'resolve'])->name('projects.issues.resolve');
        });
    });

Route::middleware(['auth'])->group(function () {
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';
