<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * La date a laquelle l'ecole recoit les dossiers sur place.
 *
 * Deposer une candidature en ligne ne finit rien : le dossier se boucle a
 * l'etablissement, avec les pieces et le paiement. Restait la question que la
 * scolarite entend le plus au telephone — « je viens quand ? ». Le portail
 * peut y repondre lui-meme, a condition que l'ecole ait dit a partir de quand.
 *
 * Un reglage, pas une constante : chaque ecole ouvre son guichet quand elle
 * veut, et cette date bouge d'une rentree a l'autre. Vide par defaut — le
 * portail se contente alors d'inviter a se rapprocher de l'etablissement,
 * ce qui reste vrai en toutes circonstances.
 */
return new class extends Migration
{
    private const CLE = 'inscriptions.physiques.debut';

    public function up(): void
    {
        if (DB::table('settings')->where('key', self::CLE)->exists()) {
            return;
        }

        // Cle etrangere settings.created_by en ON DELETE SET NULL : coder « 1 »
        // en dur casserait la suite de tests sur une base fraiche.
        $createur = DB::table('users')->min('id');

        DB::table('settings')->insert([
            'key' => self::CLE,
            'value' => '',
            // `string` et non `date` : c'est le type que la page de reglages
            // sait rendre, et c'est celui des deux bornes de la fenetre
            // saisonniere voisines (reinscriptions.en_ligne.ouverture/fermeture).
            // Une exception ici obligerait a toucher le formulaire.
            'type' => 'string',
            'group' => 'scolarite',
            'category' => 'scolarite',
            'default_value' => '',
            'description' => "Premier jour ou l'etablissement recoit les candidats pour finaliser leur dossier (pieces et paiement), au format AAAA-MM-JJ. Le portail public l'annonce a la fin du formulaire. Vide = aucune date annoncee.",
            'is_required' => 0,
            'validation_rules' => null,
            'is_active' => 1,
            'sort_order' => 162,
            'created_by' => $createur,
            'updated_by' => $createur,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->where('key', self::CLE)->delete();
    }
};
