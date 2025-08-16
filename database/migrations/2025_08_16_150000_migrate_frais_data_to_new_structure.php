<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrer les règles vers les configurations
        $this->migrateRulesToConfigurations();
        
        // Migrer les variants vers les options
        $this->migrateVariantsToOptions();
        
        // Mettre à jour les références dans les souscriptions
        $this->updateSubscriptionReferences();
        
        Log::info('Migration des données de frais terminée avec succès');
    }

    /**
     * Migrer les règles de frais vers les configurations
     */
    private function migrateRulesToConfigurations(): void
    {
        if (!Schema::hasTable('esbtp_frais_rules')) {
            Log::info('Table esbtp_frais_rules non trouvée, migration ignorée');
            return;
        }

        $rules = DB::table('esbtp_frais_rules')
            ->where('is_active', true)
            ->orderBy('created_at')
            ->get();

        Log::info("Migration de {$rules->count()} règles vers les configurations");

        foreach ($rules as $rule) {
            // Vérifier si une configuration existe déjà
            $existing = DB::table('esbtp_frais_configurations')
                ->where('frais_category_id', $rule->frais_category_id)
                ->where('filiere_id', $rule->filiere_id)
                ->where('niveau_id', $rule->niveau_id)
                ->where('annee_universitaire_id', $rule->annee_universitaire_id)
                ->first();

            if (!$existing) {
                DB::table('esbtp_frais_configurations')->insert([
                    'frais_category_id' => $rule->frais_category_id,
                    'filiere_id' => $rule->filiere_id,
                    'niveau_id' => $rule->niveau_id,
                    'annee_universitaire_id' => $rule->annee_universitaire_id,
                    'amount' => $rule->amount ?? 0,
                    'payment_deadline_days' => $rule->payment_deadline_days ?? 30,
                    'installments_allowed' => $rule->installments_allowed ?? false,
                    'max_installments' => $rule->max_installments ?? 1,
                    'min_installment_amount' => $rule->min_installment_amount,
                    'late_fee_percentage' => $rule->late_fee_percentage ?? 0,
                    'late_fee_amount' => $rule->late_fee_amount ?? 0,
                    'early_payment_discount' => 0,
                    'sibling_discount_enabled' => false,
                    'bulk_discount_tiers' => null,
                    'seasonal_adjustments' => null,
                    'special_conditions' => null,
                    'is_active' => $rule->is_active ?? true,
                    'effective_date' => $rule->effective_date ?? now(),
                    'expiry_date' => $rule->expiry_date,
                    'created_by' => 1, // Utilisateur système
                    'notes' => 'Migré depuis esbtp_frais_rules (ID: ' . $rule->id . ')',
                    'created_at' => $rule->created_at ?? now(),
                    'updated_at' => $rule->updated_at ?? now(),
                ]);
            }
        }
    }

    /**
     * Migrer les variants vers les options
     */
    private function migrateVariantsToOptions(): void
    {
        if (!Schema::hasTable('esbtp_frais_variants')) {
            Log::info('Table esbtp_frais_variants non trouvée, migration ignorée');
            return;
        }

        $variants = DB::table('esbtp_frais_variants')
            ->where('is_active', true)
            ->orderBy('frais_category_id')
            ->orderBy('sort_order')
            ->get();

        Log::info("Migration de {$variants->count()} variants vers les options");

        foreach ($variants as $variant) {
            // Pour les variants, nous créons des options globales (configuration_id = null)
            // car les variants étaient liés aux catégories, pas aux configurations spécifiques
            
            DB::table('esbtp_frais_options')->insert([
                'configuration_id' => null, // Option globale
                'name' => $variant->name,
                'description' => $variant->description,
                'additional_amount' => max(0, ($variant->amount ?? 0) - $this->getCategoryDefaultAmount($variant->frais_category_id)),
                'is_default' => $variant->is_default ?? false,
                'is_active' => $variant->is_active ?? true,
                'available_from' => now(),
                'available_to' => null,
                'eligibility_conditions' => json_encode([
                    'migrated_from_variant' => true,
                    'original_variant_id' => $variant->id,
                    'original_category_id' => $variant->frais_category_id
                ]),
                'max_selections' => 1,
                'sort_order' => $variant->sort_order ?? 0,
                'created_at' => $variant->created_at ?? now(),
                'updated_at' => $variant->updated_at ?? now(),
            ]);
        }
    }

    /**
     * Obtenir le montant par défaut d'une catégorie
     */
    private function getCategoryDefaultAmount($categoryId): float
    {
        $category = DB::table('esbtp_frais_categories')
            ->where('id', $categoryId)
            ->first();
        
        return $category ? ($category->default_amount ?? 0) : 0;
    }

    /**
     * Mettre à jour les références dans les souscriptions
     */
    private function updateSubscriptionReferences(): void
    {
        if (!Schema::hasTable('esbtp_frais_subscriptions')) {
            Log::info('Table esbtp_frais_subscriptions non trouvée, mise à jour ignorée');
            return;
        }

        // Ajouter une colonne pour stocker l'ID de l'option sélectionnée
        if (!Schema::hasColumn('esbtp_frais_subscriptions', 'selected_option_id')) {
            Schema::table('esbtp_frais_subscriptions', function (Blueprint $table) {
                $table->unsignedBigInteger('selected_option_id')->nullable()->after('frais_category_id');
                $table->foreign('selected_option_id')->references('id')->on('esbtp_frais_options')->onDelete('set null');
            });
        }

        // Pour les souscriptions existantes, nous ne pouvons pas mapper automatiquement
        // vers des options spécifiques car nous n'avons pas assez d'informations
        // Les nouvelles souscriptions utiliseront cette colonne
        
        Log::info("Colonne selected_option_id ajoutée pour les futures souscriptions");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer les données migrées
        DB::table('esbtp_frais_configurations')
            ->where('notes', 'LIKE', 'Migré depuis esbtp_frais_rules%')
            ->delete();
        
        DB::table('esbtp_frais_options')
            ->whereJsonContains('eligibility_conditions->migrated_from_variant', true)
            ->delete();
        
        // Supprimer la colonne ajoutée si elle existe
        if (Schema::hasColumn('esbtp_frais_subscriptions', 'selected_option_id')) {
            Schema::table('esbtp_frais_subscriptions', function (Blueprint $table) {
                $table->dropForeign(['selected_option_id']);
                $table->dropColumn('selected_option_id');
            });
        }
        
        Log::info('Rollback de la migration des données de frais terminé');
    }
};