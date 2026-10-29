<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Sème les seuils qui colorent la moyenne générale et le taux de réussite de
 * /esbtp/resultats.
 *
 * L'écran de réglages ne met à jour que les lignes existantes : sans ce semis,
 * les champs s'afficheraient sans jamais être enregistrés. Les clés sont
 * écrites en toutes lettres : une migration décrit un état figé.
 */
return new class extends Migration
{
    private const REGLAGES = [
        'resultats.moyenne_satisfaisante' => [
            'value' => '12',
            'type' => 'float',
            'description' => 'Moyenne générale (sur 20) à partir de laquelle la page Résultats l’affiche en vert. Entre 10 et cette valeur, elle est en orange ; sous 10, en rouge.',
            'sort_order' => 173,
        ],
        'resultats.reussite_satisfaisante_pct' => [
            'value' => '70',
            'type' => 'integer',
            'description' => 'Taux de réussite (en %) à partir duquel la page Résultats l’affiche en vert.',
            'sort_order' => 174,
        ],
        'resultats.reussite_alerte_pct' => [
            'value' => '50',
            'type' => 'integer',
            'description' => 'Taux de réussite (en %) sous lequel la page Résultats l’affiche en rouge.',
            'sort_order' => 175,
        ],
    ];

    public function up(): void
    {
        $createur = DB::table('users')->min('id');

        foreach (self::REGLAGES as $cle => $reglage) {
            if (DB::table('settings')->where('key', $cle)->exists()) {
                continue;
            }

            DB::table('settings')->insert([
                'key' => $cle,
                'value' => $reglage['value'],
                'type' => $reglage['type'],
                'group' => 'scolarite',
                'category' => 'scolarite',
                'default_value' => $reglage['value'],
                'description' => $reglage['description'],
                'is_required' => 0,
                'validation_rules' => null,
                'is_active' => 1,
                'sort_order' => $reglage['sort_order'],
                'created_by' => $createur,
                'updated_by' => $createur,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', array_keys(self::REGLAGES))->delete();
    }
};
