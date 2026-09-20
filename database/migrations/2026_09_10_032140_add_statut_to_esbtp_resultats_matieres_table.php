<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * La photographie d'un bulletin apprend a dire ce qu'une ligne signifie.
 *
 * Jusqu'ici une ligne portait une moyenne, et rien d'autre. Une matiere sans
 * note n'y figurait pas du tout : le lecteur ne pouvait pas distinguer « le
 * professeur n'a pas rendu ses notes » de « cet etudiant en est dispense ».
 *
 * `moyenne` devient donc NULLABLE. C'est une modification de colonne, la seule
 * de ce chantier, et elle est necessaire : sans elle, une ligne « dispense » ou
 * « non note » devrait porter un zero, et un zero se compte dans une moyenne.
 * Aucune valeur existante n'est reecrite ; toutes les lignes deja en base
 * gardent leur moyenne et recoivent le statut « note », qui est ce qu'elles
 * ont toujours voulu dire.
 *
 * Cette colonne n'est PAS ajoutee a `esbtp_resultats` : cette table-la alimente
 * les rangs, les statistiques de classe et les exports, et y injecter des NULL
 * exposerait tout cela pour un benefice nul. Les quatre etats vivent sur la
 * photographie du bulletin, qui est ce que le PDF relit.
 */
return new class extends Migration
{
    private const TABLE = 'esbtp_resultats_matieres';

    public function up(): void
    {
        // Le type reel a ete releve avant d'ecrire ce MODIFY : decimal(5,2)
        // NOT NULL, cree en mars 2025 et jamais altere depuis.
        DB::statement('ALTER TABLE '.self::TABLE.' MODIFY moyenne DECIMAL(5,2) NULL');

        Schema::table(self::TABLE, function (Blueprint $table) {
            if (! Schema::hasColumn(self::TABLE, 'statut')) {
                // note | dispense | non_note. Defaut « note » : toutes les
                // lignes deja ecrites en portent une.
                $table->string('statut', 16)->default('note')->after('moyenne');
            }

            if (! Schema::hasColumn(self::TABLE, 'motif_dispense')) {
                $table->string('motif_dispense', 160)->nullable()->after('statut');
            }

            if (! Schema::hasColumn(self::TABLE, 'dispense_id')) {
                // Sans cle etrangere : une dispense se soft-delete, la
                // photographie doit lui survivre.
                $table->unsignedBigInteger('dispense_id')->nullable()->after('motif_dispense');
            }
        });
    }

    public function down(): void
    {
        Schema::table(self::TABLE, function (Blueprint $table) {
            foreach (['dispense_id', 'motif_dispense', 'statut'] as $colonne) {
                if (Schema::hasColumn(self::TABLE, $colonne)) {
                    $table->dropColumn($colonne);
                }
            }
        });

        // Remettre NOT NULL ferait de chaque « dispense » et de chaque « non
        // note » un zero — c'est-a-dire un echec, sur un document officiel.
        // On refuse plutot que de convertir en silence.
        $aDesNuls = DB::table(self::TABLE)->whereNull('moyenne')->exists();

        if ($aDesNuls) {
            throw new RuntimeException(
                'Des lignes de bulletin portent une moyenne nulle (dispense ou matiere non notee). '
                .'Revenir en arriere les transformerait en zero. Traitez ces lignes avant de rejouer ce rollback.'
            );
        }

        DB::statement('ALTER TABLE '.self::TABLE.' MODIFY moyenne DECIMAL(5,2) NOT NULL');
    }
};
