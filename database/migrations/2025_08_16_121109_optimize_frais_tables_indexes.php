<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Créer d'abord les nouvelles tables
        $this->createFraisConfigurationsTable();
        $this->createFraisOptionsTable();
        
        // Ensuite optimiser les tables existantes
        $this->optimizeExistingTables();
    }

    /**
     * Créer la table de configuration unifiée
     */
    private function createFraisConfigurationsTable(): void
    {
        if (!Schema::hasTable('esbtp_frais_configurations')) {
            Schema::create('esbtp_frais_configurations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('frais_category_id')->constrained('esbtp_frais_categories')->onDelete('cascade');
                $table->foreignId('filiere_id')->constrained('esbtp_filieres')->onDelete('cascade');
                $table->foreignId('niveau_id')->constrained('esbtp_niveau_etudes')->onDelete('cascade');
                $table->foreignId('annee_universitaire_id')->nullable()->constrained('esbtp_annee_universitaires')->onDelete('cascade');
                
                // Montants et conditions
                $table->decimal('amount', 12, 2);
                $table->integer('payment_deadline_days')->default(30);
                $table->boolean('installments_allowed')->default(false);
                $table->integer('max_installments')->default(1);
                $table->decimal('min_installment_amount', 12, 2)->nullable();
                
                // Frais supplémentaires
                $table->decimal('late_fee_percentage', 5, 2)->default(0);
                $table->decimal('late_fee_amount', 12, 2)->default(0);
                $table->decimal('early_payment_discount', 5, 2)->default(0);
                
                // Options de remise
                $table->boolean('sibling_discount_enabled')->default(false);
                $table->json('bulk_discount_tiers')->nullable();
                $table->json('seasonal_adjustments')->nullable();
                $table->json('special_conditions')->nullable();
                
                // Métadonnées
                $table->boolean('is_active')->default(true);
                $table->date('effective_date');
                $table->date('expiry_date')->nullable();
                $table->foreignId('created_by')->constrained('users');
                $table->text('notes')->nullable();
                
                $table->timestamps();
                $table->softDeletes();
                
                // Index optimisés pour les requêtes fréquentes
                $table->index(['frais_category_id', 'filiere_id', 'niveau_id', 'is_active'], 'idx_config_lookup');
                $table->index(['is_active', 'effective_date', 'expiry_date'], 'idx_config_validity');
                $table->index(['filiere_id', 'niveau_id'], 'idx_config_class');
                
                // Contrainte d'unicité pour éviter les doublons
                $table->unique([
                    'frais_category_id', 
                    'filiere_id', 
                    'niveau_id', 
                    'annee_universitaire_id'
                ], 'unq_config_class_category');
            });
        }
    }

    /**
     * Créer la table des options de frais
     */
    private function createFraisOptionsTable(): void
    {
        if (!Schema::hasTable('esbtp_frais_options')) {
            Schema::create('esbtp_frais_options', function (Blueprint $table) {
                $table->id();
                $table->foreignId('configuration_id')->nullable()->constrained('esbtp_frais_configurations')->onDelete('cascade');
                
                // Détails de l'option
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('additional_amount', 12, 2)->default(0);
                
                // Disponibilité
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->datetime('available_from')->nullable();
                $table->datetime('available_to')->nullable();
                
                // Conditions d'éligibilité
                $table->json('eligibility_conditions')->nullable();
                $table->integer('max_selections')->nullable();
                $table->integer('sort_order')->default(0);
                
                $table->timestamps();
                $table->softDeletes();
                
                // Index pour les requêtes fréquentes
                $table->index(['configuration_id', 'is_active', 'sort_order'], 'idx_options_config');
                $table->index(['is_default', 'is_active'], 'idx_options_default');
                $table->index(['available_from', 'available_to', 'is_active'], 'idx_options_availability');
            });
        }
    }

    /**
     * Optimiser les tables existantes avec des index
     */
    private function optimizeExistingTables(): void
    {
        // Optimiser esbtp_frais_categories
        if (Schema::hasTable('esbtp_frais_categories')) {
            Schema::table('esbtp_frais_categories', function (Blueprint $table) {
                $this->addIndexIfNotExists($table, 'esbtp_frais_categories', 
                    ['is_active', 'category_type', 'sort_order'], 'idx_categories_lookup');
                $this->addIndexIfNotExists($table, 'esbtp_frais_categories', 
                    ['is_mandatory', 'is_active'], 'idx_categories_mandatory');
                $this->addIndexIfNotExists($table, 'esbtp_frais_categories', 
                    'code', 'idx_categories_code');
            });
        }

        // Optimiser esbtp_frais_rules si elle existe
        if (Schema::hasTable('esbtp_frais_rules')) {
            Schema::table('esbtp_frais_rules', function (Blueprint $table) {
                $this->addIndexIfNotExists($table, 'esbtp_frais_rules', 
                    ['frais_category_id', 'filiere_id', 'niveau_id', 'is_active'], 'idx_rules_lookup');
                $this->addIndexIfNotExists($table, 'esbtp_frais_rules', 
                    ['is_active', 'effective_date'], 'idx_rules_active');
            });
        }

        // Optimiser esbtp_frais_variants si elle existe
        if (Schema::hasTable('esbtp_frais_variants')) {
            Schema::table('esbtp_frais_variants', function (Blueprint $table) {
                $this->addIndexIfNotExists($table, 'esbtp_frais_variants', 
                    ['frais_category_id', 'is_active', 'sort_order'], 'idx_variants_category');
                $this->addIndexIfNotExists($table, 'esbtp_frais_variants', 
                    ['is_default', 'is_active'], 'idx_variants_default');
            });
        }

        // Optimiser esbtp_frais_subscriptions si elle existe
        if (Schema::hasTable('esbtp_frais_subscriptions')) {
            Schema::table('esbtp_frais_subscriptions', function (Blueprint $table) {
                $this->addIndexIfNotExists($table, 'esbtp_frais_subscriptions', 
                    ['inscription_id', 'frais_category_id', 'is_active'], 'idx_subscriptions_lookup');
                $this->addIndexIfNotExists($table, 'esbtp_frais_subscriptions', 
                    ['subscribed_at', 'is_active'], 'idx_subscriptions_temporal');
            });
        }
    }

    /**
     * Ajouter un index seulement s'il n'existe pas déjà
     */
    private function addIndexIfNotExists(Blueprint $table, string $tableName, $columns, string $indexName): void
    {
        if (!$this->indexExists($tableName, $indexName)) {
            $table->index($columns, $indexName);
        }
    }

    /**
     * Vérifier si un index existe
     */
    private function indexExists(string $tableName, string $indexName): bool
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM {$tableName} WHERE Key_name = ?", [$indexName]);
            return count($indexes) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('esbtp_frais_options');
        Schema::dropIfExists('esbtp_frais_configurations');
        
        // Les index seront supprimés automatiquement si les tables sont supprimées
        // Pour une migration réversible complète, il faudrait garder la trace des index créés
    }
};