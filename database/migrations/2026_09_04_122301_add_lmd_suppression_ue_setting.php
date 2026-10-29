<?php

use App\Services\LMD\SuppressionUeService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rendre reglable ce que devient un element constitutif quand son unite est
 * supprimee.
 *
 * `esbtp_matieres.unite_enseignement_id` est le discriminateur BTS/LMD : le
 * remettre a nul verse l'element dans le catalogue BTS, ou une vingtaine
 * d'ecrans en service iront le proposer. C'est le comportement d'origine, et il
 * a du sens dans une ecole mixte qui recycle ses matieres. Il n'en a aucun dans
 * une ecole tout-LMD comme USAT, ou l'element atterrit dans un catalogue que
 * personne ne regarde.
 *
 * La question se tranche donc par etablissement, pas dans le code. Le service
 * lisait deja le reglage avec un defaut ; sans cette ligne en base il restait
 * invisible et intouchable depuis la page de reglages, ce qui ne vaut pas
 * « configurable ».
 *
 * Valeur posee a « 1 » : le deploiement ne change strictement rien tant qu'une
 * ecole n'a pas decide le contraire.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('settings')->where('key', SuppressionUeService::REGLAGE_LIBERER_ECUES)->exists()) {
            return;
        }

        // Cle etrangere settings.created_by en ON DELETE SET NULL : coder « 1 »
        // en dur casserait la suite de tests sur une base fraiche.
        $createur = DB::table('users')->min('id');

        DB::table('settings')->insert([
            'key' => SuppressionUeService::REGLAGE_LIBERER_ECUES,
            'value' => '1',
            'type' => 'boolean',
            'group' => 'lmd',
            'category' => 'validation',
            'default_value' => '1',
            'description' => "A la suppression d'une unite d'enseignement, rendre ses elements constitutifs au catalogue BTS. "
                . "Decoche dans une ecole tout-LMD : les elements restent rattaches a l'unite supprimee plutot que "
                . "d'apparaitre dans les ecrans BTS (evaluations, notes, examens).",
            'is_required' => 0,
            'validation_rules' => null,
            'is_active' => 1,
            'sort_order' => 14,
            'created_by' => $createur,
            'updated_by' => $createur,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->where('key', SuppressionUeService::REGLAGE_LIBERER_ECUES)->delete();
    }
};
