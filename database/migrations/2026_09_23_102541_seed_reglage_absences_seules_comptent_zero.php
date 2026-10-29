<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Le reglage « une matiere faite seulement d'absences compte 0 », cree en base.
 *
 * Meme raison que `seed_reglages_moyenne_par_blocs` : un defaut declare dans
 * `SettingsHelper` n'atteint aucune instance en service, et l'ecran de
 * configuration ne le trouverait pas.
 *
 * Additive et rejouable : une valeur deja posee par l'ecole n'est jamais
 * ecrasee. Le defaut, « 1 », reproduit le comportement historique du bulletin
 * officiel — aucune moyenne ne bouge tant que l'ecole n'a rien demande.
 */
return new class extends Migration
{
    private const CLE = 'bulletin_absences_seules_comptent_zero';

    public function up(): void
    {
        if (DB::table('settings')->where('key', self::CLE)->exists()) {
            return;
        }

        // Le premier utilisateur existant, et null sur une base vide : « 1 » en
        // dur casse la cle etrangere des que la table users est vide.
        $auteur = DB::table('users')->min('id');
        $maintenant = now();

        DB::table('settings')->insert([
            'key' => self::CLE,
            'value' => '1',
            'type' => 'string',
            'description' => "Une matiere dont l'eleve n'a que des absences compte-t-elle 0 dans la moyenne generale ? « 1 » : oui, 0/20. « 0 » : elle n'a pas de moyenne et sort du calcul, comme une matiere jamais notee.",
            'default_value' => '1',
            'is_required' => false,
            'is_active' => true,
            'requires_restart' => false,
            'group' => 'bulletin',
            'created_by' => $auteur,
            'updated_by' => $auteur,
            'created_at' => $maintenant,
            'updated_at' => $maintenant,
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->where('key', self::CLE)->delete();
    }
};
