<?php

use App\Enums\IntegrationStatus;
use App\Enums\IntegrationType;
use App\Jobs\SyncGoogleCalendarJob;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    User::whereHas('integrations', function ($query) {
        $query->where('type', IntegrationType::GoogleCalendar)
            ->where('status', IntegrationStatus::Connected);
    })->each(function (User $user) {
        SyncGoogleCalendarJob::dispatch($user);
    });
})->everyFiveMinutes()->name('sync-google-calendars');
