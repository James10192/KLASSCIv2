<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupFiliereManagement extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Créer une fonction simple pour logger les migrations au lieu d'utiliser migration_log
        $logMigrationMessage = function($message) {
            Log::info("Migration: " . $message);
        };

        // Vérifier l'existence des tables avant de migrer les données
        if (Schema::hasTable('esbtp_filiere_matiere') && Schema::hasTable('esbtp_matiere_filiere')) {
            try {
                // Copier les données de l'ancienne table vers la nouvelle (si nécessaire)
                $rows = DB::table('esbtp_filiere_matiere')->get();
                foreach ($rows as $row) {
                    // Vérifier si l'entrée existe déjà dans la nouvelle table
                    $exists = DB::table('esbtp_matiere_filiere')
                        ->where('matiere_id', $row->matiere_id)
                        ->where('filiere_id', $row->filiere_id)
                        ->exists();

                    if (!$exists) {
                        DB::table('esbtp_matiere_filiere')->insert([
                            'matiere_id' => $row->matiere_id,
                            'filiere_id' => $row->filiere_id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
                $logMigrationMessage('Successfully migrated data from esbtp_filiere_matiere to esbtp_matiere_filiere');
            } catch (\Exception $e) {
                $logMigrationMessage('Failed to migrate data from esbtp_filiere_matiere: ' . $e->getMessage());
            }
        } else {
            $logMigrationMessage('One of the required tables does not exist, skipping data migration');
        }

        // Supprimer l'ancienne table si elle existe
        if (Schema::hasTable('esbtp_filiere_matiere')) {
            Schema::dropIfExists('esbtp_filiere_matiere');
            $logMigrationMessage('Successfully dropped table esbtp_filiere_matiere');
        }

        // 2. Handle the redundant active field in esbtp_filieres
        if (Schema::hasTable('esbtp_filieres') &&
            Schema::hasColumn('esbtp_filieres', 'is_active') &&
            Schema::hasColumn('esbtp_filieres', 'active')) {

            try {
                // Copy active value to is_active for any rows where they differ
                $filieres = DB::table('esbtp_filieres')->get();

                foreach ($filieres as $filiere) {
                    if ((bool)$filiere->active !== (bool)$filiere->is_active) {
                        DB::table('esbtp_filieres')
                            ->where('id', $filiere->id)
                            ->update(['is_active' => (bool)$filiere->active]);
                    }
                }

                // Drop the redundant column
                Schema::table('esbtp_filieres', function (Blueprint $table) {
                    $table->dropColumn('active');
                });

                // Log success
                $logMigrationMessage('Successfully removed redundant active column from esbtp_filieres');
            } catch (\Exception $e) {
                // Log error but continue migration
                $logMigrationMessage('Failed to update is_active field: ' . $e->getMessage());
            }
        }

        // 3. Handle the direct filiere_id from esbtp_matieres
        if (Schema::hasTable('esbtp_matieres') &&
            Schema::hasTable('esbtp_matiere_filiere') &&
            Schema::hasColumn('esbtp_matieres', 'filiere_id')) {

            try {
                // Ensure all direct filiere_id relationships are in the pivot table
                $matieres = DB::table('esbtp_matieres')
                           ->whereNotNull('filiere_id')
                           ->get();

                foreach ($matieres as $matiere) {
                    // Check if relationship already exists in pivot table
                    $exists = DB::table('esbtp_matiere_filiere')
                        ->where('matiere_id', $matiere->id)
                        ->where('filiere_id', $matiere->filiere_id)
                        ->exists();

                    // If not exists, insert it
                    if (!$exists) {
                        DB::table('esbtp_matiere_filiere')->insert([
                            'matiere_id' => $matiere->id,
                            'filiere_id' => $matiere->filiere_id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                // Remove the column
                Schema::table('esbtp_matieres', function (Blueprint $table) {
                    if (Schema::hasColumn('esbtp_matieres', 'filiere_id')) {
                        $table->dropForeign(['filiere_id']);
                        $table->dropColumn('filiere_id');
                    }
                });

                $logMigrationMessage('Successfully migrated direct filiere_id relationships to pivot table');
            } catch (\Exception $e) {
                // Log error but continue migration
                $logMigrationMessage('Failed to migrate filiere_id relationships: ' . $e->getMessage());
            }
        }

        // 4. Ensure esbtp_matiere_filiere has the correct structure
        if (!Schema::hasColumn('esbtp_matiere_filiere', 'is_active')) {
            try {
                Schema::table('esbtp_matiere_filiere', function (Blueprint $table) {
                    $table->boolean('is_active')->default(true)->after('matiere_id');
                });

                $logMigrationMessage('Added is_active column to esbtp_matiere_filiere table');
            } catch (\Exception $e) {
                // Log failure
                $logMigrationMessage('Failed to add is_active column to esbtp_matiere_filiere table: ' . $e->getMessage());
            }
        }

        // Ensure proper timestamps and index exists
        if (!Schema::hasColumn('esbtp_matiere_filiere', 'created_at')) {
            try {
                Schema::table('esbtp_matiere_filiere', function (Blueprint $table) {
                    $table->timestamps();
                });

                $logMigrationMessage('Added timestamps to esbtp_matiere_filiere table');
            } catch (\Exception $e) {
                // Log failure
                $logMigrationMessage('Failed to add timestamps to esbtp_matiere_filiere table: ' . $e->getMessage());
            }
        }

        // Update existing relationships to have is_active=true
        if (Schema::hasColumn('esbtp_matiere_filiere', 'is_active')) {
            try {
                DB::table('esbtp_matiere_filiere')
                    ->whereNull('is_active')
                    ->update(['is_active' => true]);

                $logMigrationMessage('Updated existing relationships in esbtp_matiere_filiere to have is_active=true');
            } catch (\Exception $e) {
                // Log failure
                $logMigrationMessage('Failed to update existing relationships in esbtp_matiere_filiere: ' . $e->getMessage());
            }
        }
    }

    /**
     * Check if an index exists
     *
     * @param string $table
     * @param string $index
     * @return bool
     */
    private function hasIndex($table, $index)
    {
        // Depuis Laravel 11, le schema natif remplace Doctrine pour les index.
        return Schema::hasIndex($table, $index);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // La table a été supprimée, nous devrons la recréer si nécessaire
        if (!Schema::hasTable('esbtp_filiere_matiere')) {
            Schema::create('esbtp_filiere_matiere', function (Blueprint $table) {
                $table->id();
                $table->foreignId('filiere_id')->constrained('esbtp_filieres')->onDelete('cascade');
                $table->foreignId('matiere_id')->constrained('esbtp_matieres')->onDelete('cascade');
                $table->timestamps();
            });

            Log::info('Migration: Successfully recreated table esbtp_filiere_matiere');
        }
    }
}
