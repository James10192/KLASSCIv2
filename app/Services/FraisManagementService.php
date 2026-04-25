<?php

namespace App\Services;

use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPFraisOption;
use App\Models\ESBTPInscription;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use Illuminate\Support\Collection;

/**
 * Service de gestion des frais basé sur les meilleures pratiques 2025
 * 
 * Architecture séparée :
 * - Frais obligatoires par classe (inscription, scolarité)
 * - Services optionnels globaux (transport, cantine)
 */
class FraisManagementService
{
    /**
     * Obtient les options disponibles pour une catégorie de frais
     */
    public function getOptionsForCategory(ESBTPFraisCategory $category, ESBTPInscription $inscription = null): Collection
    {
        if ($category->is_mandatory) {
            return $this->getClassBasedOptions($category, $inscription);
        } else {
            return $this->getGlobalOptions($category);
        }
    }

    /**
     * Obtient les options disponibles pour une catégorie de frais à partir d'une classe
     * Utilisé lors de la création d'inscription
     */
    public function getOptionsForCategoryByClass(ESBTPFraisCategory $category, ESBTPClasse $classe): Collection
    {
        if ($category->is_mandatory) {
            return $this->getClassBasedOptionsByClass($category, $classe);
        } else {
            return $this->getGlobalOptions($category);
        }
    }

    /**
     * Obtient les options par classe pour les frais obligatoires
     */
    public function getClassBasedOptions(ESBTPFraisCategory $category, ESBTPInscription $inscription): Collection
    {
        if (!$inscription) {
            return collect();
        }

        // Trouver la configuration pour cette classe
        $configuration = ESBTPFraisConfiguration::where('frais_category_id', $category->id)
            ->where('filiere_id', $inscription->filiere_id)
            ->where('niveau_id', $inscription->niveau_id)
            ->where('annee_scolaire_id', $inscription->annee_scolaire_id)
            ->where('is_active', true)
            ->first();

        if (!$configuration) {
            return collect();
        }

        // Retourner les options liées à cette configuration
        return ESBTPFraisOption::classBased()
            ->where('configuration_id', $configuration->id)
            ->active()
            ->ordered()
            ->get();
    }

    /**
     * Obtient les options par classe pour les frais obligatoires à partir d'une classe
     * Version adaptée pour la création d'inscription
     */
    public function getClassBasedOptionsByClass(ESBTPFraisCategory $category, ESBTPClasse $classe): Collection
    {
        // Trouver la configuration pour cette classe
        $configuration = ESBTPFraisConfiguration::where('frais_category_id', $category->id)
            ->where('filiere_id', $classe->filiere_id)
            ->where('niveau_id', $classe->niveau_etude_id)
            ->where('annee_scolaire_id', $classe->annee_universitaire_id)
            ->where('is_active', true)
            ->first();

        if (!$configuration) {
            return collect();
        }

        // Retourner les options liées à cette configuration
        return ESBTPFraisOption::classBased()
            ->where('configuration_id', $configuration->id)
            ->active()
            ->ordered()
            ->get();
    }

    /**
     * Obtient les options globales pour les services optionnels
     */
    public function getGlobalOptions(ESBTPFraisCategory $category): Collection
    {
        // assigned() filtre les options orphelines (sans assignation) qui
        // sinon s'afficheraient à l'étudiant comme inutiles. L'écran admin
        // (optional-config.blade.php) lit $category->options direct sans
        // passer par ce service pour pouvoir afficher le badge "Aucune
        // assignation" et permettre la correction.
        return ESBTPFraisOption::global()
            ->forFraisCategory($category->id)
            ->active()
            ->assigned()
            ->ordered()
            ->get();
    }

    /**
     * Vérifie si une catégorie de frais nécessite une configuration par classe
     */
    public function requiresClassConfiguration(ESBTPFraisCategory $category): bool
    {
        return $category->is_mandatory;
    }

