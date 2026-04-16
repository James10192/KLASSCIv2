<?php

namespace App\Services;

use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;

class FeeCalculationService
{
    /**
     * Construire la clé de configuration (catégorie + filière + niveau).
     * Utilisée pour grouper les ESBTPFraisConfiguration.
     */
    public static function buildConfigKey(int $categoryId, ?int $filiereId, ?int $niveauId): string
    {
        return "{$categoryId}_{$filiereId}_{$niveauId}";
    }

    /**
     * Calculer le montant attendu pour une inscription et une catégorie de frais.
     *
     * @param ESBTPFraisCategory $category
     * @param ESBTPInscription $inscription
     * @param \Illuminate\Support\Collection|null $configurations Pre-loaded configs grouped by configKey
     * @param \Illuminate\Support\Collection|null $subscriptions Pre-loaded subscriptions grouped by inscription_id
     */
    public static function getMontantAttendu(
        ESBTPFraisCategory $category,
        ESBTPInscription $inscription,
        $configurations = null,
        $subscriptions = null
    ): float {
        // Toujours prioriser la subscription individuelle (montant personnalisé pour cet étudiant)
        if ($subscriptions) {
            $inscSubs = $subscriptions->get($inscription->id, collect());
            $sub = $inscSubs->where('frais_category_id', $category->id)->first();
        } else {
            $sub = ESBTPFraisSubscription::where('inscription_id', $inscription->id)
                ->where('frais_category_id', $category->id)
                ->where('is_active', true)
                ->first();
        }

        if ($sub) {
            return (float) $sub->amount;
        }

        // Pas de subscription → fallback selon le type
        if ($category->is_mandatory) {
            if ($configurations) {
                $configKey = static::buildConfigKey($category->id, $inscription->filiere_id, $inscription->niveau_id);
                $config = $configurations->get($configKey, collect())->first();
            } else {
                $config = ESBTPFraisConfiguration::where('frais_category_id', $category->id)
                    ->where('filiere_id', $inscription->filiere_id)
                    ->where('niveau_id', $inscription->niveau_id)
                    ->first();
            }

            $status = $inscription->affectation_status ?? ESBTPInscription::DEFAULT_AFFECTATION_STATUS;

            return $config
                ? $config->getMontantByStatus($status)
                : $category->default_amount;
        }

        // Frais optionnel sans subscription → pas concerné
        return 0;
    }
}
