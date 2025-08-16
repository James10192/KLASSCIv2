<?php

namespace App\Services;

use App\Models\ESBTPFraisVariant;
use App\Models\ESBTPFraisOption;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPFraisCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service pour migrer les anciens variants vers le nouveau système d'options
 * et créer un backup des données
 */
class FraisVariantBackupService
{
    /**
     * Créer un backup de tous les variants existants
     */
    public function createBackup(): array
    {
        try {
            $variants = ESBTPFraisVariant::all();
            
            $backup = [
                'timestamp' => now()->toISOString(),
                'total_variants' => $variants->count(),
                'variants' => []
            ];
            
            foreach ($variants as $variant) {
                // Récupérer le nom de la catégorie si possible
                $categoryName = 'Unknown';
                try {
                    $category = ESBTPFraisCategory::find($variant->category_id);
                    $categoryName = $category ? $category->name : 'Unknown';
                } catch (\Exception $e) {
                    // Ignore si erreur
                }
                
                $backup['variants'][] = [
                    'id' => $variant->id,
                    'category_id' => $variant->category_id,
                    'category_name' => $categoryName,
                    'name' => $variant->name,
                    'description' => $variant->description,
                    'amount' => $variant->amount,
                    'additional_amount' => $variant->additional_amount ?? 0,
                    'is_default' => $variant->is_default ?? false,
                    'is_active' => $variant->is_active ?? true,
                    'sort_order' => $variant->sort_order ?? 0,
                    'created_at' => $variant->created_at,
                    'updated_at' => $variant->updated_at
                ];
            }
            
            // Sauvegarder dans un fichier
            $backupPath = storage_path('app/backups/frais_variants_' . now()->format('Y_m_d_H_i_s') . '.json');
            
            if (!is_dir(dirname($backupPath))) {
                mkdir(dirname($backupPath), 0755, true);
            }
            
            file_put_contents($backupPath, json_encode($backup, JSON_PRETTY_PRINT));
            
            Log::info('Backup des variants créé avec succès', [
                'path' => $backupPath,
                'count' => $variants->count()
            ]);
            
            return [
                'success' => true,
                'backup_path' => $backupPath,
                'variants_count' => $variants->count(),
                'message' => 'Backup créé avec succès'
            ];
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création du backup des variants', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Erreur lors de la création du backup: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Migrer les variants vers le nouveau système d'options
     */
    public function migrateToOptions(): array
    {
        try {
            DB::beginTransaction();
            
            $variants = ESBTPFraisVariant::all();
            $migratedCount = 0;
            $errors = [];
            
            foreach ($variants as $variant) {
                try {
                    // Trouver ou créer une configuration pour cette catégorie
                    $configuration = $this->findOrCreateConfiguration($variant);
                    
                    if ($configuration) {
                        // Créer l'option correspondante
                        ESBTPFraisOption::create([
                            'configuration_id' => $configuration->id,
                            'name' => $variant->name,
                            'description' => $variant->description,
                            'additional_amount' => $variant->additional_amount ?? 0,
                            'is_default' => $variant->is_default ?? false,
                            'is_active' => $variant->is_active ?? true,
                            'available_from' => now(),
                            'sort_order' => $variant->sort_order ?? 0
                        ]);
                        
                        $migratedCount++;
                    } else {
                        $errors[] = "Impossible de créer une configuration pour le variant {$variant->name}";
                    }
                    
                } catch (\Exception $e) {
                    $errors[] = "Erreur lors de la migration du variant {$variant->name}: " . $e->getMessage();
                }
            }
            
            DB::commit();
            
            Log::info('Migration des variants vers les options terminée', [
                'migrated' => $migratedCount,
                'total' => $variants->count(),
                'errors' => count($errors)
            ]);
            
            return [
                'success' => true,
                'migrated_count' => $migratedCount,
                'total_variants' => $variants->count(),
                'errors' => $errors,
                'message' => "Migration terminée: {$migratedCount}/{$variants->count()} variants migrés"
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erreur lors de la migration des variants', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Erreur lors de la migration: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Trouver ou créer une configuration pour un variant
     */
    private function findOrCreateConfiguration($variant): ?ESBTPFraisConfiguration
    {
        try {
            // Essayer de trouver une configuration existante pour cette catégorie
            $configuration = ESBTPFraisConfiguration::where('frais_category_id', $variant->category_id)
                ->first();
            
            if ($configuration) {
                return $configuration;
            }
            
            // Si aucune configuration n'existe, en créer une générique
            $category = ESBTPFraisCategory::find($variant->category_id);
            if (!$category) {
                return null;
            }
            
            // Prendre la première filière et niveau disponibles
            $filiere = \App\Models\ESBTPFiliere::where('is_active', true)->first();
            $niveau = \App\Models\ESBTPNiveauEtude::where('is_active', true)->first();
            
            if (!$filiere || !$niveau) {
                return null;
            }
            
            return ESBTPFraisConfiguration::create([
                'frais_category_id' => $variant->category_id,
                'filiere_id' => $filiere->id,
                'niveau_id' => $niveau->id,
                'amount' => $variant->amount ?? $category->default_amount,
                'payment_deadline_days' => $category->payment_deadline_days,
                'is_active' => true,
                'is_valid' => true,
                'effective_date' => now()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de configuration pour variant', [
                'variant_id' => $variant->id,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }
    
    /**
     * Vérifier la compatibilité avant migration
     */
    public function checkCompatibility(): array
    {
        $issues = [];
        
        // Vérifier si la table variants existe
        if (!DB::getSchemaBuilder()->hasTable('esbtp_frais_variants')) {
            $issues[] = 'Table esbtp_frais_variants non trouvée';
        }
        
        // Vérifier si des variants existent
        if (ESBTPFraisVariant::count() === 0) {
            $issues[] = 'Aucun variant à migrer';
        }
        
        // Vérifier si les filières et niveaux existent
        if (\App\Models\ESBTPFiliere::where('is_active', true)->count() === 0) {
            $issues[] = 'Aucune filière active trouvée';
        }
        
        if (\App\Models\ESBTPNiveauEtude::where('is_active', true)->count() === 0) {
            $issues[] = 'Aucun niveau d\'étude actif trouvé';
        }
        
        return [
            'compatible' => count($issues) === 0,
            'issues' => $issues,
            'variants_count' => ESBTPFraisVariant::count()
        ];
    }
    
    /**
     * Restaurer depuis un backup
     */
    public function restoreFromBackup(string $backupPath): array
    {
        try {
            if (!file_exists($backupPath)) {
                return [
                    'success' => false,
                    'message' => 'Fichier de backup non trouvé'
                ];
            }
            
            $backup = json_decode(file_get_contents($backupPath), true);
            
            if (!$backup || !isset($backup['variants'])) {
                return [
                    'success' => false,
                    'message' => 'Format de backup invalide'
                ];
            }
            
            DB::beginTransaction();
            
            $restoredCount = 0;
            
            foreach ($backup['variants'] as $variantData) {
                ESBTPFraisVariant::create([
                    'category_id' => $variantData['category_id'],
                    'name' => $variantData['name'],
                    'description' => $variantData['description'],
                    'amount' => $variantData['amount'],
                    'additional_amount' => $variantData['additional_amount'],
                    'is_default' => $variantData['is_default'],
                    'is_active' => $variantData['is_active'],
                    'sort_order' => $variantData['sort_order']
                ]);
                
                $restoredCount++;
            }
            
            DB::commit();
            
            Log::info('Restoration depuis backup réussie', [
                'restored_count' => $restoredCount,
                'backup_path' => $backupPath
            ]);
            
            return [
                'success' => true,
                'restored_count' => $restoredCount,
                'message' => "Restoration réussie: {$restoredCount} variants restaurés"
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erreur lors de la restoration', [
                'error' => $e->getMessage(),
                'backup_path' => $backupPath
            ]);
            
            return [
                'success' => false,
                'message' => 'Erreur lors de la restoration: ' . $e->getMessage()
            ];
        }
    }
}