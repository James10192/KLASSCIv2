<?php

use App\Services\Security\SeparationOfDutiesService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Les trois reglages de separation des devoirs, crees en base.
 *
 * `SeparationOfDutiesService` les LIT deja (`SettingsHelper::get()`), et le
 * service se replie proprement sur la valeur d'usine quand ils n'existent pas.
 * Mais la boucle d'enregistrement de l'ecran des reglages, elle, ignore en
 * SILENCE toute cle absente de la table : sans ces lignes, les trois nouvelles
 * cases s'afficheraient, se cocheraient, et ne s'enregistreraient jamais.
 *
 * Meme panne que pour les reglages de composition de la moyenne : les defauts
 * declares dans le code n'atteignent aucune instance en service.
 *
 * Additive et rejouable : une valeur deja posee par l'ecole n'est jamais
 * ecrasee. Le defaut reprend `config/sod.php`, c'est-a-dire le comportement
 * actuel — les trois regles restent actives tant que l'ecole ne demande rien.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Le premier utilisateur existant, et null sur une base vide : ecrire
        // « 1 » en dur casse la contrainte de cle etrangere des que la table
        // users est vide, et avec elle toute la suite de tests.
        $auteur = DB::table('users')->min('id');
        $maintenant = now();

        foreach (SeparationOfDutiesService::reglesExposables() as $regle) {
            if (DB::table('settings')->where('key', $regle['cle'])->exists()) {
                continue;
            }

            $valeur = $regle['defaut'] ? '1' : '0';

            DB::table('settings')->insert([
                'key' => $regle['cle'],
                'value' => $valeur,
                'type' => 'boolean',
                'description' => $regle['label'].' — '.$regle['hint'],
                'default_value' => $valeur,
                'is_required' => false,
                'is_active' => true,
                'requires_restart' => false,
                'group' => 'lmd',
                'created_by' => $auteur,
                'updated_by' => $auteur,
                'created_at' => $maintenant,
                'updated_at' => $maintenant,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')
            ->whereIn('key', SeparationOfDutiesService::clesDeReglage())
            ->delete();
    }
};
