<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * L'annee visee par les inscriptions en ligne, decouplee de l'annee courante.
 *
 * La rentree arrive toujours avant que l'annee precedente soit close. Une ecole
 * qui n'a pas fini de saisir ses notes ne PEUT pas basculer `is_current` : les
 * ecrans de saisie des notes et des evaluations filtrent dessus sans offrir de
 * selecteur d'annee, si bien que le jour de la bascule, les evaluations de
 * l'annee ecoulee disparaissent de l'ecran. Lier l'inscription en ligne a
 * `is_current` obligerait donc a choisir entre finir ses bulletins et ouvrir sa
 * rentree — c'est exactement la situation d'ESBTP Abidjan en aout 2026.
 *
 * Vide par defaut : rien ne change pour une ecole qui ne configure rien.
 *
 * La cle vit dans l'espace `inscriptions.*` et non `reinscriptions.*` a
 * dessein : une reinscription EST une inscription, et une ecole ne visera pas
 * deux annees differentes selon qu'un eleve est nouveau ou non. Le canal des
 * nouvelles inscriptions, quand il existera, lira le meme reglage.
 */
return new class extends Migration
{
    private const CLE = 'inscriptions.annee_cible';

    public function up(): void
    {
        if (DB::table('settings')->where('key', self::CLE)->exists()) {
            return;
        }

        // Voir la migration des reglages du portail : la cle etrangere
        // settings.created_by est ON DELETE SET NULL, donc coder « 1 » en dur
        // casserait la suite de tests sur une base fraiche, ou aucun user
        // n'existe encore.
        $createur = DB::table('users')->min('id');

        DB::table('settings')->insert([
            'key' => self::CLE,
            'value' => '',
            'type' => 'string',
            'group' => 'scolarite',
            'category' => 'scolarite',
            'default_value' => '',
            'description' => "Annee universitaire visee par les inscriptions en ligne. Vide = l'annee courante. A renseigner quand l'ecole ouvre sa rentree avant d'avoir clos l'annee precedente.",
            'is_required' => 0,
            'validation_rules' => null,
            'is_active' => 1,
            'sort_order' => 160,
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
