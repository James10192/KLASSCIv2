<?php

use App\Services\Documents\CodeQrDocument;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Imprime-t-on un code QR sur les documents ?
 *
 * Le code ramene le papier au dossier : la fiche part au guichet, revient
 * signee, et il faut alors retrouver l'eleve. Il n'expose rien — l'adresse mene
 * a l'application, qui demande de s'identifier.
 *
 * Une ecole peut ne pas en vouloir : papier deja charge, pas de telephone au
 * guichet, usage juge inutile. C'est donc un reglage. Le defaut est « oui » :
 * le code ne coute rien a qui l'ignore.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('settings')->where('key', CodeQrDocument::REGLAGE_ACTIF)->exists()) {
            return;
        }

        // Cle etrangere settings.created_by en ON DELETE SET NULL : coder « 1 »
        // en dur casserait la suite de tests sur une base fraiche.
        $createur = DB::table('users')->min('id');

        DB::table('settings')->insert([
            'key' => CodeQrDocument::REGLAGE_ACTIF,
            'value' => '1',
            'type' => 'boolean',
            'group' => 'documents',
            'category' => 'documents',
            'default_value' => '1',
            'description' => "Imprimer un code QR sur la fiche d'inscription. Scanne, il ouvre le dossier de "
                . "l'etudiant dans l'application : utile quand la fiche revient signee. Il n'affiche rien "
                . "a qui n'est pas connecte.",
            'is_required' => 0,
            'validation_rules' => null,
            'is_active' => 1,
            'sort_order' => 170,
            'created_by' => $createur,
            'updated_by' => $createur,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->where('key', CodeQrDocument::REGLAGE_ACTIF)->delete();
    }
};
