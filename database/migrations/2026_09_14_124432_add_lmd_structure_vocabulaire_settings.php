<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Le nom des trois rangs de la structure LMD, par etablissement.
 *
 * Domaine → Mention → Parcours est le vocabulaire du referentiel UEMOA. Une
 * universite organisee en composantes, departements et specialites garde la
 * meme structure a trois rangs : seul le mot change. Defauts : le vocabulaire
 * actuel, rien ne change pour les instances existantes.
 *
 * Lu par App\Services\LMD\VocabulaireStructure.
 */
return new class extends Migration
{
    private const REGLAGES = [
        'lmd.structure_libelle_domaine' => [
            'valeur' => 'Domaine',
            'ordre' => 300,
            'description' => "Nom du premier rang de la structure LMD, au singulier (ex. : Domaine, Composante, Faculté). Chaque élément de ce rang peut en plus porter sa nature (UFR, faculté, école, institut). Défaut : Domaine.",
        ],
        'lmd.structure_libelle_mention' => [
            'valeur' => 'Mention',
            'ordre' => 301,
            'description' => "Nom du deuxième rang de la structure LMD, au singulier (ex. : Mention, Département). Défaut : Mention.",
        ],
        'lmd.structure_libelle_parcours' => [
            'valeur' => 'Parcours',
            'ordre' => 302,
            'description' => "Nom du troisième rang de la structure LMD, au singulier (ex. : Parcours, Spécialité, Option). Défaut : Parcours.",
        ],
    ];

    public function up(): void
    {
        // settings.created_by porte une cle etrangere vers users : « 1 » en dur
        // casserait la migration sur une base fraiche.
        $createur = DB::table('users')->min('id');
        $maintenant = now();

        foreach (self::REGLAGES as $cle => $reglage) {
            if (DB::table('settings')->where('key', $cle)->exists()) {
                continue;
            }

            DB::table('settings')->insert([
                'key' => $cle,
                'value' => $reglage['valeur'],
                'type' => 'string',
                'group' => 'lmd',
                'category' => 'lmd',
                'default_value' => $reglage['valeur'],
                'description' => $reglage['description'],
                'is_required' => 0,
                // Colonne JSON (CHECK json_valid sur MariaDB) : un tableau encode.
                'validation_rules' => json_encode(['nullable', 'string', 'max:40']),
                'is_active' => 1,
                'sort_order' => $reglage['ordre'],
                'created_by' => $createur,
                'updated_by' => $createur,
                'created_at' => $maintenant,
                'updated_at' => $maintenant,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', array_keys(self::REGLAGES))->delete();
    }
};
