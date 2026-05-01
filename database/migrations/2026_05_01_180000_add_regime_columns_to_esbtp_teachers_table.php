<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fusion DB : `esbtp_enseignant_profiles` est aboli, ses colonnes pertinentes
     * sont ramenées dans `esbtp_teachers`. Migration idempotente.
     */
    public function up(): void
    {
        Schema::table('esbtp_teachers', function (Blueprint $table) {
            if (!Schema::hasColumn('esbtp_teachers', 'regime')) {
                $table->enum('regime', ['vacataire', 'permanent', 'consultant'])
                    ->default('vacataire')
                    ->after('status');
            }
            if (!Schema::hasColumn('esbtp_teachers', 'taux_horaire')) {
                $table->decimal('taux_horaire', 10, 2)->nullable()->after('regime');
            }
            if (!Schema::hasColumn('esbtp_teachers', 'date_debut_activite')) {
                $table->date('date_debut_activite')->nullable()->after('taux_horaire');
            }
            if (!Schema::hasColumn('esbtp_teachers', 'diplome_principal')) {
                $table->string('diplome_principal')->nullable()->after('date_debut_activite');
            }
            if (!Schema::hasColumn('esbtp_teachers', 'universite_diplome')) {
                $table->string('universite_diplome')->nullable()->after('diplome_principal');
            }
            if (!Schema::hasColumn('esbtp_teachers', 'annee_diplome')) {
                $table->year('annee_diplome')->nullable()->after('universite_diplome');
            }
        });

        // Data copy depuis esbtp_enseignant_profiles si la table existe (tenant déjà migré).
        // Idempotent : ré-exécuter écrase avec les mêmes valeurs.
        if (Schema::hasTable('esbtp_enseignant_profiles')) {
            DB::statement("
                UPDATE esbtp_teachers t
                INNER JOIN esbtp_enseignant_profiles p ON p.user_id = t.user_id
                SET
                    t.regime = CASE
                        WHEN p.type_contrat = 'permanent' THEN 'permanent'
                        WHEN p.type_contrat = 'consultant' THEN 'consultant'
                        ELSE 'vacataire'
                    END,
                    t.taux_horaire = COALESCE(t.taux_horaire, p.taux_horaire),
                    t.date_debut_activite = COALESCE(t.date_debut_activite, p.date_embauche),
                    t.diplome_principal = COALESCE(t.diplome_principal, p.diplome_principal),
                    t.universite_diplome = COALESCE(t.universite_diplome, p.universite_diplome),
                    t.annee_diplome = COALESCE(t.annee_diplome, p.annee_diplome)
            ");
        }
    }

    public function down(): void
    {
        Schema::table('esbtp_teachers', function (Blueprint $table) {
            $columns = ['regime', 'taux_horaire', 'date_debut_activite',
                'diplome_principal', 'universite_diplome', 'annee_diplome'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('esbtp_teachers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
