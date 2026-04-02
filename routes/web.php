<?php

use App\Http\Controllers\GoogleCalendarController;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Auth\VerifyEmail;
use App\Livewire\Onboarding;
use App\Livewire\Pages\PlannerPage;
use App\Livewire\Pages\ProjectsPage;
use App\Livewire\Pages\TaskPage;
use App\Livewire\PlanSummary;
use App\Livewire\Profile;
use App\Livewire\Settings;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
    Route::get('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::get('/reset-password/{token}', ResetPassword::class)->name('password.reset');
});

Route::middleware('auth')->group(function () {
    Route::get('/verify-email', VerifyEmail::class)->name('verification.notice');
    Route::get('/onboarding', Onboarding::class)->name('onboarding');
    Route::get('/', PlannerPage::class)->name('planner');
    Route::get('/tasks', TaskPage::class)->name('tasks');
    Route::get('/projects', ProjectsPage::class)->name('projects');
    Route::get('/settings', Settings::class)->name('settings');
    Route::get('/plan-summary', PlanSummary::class)->name('plan-summary');
    Route::get('/profile', Profile::class)->name('profile');

    Route::get('/auth/google/redirect', [GoogleCalendarController::class, 'redirect'])->name('google.redirect');
    Route::get('/auth/google/callback', [GoogleCalendarController::class, 'callback'])->name('google.callback');

    Route::post('/logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/login');
    })->name('logout');
});
