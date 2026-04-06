<?php

namespace App\Livewire;

use App\Enums\IntegrationStatus;
use App\Enums\IntegrationType;
use App\Providers\TaskProviders\JiraProvider;
use LivewireUI\Modal\ModalComponent;

class JiraConfigModal extends ModalComponent
{
    public string $domain = '';

    public string $email = '';

    public string $apiToken = '';

    public ?string $error = null;

    public bool $isConnected = false;

    public function mount(): void
    {
        $integration = auth()->user()->integrations()
            ->where('type', IntegrationType::Jira)
            ->first();

        if ($integration && $integration->status === IntegrationStatus::Connected) {
            $this->isConnected = true;
            $this->domain = $integration->configuration['domain'] ?? '';
            $this->email = $integration->configuration['email'] ?? '';
            $this->apiToken = $integration->configuration['api_token'] ?? '';
        }
    }

    public function connect(): void
    {
        $this->validate([
            'domain' => 'required|url',
            'email' => 'required|email',
            'apiToken' => 'required|string|min:10',
        ]);

        $this->error = null;

        $domain = rtrim($this->domain, '/');

        if (! JiraProvider::validateCredentials($domain, $this->email, $this->apiToken)) {
            $this->error = 'Invalid credentials. Could not connect to Jira.';

            return;
        }

        auth()->user()->integrations()->updateOrCreate(
            ['type' => IntegrationType::Jira],
            [
                'status' => IntegrationStatus::Connected,
                'connected_at' => now(),
                'configuration' => [
                    'domain' => $domain,
                    'email' => $this->email,
                    'api_token' => $this->apiToken,
                ],
            ]
        );

        $this->isConnected = true;
        $this->dispatch('integration-updated');
        $this->forceClose()->closeModal();
    }

    public function disconnect(): void
    {
        auth()->user()->integrations()
            ->where('type', IntegrationType::Jira)
            ->update([
                'status' => IntegrationStatus::Disconnected,
                'configuration' => null,
            ]);

        $this->isConnected = false;
        $this->domain = '';
        $this->email = '';
        $this->apiToken = '';
        $this->dispatch('integration-updated');
        $this->forceClose()->closeModal();
    }

    public static function modalMaxWidth(): string
    {
        return 'md';
    }

    public function render()
    {
        return view('livewire.jira-config-modal');
    }
}
