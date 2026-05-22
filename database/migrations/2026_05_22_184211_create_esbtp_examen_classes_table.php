<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Refonte LMD UEMOA examens — un examen peut désormais cibler N classes
 * (mutualisation par parcours/mention/domaine). Cette migration introduit :
 *
 *  1. Table pivot `esbtp_examen_classes` (examen ↔ classes).
 *  2. Colonnes `scope_type` + `scope_id` sur `esbtp_examens_planifies`
 *     pour conserver l'intention de l'utilisateur (parcours, mention, etc.)
 *     et la reconstituer en édition.
 *  3. Colonne `unite_enseignement_id` (FK rapide vers UE pour filtrage).
 *  4. Colonne `parcours_ids` JSON pour le toggle "inter-parcours".
 *  5. Backfill : copie de `classe_id` existant vers la pivot (rétrocompat).
 *
 * La colonne `classe_id` est conservée mais devient nullable — elle représente
 * la "classe principale" pour les examens legacy BTS et l'audit historique.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esbtp_examen_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examen_id')
                ->constrained('esbtp_examens_planifies')
                ->cascadeOnDelete();
            $table->foreignId('classe_id')
                ->constrained('esbtp_classes')
                ->cascadeOnDelete();
            $table->boolean('excluded')->default(false)
                ->comment('User a décoché cette classe du scope auto');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['examen_id', 'classe_id'], 'examen_classe_unique');
            $table->index('classe_id');
        });

        Schema::table('esbtp_examens_planifies', function (Blueprint $table) {
            // Le scope défini par l'utilisateur — informationnel + reconstitution
            $table->string('scope_type', 16)
                ->default('classe')
                ->after('classe_id')
                ->comment('classe | parcours | mention | domaine');
            $table->unsignedBigInteger('scope_id')->nullable()->after('scope_type')
                ->comment('ID de l\'entité scope (parcours_id / mention_id (=filiere_id en LMD) / domaine_id)');

            // FK directe vers UE pour filtrage rapide (dérivable depuis matiere.unite_enseignement_id)
            $table->unsignedBigInteger('unite_enseignement_id')->nullable()->after('matiere_id');

            // Inter-parcours toggle : liste des parcours supplémentaires mutualisés
            $table->json('parcours_ids')->nullable()->after('parcours_id')
                ->comment('IDs des parcours additionnels en inter-parcours (le scope_id reste le parcours "principal")');

            $table->index(['scope_type', 'scope_id'], 'examens_scope_idx');
            $table->index('unite_enseignement_id', 'examens_ue_idx');

            // classe_id devient nullable (compat soft : ancien BTS conserve, LMD peut avoir 0 classe directe)
            $table->unsignedBigInteger('classe_id')->nullable()->change();
        });

        // Backfill : copie `classe_id` existant vers la pivot
        DB::table('esbtp_examens_planifies')
            ->whereNotNull('classe_id')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                $now = now();
                $inserts = [];
                foreach ($rows as $row) {
                    $inserts[] = [
                        'examen_id' => $row->id,
                        'classe_id' => $row->classe_id,
                        'excluded' => false,
                        'created_at' => $row->created_at ?? $now,
                        'updated_at' => $row->updated_at ?? $now,
                    ];
                }
                if ($inserts) {
                    // insertOrIgnore : on évite l'erreur si la migration a déjà tourné partiellement
                    DB::table('esbtp_examen_classes')->insertOrIgnore($inserts);
                }
            });

        // Renseigner unite_enseignement_id depuis matiere si présent
        DB::statement(<<<'SQL'
            UPDATE esbtp_examens_planifies e
            INNER JOIN esbtp_matieres m ON m.id = e.matiere_id
            SET e.unite_enseignement_id = m.unite_enseignement_id
            WHERE e.unite_enseignement_id IS NULL
              AND m.unite_enseignement_id IS NOT NULL
SQL);
    }

    public function down(): void
    {
        Schema::table('esbtp_examens_planifies', function (Blueprint $table) {
            $table->dropIndex('examens_scope_idx');
            $table->dropIndex('examens_ue_idx');
            $table->dropColumn(['scope_type', 'scope_id', 'unite_enseignement_id', 'parcours_ids']);
            // On laisse classe_id nullable (réversion molle, pas de re-NOT-NULL pour éviter casser
            // d'éventuelles données introduites en LMD multi-classe entre-temps)
        });
        Schema::dropIfExists('esbtp_examen_classes');
    }
};
