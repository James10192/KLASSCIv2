<?php

namespace App\Services\Sms;

use App\Domain\Notifications\Contracts\SmsProviderInterface;
use App\Helpers\SettingsHelper;
use Illuminate\Support\Facades\Log;

/**
 * Dispatcher SMS multi-providers (Phase 9 Plan v4).
 *
 * Sélectionne le provider actif via setting tenant `sms.provider`.
 * Cascade fallback : si provider primaire échoue, essaie le suivant.
 *
 * Ordre fallback par défaut : Orange → Beem → SMS.to.
 */
class SmsDispatcher
{
    /** @var array<string, SmsProviderInterface> */
    protected array $providers = [];

    public function register(string $name, SmsProviderInterface $provider): self
    {
        $this->providers[$name] = $provider;

        return $this;
    }

    /**
     * Envoie un SMS via le provider configuré, avec fallback cascade si échec.
     *
     * @return array{success: bool, provider?: string, message_id?: string, error?: string, cost_fcfa?: float}
     */
    public function send(string $phoneNumber, string $message): array
    {
        $cascade = $this->resolveCascade();

        foreach ($cascade as $providerName) {
            if (! isset($this->providers[$providerName])) {
                Log::warning('[sms-dispatcher] Provider non enregistré', ['name' => $providerName]);
                continue;
            }

            $provider = $this->providers[$providerName];

            if (! $provider->isAvailable()) {
                Log::info('[sms-dispatcher] Provider non disponible, fallback', ['name' => $providerName]);
                continue;
            }

            $result = $provider->send($phoneNumber, $message);

            if ($result['success'] ?? false) {
                return array_merge($result, ['provider' => $providerName]);
            }

            Log::warning('[sms-dispatcher] Provider échec, cascade vers suivant', [
                'name' => $providerName,
                'error' => $result['error'] ?? 'unknown',
            ]);
        }

        return [
            'success' => false,
            'error' => 'Tous les providers SMS ont échoué ou sont indisponibles',
            'attempted' => $cascade,
        ];
    }

    /**
     * Résout l'ordre de cascade depuis settings tenant.
     * Default : Orange → Beem → SMS.to.
     */
    protected function resolveCascade(): array
    {
        $primary = SettingsHelper::get('sms.provider', 'orange');
        $fallback = SettingsHelper::get('sms.fallback_providers', ['beem', 'smsto']);

        if (! is_array($fallback)) {
            $fallback = [];
        }

        return array_unique(array_merge([$primary], $fallback));
    }
}
