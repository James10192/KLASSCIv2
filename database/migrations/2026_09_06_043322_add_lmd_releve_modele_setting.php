<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Quelle mise en page pour le relevé de notes LMD.
 *
 * Deux modèles coexistent désormais :
 *
 *  - `klassci` : celui qui existait. Bandeau de l'établissement, couleurs de
 *    l'école, code de vérification. Trois écoles LMD l'impriment déjà ;
 *  - `mesrs` : le modèle officiel du Ministère de l'Enseignement Supérieur —
 *    en-tête à deux colonnes, cadres logo et emblème, mention et décision par
 *    unité d'enseignement, semestres en marge, cumul de crédits annuel.
 *
 * Le défaut reste `klassci`, et ce n'est pas de la timidité : changer le papier
 * d'une école qui ne l'a pas demandé est une régression, pas une amélioration.
 * Une université qui doit rendre des relevés au ministère bascule sur `mesrs`.
 *
 * Le choix est GRAVÉ dans l'instantané au moment de l'émission, pas relu à
 * l'impression : une école qui bascule ne doit pas voir changer la mise en page
 * des relevés qu'elle a déjà émis et signés.
 */
return new class extends Migration
{
    private const CLE = 'lmd_releve_modele';

    public function up(): void
    {
        if (DB::table('settings')->where('key', self::CLE)->exists()) {
            return;
        }

        // settings.created_by porte une cle etrangere vers users en ON DELETE
        // SET NULL : coder « 1 » en dur casserait la migration sur une base
        // fraiche, ou aucun utilisateur n'existe encore.
        $createur = DB::table('users')->min('id');
        $maintenant = now();

        DB::table('settings')->insert([
            'key' => self::CLE,
            'value' => 'klassci',
            'type' => 'string',
            'group' => 'academique',
            'category' => 'academique',
            'default_value' => 'klassci',
            'description' => "Mise en page du releve de notes LMD. klassci : le modele de l'application, avec le bandeau et les couleurs de l'ecole. mesrs : le modele officiel du Ministere de l'Enseignement Superieur, avec en-tete ministeriel, mention et decision par unite d'enseignement. Le choix est grave a l'emission : les releves deja emis gardent leur mise en page. Defaut : klassci.",
            'is_required' => 0,
            'validation_rules' => null,
            'is_active' => 1,
            'sort_order' => 190,
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
