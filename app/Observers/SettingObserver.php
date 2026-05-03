<?php

namespace App\Observers;

use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use OwenIt\Auditing\Models\Audit;

/**
 * Observer custom pour le modèle Setting.
 *
 * Pourquoi pas le trait Auditable ?
 * Les settings sont des paires clé-valeur hétéroclites où c'est la combinaison
 * (key, value) qui fait sens dans l'audit, pas la diff de toutes les colonnes
 * de la table. On écrit directement dans la table `audits` avec le tag
 * "settings" pour faciliter le filtrage côté UI.
 */
class SettingObserver
{
    /**
     * Fragments de clé qui marquent un setting comme sensible. Quand un de ces
     * tokens apparaît dans `$setting->key`, la valeur n'est PAS écrite dans
     * l'audit (on la remplace par `[REDACTED]`) — l'audit log n'est pas un
     * canal sécurisé pour stocker des secrets.
     */
    private const SENSITIVE_KEY_FRAGMENTS = [
        'password',
        'secret',
        'token',
        'api_key',
        'apikey',
        'private_key',
        'smtp_pass',
    ];

    /**
     * Logge la création d'un setting.
     */
    public function created(Setting $setting): void
    {
        $this->writeAudit($setting, 'created', null, $setting->value);
    }

    /**
     * Logge le changement de valeur d'un setting.
     */
    public function updated(Setting $setting): void
    {
        // On ne logge que si la value a réellement changé. Les autres colonnes
        // (label, description, sort_order...) sont ignorées pour éviter
        // d'inonder l'audit avec du bruit non-fonctionnel.
        if (! $setting->wasChanged('value')) {
            return;
        }

        $this->writeAudit(
            $setting,
            'updated',
            $setting->getOriginal('value'),
            $setting->value
        );
    }

    /**
     * Logge la suppression d'un setting (rare — souvent désactivation via is_active).
     */
    public function deleted(Setting $setting): void
    {
        $this->writeAudit($setting, 'deleted', $setting->value, null);
    }

    /**
     * Écrit la ligne dans la table `audits`.
     *
     * On wrap dans un try/catch pour ne JAMAIS faire planter une opération
     * de save() à cause d'un audit qui foire (ex: table audits indispo).
     */
    private function writeAudit(Setting $setting, string $event, $oldValue, $newValue): void
    {
        $isSensitive = $this->isSensitive($setting->key ?? '');
        $payload = function ($value) use ($setting, $isSensitive) {
            return [
                'key' => $setting->key,
                'group' => $setting->group,
                'value' => $isSensitive ? '[REDACTED]' : $value,
            ];
        };

        $request = request();

        try {
            Audit::create([
                'user_type' => \App\Models\User::class,
                'user_id' => auth()->id(),
                'event' => $event,
                'auditable_type' => Setting::class,
                'auditable_id' => $setting->getKey(),
                'old_values' => json_encode($payload($oldValue)),
                'new_values' => json_encode($payload($newValue)),
                'url' => $request?->fullUrl(),
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'tags' => 'settings',
            ]);
        } catch (\Throwable $e) {
            // Ne jamais bloquer le flow métier si l'audit échoue.
            Log::warning('SettingObserver: échec écriture audit', [
                'setting_key' => $setting->key,
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Détecte si la clé contient un fragment sensible (case-insensitive).
     */
    private function isSensitive(string $key): bool
    {
        $lower = strtolower($key);
        foreach (self::SENSITIVE_KEY_FRAGMENTS as $fragment) {
            if (str_contains($lower, $fragment)) {
                return true;
            }
        }
        return false;
    }
}
