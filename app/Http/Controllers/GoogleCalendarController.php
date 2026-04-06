<?php

namespace App\Http\Controllers;

use App\Enums\IntegrationStatus;
use App\Enums\IntegrationType;
use App\Services\GoogleCalendarService;
use Illuminate\Http\RedirectResponse;

class GoogleCalendarController extends Controller
{
    public function __construct(
        private GoogleCalendarService $googleCalendarService,
    ) {}

    public function redirect(): RedirectResponse
    {
        return redirect()->away($this->googleCalendarService->getAuthUrl());
    }

    public function callback(): RedirectResponse
    {
        $code = request()->query('code');

        if (! $code) {
            return redirect()->route('settings')->with('error', 'Google Calendar authorization was cancelled.');
        }

        try {
            $tokens = $this->googleCalendarService->handleCallback($code);
        } catch (\Exception $e) {
            return redirect()->route('settings')->with('error', 'Failed to connect Google Calendar. Please try again.');
        }

        $user = auth()->user();

        $integration = $user->integrations()
            ->where('type', IntegrationType::GoogleCalendar)
            ->first();

        if ($integration) {
            $integration->update([
                'status' => IntegrationStatus::Connected,
                'configuration' => array_merge($integration->configuration ?? [], $tokens),
                'connected_at' => now(),
            ]);
        } else {
            $user->integrations()->create([
                'type' => IntegrationType::GoogleCalendar,
                'status' => IntegrationStatus::Connected,
                'configuration' => $tokens,
                'connected_at' => now(),
            ]);
        }

        return redirect()->route('settings')->with('message', 'Google Calendar connected successfully.');
    }
}
