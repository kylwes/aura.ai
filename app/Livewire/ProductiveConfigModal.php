<?php

namespace App\Livewire;

use App\Enums\IntegrationStatus;
use App\Enums\IntegrationType;
use App\Providers\TaskProviders\ProductiveProvider;
use LivewireUI\Modal\ModalComponent;

class ProductiveConfigModal extends ModalComponent
{
    public string $apiToken = '';

    public ?string $error = null;

    public bool $isConnected = false;

    public function mount(): void
    {
        $integration = auth()->user()->integrations()
            ->where('type', IntegrationType::Productive)
            ->first();

        if ($integration && $integration->status === IntegrationStatus::Connected) {
            $this->isConnected = true;
            $this->apiToken = $integration->configuration['api_token'] ?? '';
        }
    }

    public function connect(): void
    {
        $this->validate([
            'apiToken' => 'required|string|min:10',
        ]);

        $this->error = null;

        // Verify the token by fetching the organization
        $orgId = ProductiveProvider::fetchOrganizationId($this->apiToken);

        if (! $orgId) {
            $this->error = 'Invalid API token. Could not connect to Productive.';

            return;
        }

        auth()->user()->integrations()->updateOrCreate(
            ['type' => IntegrationType::Productive],
            [
                'status' => IntegrationStatus::Connected,
                'connected_at' => now(),
                'configuration' => [
                    'api_token' => $this->apiToken,
                    'organization_id' => $orgId,
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
            ->where('type', IntegrationType::Productive)
            ->update([
                'status' => IntegrationStatus::Disconnected,
                'configuration' => null,
            ]);

        $this->isConnected = false;
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
        return view('livewire.productive-config-modal');
    }
}
