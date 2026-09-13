<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Jusqu'à quel reste dû une réinscription est-elle acceptée.
 *
 * Deux endroits répondaient à cette question, et ils ne disaient pas la même
 * chose : la liste des étudiants affichait « peut se réinscrire » dès que le
 * solde tombait sous 50 000 F — une tolérance écrite en dur, avec son commentaire
 * — tandis que la garde qui autorise réellement l'acte exigeait un solde nul.
 * L'agent voyait donc un feu vert, cliquait, et se heurtait à un refus.
 *
 * Les deux lisent désormais ce réglage. Le défaut est **0**, c'est-à-dire le
 * comportement de la garde : aucune instance ne change de règle. C'est
 * l'affichage qui cesse de mentir, pas la porte qui s'ouvre.
 *
 * Une école qui veut réellement tolérer un reliquat le pose ici, et les deux
 * écrans suivent ensemble. Une tolérance de paiement est une politique
 * d'établissement — elle n'a rien à faire dans le code.
 */
return new class extends Migration
{
    private const CLE = 'reinscription.tolerance_solde';

    public function up(): void
    {
        if (DB::table('settings')->where('key', self::CLE)->exists()) {
            return;
        }

        // `settings.created_by` porte une cle etrangere vers `users` : coder « 1 »
        // en dur casserait la migration sur une base fraiche, ou aucun
        // utilisateur n'existe encore.
        $createur = DB::table('users')->min('id');
        $maintenant = now();

        DB::table('settings')->insert([
            'key' => self::CLE,
            'value' => '0',
            'type' => 'number',
            'group' => 'scolarite',
            'category' => 'scolarite',
            'default_value' => '0',
            'description' => "Reste dû, en francs, jusqu'auquel un étudiant est encore autorisé à se réinscrire. À 0, la réinscription exige un dossier entièrement soldé. Une valeur plus haute tolère un reliquat : l'écran et la garde appliquent alors le même seuil, ce qui n'était pas le cas avant ce réglage.",
            'is_required' => 0,
            'validation_rules' => 'numeric|min:0',
            'is_active' => 1,
            'sort_order' => 200,
            'created_by' => $createur,
            'updated_by' => $createur,
            'created_at' => $maintenant,
            'updated_at' => $maintenant,
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->where('key', self::CLE)->delete();
    }
};
