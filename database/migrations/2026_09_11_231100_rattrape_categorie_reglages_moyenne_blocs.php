<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rend visibles les trois reglages de composition de la moyenne.
 *
 * La migration qui les a semes posait `group` mais pas `category`. Or l'ecran
 * de configuration fait `orderBy('category')` puis `groupBy('category')` : une
 * ligne sans categorie existe en base et n'apparait sur aucun ecran. Les six
 * instances deja migrees portent donc trois reglages que l'ecole ne peut ni
 * voir ni modifier — la bascule a ete livree sans son interrupteur.
 *
 * Corriger la migration d'origine ne suffit pas : elle ne se rejouera pas la
 * ou elle est deja passee. D'ou ce rattrapage, qui ne touche que la categorie
 * et jamais la valeur.
 */
return new class extends Migration
{
    private const CLES = [
        'bulletin_moyenne_mode',
        'bulletin_bloc_general_coef',
        'bulletin_bloc_professionnel_coef',
    ];

    public function up(): void
    {
        DB::table('settings')
            ->whereIn('key', self::CLES)
            ->where(function ($requete) {
                $requete->whereNull('category')->orWhere('category', '');
            })
            ->update(['category' => 'bulletin', 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Volontairement sans effet : remettre `category` a null rendrait de
        // nouveau ces reglages introuvables dans l'ecran de configuration.
    }
};
