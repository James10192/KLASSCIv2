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
        // On ne logge que si la value a réellement changé.
        // Les autres colonnes (label, description, sort_order...) sont ignorées
        // pour éviter d'inonder l'audit avec du bruit non-fonctionnel.
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
        try {
            Audit::create([
                'user_type' => \App\Models\User::class,
                'user_id' => auth()->id(),
                'event' => $event,
                'auditable_type' => Setting::class,
                'auditable_id' => $setting->getKey(),
                'old_values' => json_encode([
                    'key' => $setting->key,
                    'group' => $setting->group,
                    'value' => $oldValue,
                ]),
                'new_values' => json_encode([
                    'key' => $setting->key,
                    'group' => $setting->group,
                    'value' => $newValue,
                ]),
                'url' => request() ? request()->fullUrl() : null,
                'ip_address' => request() ? request()->ip() : null,
                'user_agent' => request() ? request()->userAgent() : null,
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
}
