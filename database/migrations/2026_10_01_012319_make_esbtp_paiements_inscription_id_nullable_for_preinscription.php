<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Un versement de préinscription existe AVANT l'inscription académique.
 *
 * Le parcours d'inscription configurable encaisse la préinscription sur la
 * candidature (`candidature_id`) et ne rattache le versement à l'inscription
 * qu'à la finalisation. La migration du 30 septembre 2026 affirmait que
 * `inscription_id` était « déjà nullable » : c'est vrai seulement des bases
 * créées par 2025_04_23_231835. Celles créées par 2025_03_01_100003 — la
 * majorité des instances — la déclarent NOT NULL, et chaque encaissement du
 * parcours échouait en 1048.
 *
 * Aucune donnée n'est modifiée. La clé étrangère reste en place : MODIFY ne
 * change que la nullabilité d'une colonne au type identique.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('esbtp_paiements') || ! Schema::hasColumn('esbtp_paiements', 'inscription_id')) {
            return;
        }

        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE `esbtp_paiements` MODIFY `inscription_id` BIGINT UNSIGNED NULL DEFAULT NULL');
    }

    /**
     * Ne rétablit NOT NULL que si aucun versement ne l'exige plus : défaire en
     * présence de préinscriptions non finalisées échouerait ou les perdrait.
     */
    public function down(): void
    {
        if (! Schema::hasTable('esbtp_paiements') || DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $orphelins = DB::table('esbtp_paiements')->whereNull('inscription_id')->count();
        if ($orphelins > 0) {
            Log::warning('[migration] inscription_id laissée nullable : des versements de préinscription existent', [
                'lignes' => $orphelins,
            ]);

            return;
        }

        DB::statement('ALTER TABLE `esbtp_paiements` MODIFY `inscription_id` BIGINT UNSIGNED NOT NULL');
    }
};
