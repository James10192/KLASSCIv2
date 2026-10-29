<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sous-lot α : ajoute colonne `periode` à esbtp_matiere_coefficients pour permettre
     * coefficients différents S1 vs S2.
     *
     * Backfill : pour chaque row existante (qui était "commune S1+S2"),
     *   1. UPDATE en place pour mettre periode='semestre1'
     *   2. INSERT une copie de chaque row avec periode='semestre2'
     * → comportement actuel "coeff commun" préservé après migration.
     *
     * Unique key recomposé : (matiere, filiere, niveau, annee, periode).
     */
    public function up()
    {
        if (!Schema::hasTable('esbtp_matiere_coefficients')) {
            return;
        }

        // MySQL refuse de supprimer un index qui soutient une clé étrangère, et
        // SET FOREIGN_KEY_CHECKS=0 ne lève PAS cette règle : il ne désactive que
        // la validation des lignes. MariaDB, lui, l'accepte — d'où une migration
        // qui passe sur toute la flotte et échoue sur MySQL 8.
        //
        // Elle échouait de la pire façon : le `catch` vide avalait le refus, et
        // la panne ressortait quarante lignes plus bas sous « Duplicate key name »,
        // qui ne dit rien de la cause. Le remède est de donner à chaque clé
        // étrangère son propre index AVANT de retirer l'unique composite — seul
        // `matiere_id` s'appuyait sur lui, étant en tête du composite, mais les
        // quatre sont posés pour que la migration ne dépende pas de cet ordre.
        //
        // Même remède que pour esbtp_attendance_manual_hours (2026_04_23).
        foreach ([
            'matiere_coeff_matiere_idx' => 'matiere_id',
            'matiere_coeff_filiere_idx' => 'filiere_id',
            'matiere_coeff_niveau_idx' => 'niveau_etude_id',
            'matiere_coeff_annee_idx' => 'annee_universitaire_id',
        ] as $nom => $colonne) {
            if (! $this->indexExiste($nom)) {
                DB::statement("ALTER TABLE esbtp_matiere_coefficients ADD INDEX {$nom} ({$colonne})");
            }
        }

        // Plus de try/catch : si la suppression échoue maintenant, c'est une
        // cause qu'on ne connaît pas, et elle doit se voir ici plutôt que de
        // ressortir déguisée à la fin.
        if ($this->indexExiste('matiere_coeff_unique')) {
            DB::statement('ALTER TABLE esbtp_matiere_coefficients DROP INDEX matiere_coeff_unique');
        }

        Schema::table('esbtp_matiere_coefficients', function (Blueprint $table) {
            if (!Schema::hasColumn('esbtp_matiere_coefficients', 'periode')) {
                $table->enum('periode', ['semestre1', 'semestre2'])
                    ->default('semestre1')
                    ->after('annee_universitaire_id');
            }
        });

        // Backfill : toutes les rows existantes deviennent S1, puis on duplique en S2
        DB::table('esbtp_matiere_coefficients')->update(['periode' => 'semestre1']);

        $rowsS1 = DB::table('esbtp_matiere_coefficients')
            ->where('periode', 'semestre1')
            ->get();

        $now = now();
        $batchS2 = [];
        foreach ($rowsS1 as $r) {
            $batchS2[] = [
                'matiere_id' => $r->matiere_id,
                'filiere_id' => $r->filiere_id,
                'niveau_etude_id' => $r->niveau_etude_id,
                'annee_universitaire_id' => $r->annee_universitaire_id,
                'periode' => 'semestre2',
                'coefficient' => $r->coefficient,
                'created_by' => $r->created_by,
                'updated_by' => $r->updated_by,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if (count($batchS2) >= 500) {
                DB::table('esbtp_matiere_coefficients')->insert($batchS2);
                $batchS2 = [];
            }
        }
        if (!empty($batchS2)) {
            DB::table('esbtp_matiere_coefficients')->insert($batchS2);
        }

        if (! $this->indexExiste('matiere_coeff_unique')) {
            Schema::table('esbtp_matiere_coefficients', function (Blueprint $table) {
                $table->unique(
                    ['matiere_id', 'filiere_id', 'niveau_etude_id', 'annee_universitaire_id', 'periode'],
                    'matiere_coeff_unique'
                );
            });
        }
    }

    private function indexExiste(string $nom): bool
    {
        $ligne = DB::selectOne(
            'SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.STATISTICS '
            .'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
            ['esbtp_matiere_coefficients', $nom]
        );

        return ((int) $ligne->c) > 0;
    }

    public function down()
    {
        if (!Schema::hasTable('esbtp_matiere_coefficients')) {
            return;
        }

        // Les index dédiés posés par up() soutiennent les clés étrangères, donc
        // la suppression de l'unique composite passe ici sans désactiver quoi
        // que ce soit — et sans avaler son propre échec.
        if ($this->indexExiste('matiere_coeff_unique')) {
            DB::statement('ALTER TABLE esbtp_matiere_coefficients DROP INDEX matiere_coeff_unique');
        }

        DB::table('esbtp_matiere_coefficients')->where('periode', 'semestre2')->delete();

        Schema::table('esbtp_matiere_coefficients', function (Blueprint $table) {
            if (Schema::hasColumn('esbtp_matiere_coefficients', 'periode')) {
                $table->dropColumn('periode');
            }
            $table->unique(
                ['matiere_id', 'filiere_id', 'niveau_etude_id', 'annee_universitaire_id'],
                'matiere_coeff_unique'
            );
        });
    }
};
