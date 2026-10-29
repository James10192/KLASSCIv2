<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Les trois reglages de composition de la moyenne, crees en base.
 *
 * `SettingsHelper::initializeDefaults()` declare des defauts mais n'est appele
 * NULLE PART : un reglage ajoute a ce tableau n'atteint aucune instance en
 * service, et l'ecran de configuration le declare « introuvable ». Meme panne
 * silencieuse que les droits ajoutes aux role_defaults sans rattrapage.
 *
 * Additive et rejouable : une valeur deja posee par l'ecole n'est jamais
 * ecrasee. Le defaut reproduit le comportement historique — « ponderee » —,
 * donc aucune moyenne ne bouge tant que l'ecole n'a rien demande.
 */
return new class extends Migration
{
    private const REGLAGES = [
        [
            'key' => 'bulletin_moyenne_mode',
            'value' => 'ponderee',
            'description' => "Comment se compose la moyenne du semestre. « ponderee » : une seule moyenne ponderee sur toutes les matieres. « blocs » : la moyenne de l'enseignement general et celle du professionnel, combinees selon leurs coefficients.",
        ],
        [
            'key' => 'bulletin_bloc_general_coef',
            'value' => '1',
            'description' => "Poids de l'enseignement general quand la moyenne se compose par blocs.",
        ],
        [
            'key' => 'bulletin_bloc_professionnel_coef',
            'value' => '1',
            'description' => "Poids de l'enseignement professionnel quand la moyenne se compose par blocs.",
        ],
    ];

    public function up(): void
    {
        // Le premier utilisateur existant, et null sur une base vide : ecrire
        // « 1 » en dur casse la contrainte de cle etrangere des que la table
        // users est vide, et avec elle toute la suite de tests.
        $auteur = DB::table('users')->min('id');
        $maintenant = now();

        foreach (self::REGLAGES as $reglage) {
            if (DB::table('settings')->where('key', $reglage['key'])->exists()) {
                continue;
            }

            DB::table('settings')->insert([
                'key' => $reglage['key'],
                'value' => $reglage['value'],
                'type' => 'string',
                'description' => $reglage['description'],
                'default_value' => $reglage['value'],
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
    }

    public function down(): void
    {
        DB::table('settings')
            ->whereIn('key', array_column(self::REGLAGES, 'key'))
            ->delete();
    }
};
