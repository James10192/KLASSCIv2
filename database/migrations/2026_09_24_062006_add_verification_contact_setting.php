<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seme le reglage « Verifier le contact des demandes en ligne », DESACTIVE.
 *
 * L'ecran de reglages ne met a jour que les lignes existantes : sans ce semis,
 * la case s'afficherait mais ne serait jamais enregistree. La cle est ecrite
 * en toutes lettres : une migration decrit un etat fige.
 */
return new class extends Migration
{
    private const CLE = 'inscriptions.portail.verification_contact';

    public function up(): void
    {
        if (DB::table('settings')->where('key', self::CLE)->exists()) {
            return;
        }

        $createur = DB::table('users')->min('id');

        DB::table('settings')->insert([
            'key' => self::CLE,
            'value' => '0',
            'type' => 'boolean',
            'group' => 'scolarite',
            'category' => 'scolarite',
            'default_value' => '0',
            'description' => 'Envoie un code (e-mail, sinon WhatsApp) après chaque dépôt sur le portail. '
                .'Tant que le contact n’est pas vérifié, la demande reste visible avec un badge et n’est ni '
                .'placée en rendez-vous ni convoquée par courriel.',
            'is_required' => 0,
            'validation_rules' => null,
            'is_active' => 1,
            'sort_order' => 158,
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