    /**
     * Obtient le statut de configuration pour une catégorie
     */
    public function getConfigurationStatus(ESBTPFraisCategory $category): array
    {
        if ($category->is_mandatory) {
            // Frais obligatoires - compter les configurations par classe
            $totalClasses = $this->getTotalClassCount();
            $configuredClasses = ESBTPFraisConfiguration::where('frais_category_id', $category->id)
                ->where('is_active', true)
                ->count();

            return [
                'type' => 'class_based',
                'total_classes' => $totalClasses,
                'configured_classes' => $configuredClasses,
                'completion_percentage' => $totalClasses > 0 ? round(($configuredClasses / $totalClasses) * 100, 1) : 0,
                'is_complete' => $configuredClasses >= $totalClasses,
                'message' => $configuredClasses > 0 
                    ? "Configuré pour {$configuredClasses}/{$totalClasses} classes"
                    : "Aucune configuration définie"
            ];
        } else {
            // Services optionnels - compter toutes les options (comme page SHOW)
            $optionsCount = $category->options()->active()->count();

            return [
                'type' => 'global',
                'options_count' => $optionsCount,
                'is_configured' => $optionsCount > 0,
                'message' => $optionsCount > 0 
                    ? "{$optionsCount} option(s) disponible(s)"
                    : "Aucune option configurée"
            ];
        }
    }

    /**
     * Crée une option globale pour un service optionnel
     */
    public function createGlobalOption(ESBTPFraisCategory $category, array $data): ESBTPFraisOption
    {
        if ($category->is_mandatory) {
            throw new \InvalidArgumentException('Les options globales ne peuvent être créées que pour les services optionnels');
        }

        return ESBTPFraisOption::create(array_merge($data, [
            'frais_category_id' => $category->id,
            'configuration_id' => null,
            'option_type' => 'global'
        ]));
    }

    /**
     * Crée une option liée à une classe pour un frais obligatoire
     */
    public function createClassBasedOption(ESBTPFraisConfiguration $configuration, array $data): ESBTPFraisOption
    {
        if (!$configuration->fraisCategory->is_mandatory) {
            throw new \InvalidArgumentException('Les options par classe ne peuvent être créées que pour les frais obligatoires');
        }

        return ESBTPFraisOption::create(array_merge($data, [
            'frais_category_id' => $configuration->frais_category_id,
            'configuration_id' => $configuration->id,
            'option_type' => 'class_based'
        ]));
    }

    /**
     * Obtient les statistiques pour le dashboard
     */
    public function getDashboardStats(): array
    {
        $mandatoryCategories = ESBTPFraisCategory::where('is_mandatory', true)->count();
        $optionalCategories = ESBTPFraisCategory::where('is_mandatory', false)->count();
        
        $totalConfigurations = ESBTPFraisConfiguration::where('is_active', true)->count();
        $globalOptions = ESBTPFraisOption::global()->active()->count();
        $classOptions = ESBTPFraisOption::classBased()->active()->count();

        return [
            'categories' => [
                'mandatory' => $mandatoryCategories,
                'optional' => $optionalCategories,
                'total' => $mandatoryCategories + $optionalCategories
            ],
            'configurations' => [
                'class_based' => $totalConfigurations,
                'completion_rate' => $this->getOverallConfigurationCompletion()
            ],
            'options' => [
                'global' => $globalOptions,
                'class_based' => $classOptions,
                'total' => $globalOptions + $classOptions
            ]
        ];
    }

    /**
     * Obtient le taux de completion global des configurations
     */
    private function getOverallConfigurationCompletion(): float
    {
        $mandatoryCategories = ESBTPFraisCategory::where('is_mandatory', true)->get();
        $totalRequired = $mandatoryCategories->count() * $this->getTotalClassCount();
        
        if ($totalRequired === 0) {
            return 100.0;
        }

        $totalConfigured = ESBTPFraisConfiguration::where('is_active', true)->count();
        
        return round(($totalConfigured / $totalRequired) * 100, 1);
    }

    /**
     * Obtient le nombre total de classes (filière × niveau)
     */
    private function getTotalClassCount(): int
    {
        // Compter le nombre de combinaisons théoriques possibles : filières actives × niveaux actifs
        $filieres = \App\Models\ESBTPFiliere::where('is_active', true)->count();
        $niveaux = \App\Models\ESBTPNiveauEtude::where('is_active', true)->count();
        
        return $filieres * $niveaux;
    }

    /**
     * Valide qu'une catégorie peut avoir des options du type spécifié
     */
    public function validateOptionType(ESBTPFraisCategory $category, string $optionType): bool
    {
        if ($category->is_mandatory && $optionType !== 'class_based') {
            return false;
        }

        if (!$category->is_mandatory && $optionType !== 'global') {
            return false;
        }

        return true;
    }

    /**
     * Migre les anciennes données vers la nouvelle architecture
     */
    public function migrateOldVariants(): array
    {
        $results = [
            'migrated' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        // Cette méthode peut être utilisée pour migrer les anciennes données
        // si nécessaire

        return $results;
    }
}