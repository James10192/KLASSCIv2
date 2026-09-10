<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La maquette BTS gagne deux informations, au grain (matiere, filiere, niveau) :
 * la place de la matiere sur le bulletin, et le semestre auquel elle est prevue.
 *
 * Purement additif. Aucune valeur existante n'est reecrite : toutes les lignes
 * naissent a null / false, c'est-a-dire « rien n'a ete decide ». Tant qu'une
 * ecole n'a rien decide, l'application se comporte exactement comme avant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esbtp_matiere_filiere_niveau', function (Blueprint $table) {
            if (! Schema::hasColumn('esbtp_matiere_filiere_niveau', 'ordre_bulletin')) {
                // Place de la matiere sur le bulletin pour CE combo.
                // null = herite de l'ordre general (esbtp_matieres.ordre_bulletin).
                $table->unsignedSmallInteger('ordre_bulletin')->nullable()->after('classification');
            }

            if (! Schema::hasColumn('esbtp_matiere_filiere_niveau', 'semestre')) {
                // 1 ou 2. null = prevue aux deux semestres.
                $table->unsignedTinyInteger('semestre')->nullable()->after('ordre_bulletin');
            }

            if (! Schema::hasColumn('esbtp_matiere_filiere_niveau', 'semestre_renseigne')) {
                // Dit si quelqu'un a VALIDE le semestre de cette ligne.
                //
                // Sans ce drapeau, « toutes les matieres aux deux semestres »
                // (donc semestre = null partout) serait indiscernable de « personne
                // n'a rien rempli », et repasser une matiere de « semestre 1 » a
                // « les deux » desactiverait la maquette entiere du combo.
                $table->boolean('semestre_renseigne')->default(false)->after('semestre');
            }
        });

        Schema::table('esbtp_matiere_filiere_niveau', function (Blueprint $table) {
            if (! $this->indexExiste('idx_mfn_combo_semestre')) {
                $table->index(['filiere_id', 'niveau_etude_id', 'semestre'], 'idx_mfn_combo_semestre');
            }
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_matiere_filiere_niveau', function (Blueprint $table) {
            if ($this->indexExiste('idx_mfn_combo_semestre')) {
                $table->dropIndex('idx_mfn_combo_semestre');
            }
        });

        Schema::table('esbtp_matiere_filiere_niveau', function (Blueprint $table) {
            foreach (['semestre_renseigne', 'semestre', 'ordre_bulletin'] as $colonne) {
                if (Schema::hasColumn('esbtp_matiere_filiere_niveau', $colonne)) {
                    $table->dropColumn($colonne);
                }
            }
        });
    }

    /**
     * Un schema partiellement migre ne doit ni echouer ni dupliquer : on
     * interroge l'index reellement present, pas ce qu'on suppose.
     */
    private function indexExiste(string $nom): bool
    {
        return collect(Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->listTableIndexes('esbtp_matiere_filiere_niveau'))
            ->keys()
            ->map(fn ($cle) => strtolower((string) $cle))
            ->contains(strtolower($nom));
    }
};
