<?php

use App\Services\Mobile\MobileProfileResolver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Le shell mobile (barre d'onglets, feuilles) est-il active sur cette instance ?
 *
 * Une ecole peut ne pas en vouloir : personnel uniquement sur poste fixe, ou
 * habitude de l'interface classique sur telephone. C'est donc un reglage,
 * actif par defaut : il ne coute rien a qui ouvre l'application sur un ecran
 * large, ou il ne s'affiche pas.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('settings')->where('key', MobileProfileResolver::REGLAGE_ACTIF)->exists()) {
            return;
        }

        // Cle etrangere settings.created_by en ON DELETE SET NULL : coder « 1 »
        // en dur casserait la suite de tests sur une base fraiche.
        $createur = DB::table('users')->min('id');

        DB::table('settings')->insert([
            'key' => MobileProfileResolver::REGLAGE_ACTIF,
            'value' => '1',
            'type' => 'boolean',
            'group' => 'ui',
            'category' => 'ui',
            'default_value' => '1',
            'description' => "Shell mobile (barre d'onglets, feuilles). Sur telephone, remplace le menu lateral "
                . "par une barre d'onglets adaptee au profil de la personne connectee (caisse, comptabilite, "
                . "enseignant, etudiant). Desactive, l'interface classique est servie partout.",
            'is_required' => 0,
            'validation_rules' => null,
            'is_active' => 1,
            'sort_order' => 180,
            'created_by' => $createur,
            'updated_by' => $createur,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->where('key', MobileProfileResolver::REGLAGE_ACTIF)->delete();
    }
};
