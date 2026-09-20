<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Les libelles d'usine DOMAINE / MENTION / PARCOURS masquer le vocabulaire
 * de l'etablissement. Vidés, le bulletin lit le rang (Composante, UFR...).
 * Un libelle que l'ecole a personnalise n'est pas touche.
 */
return new class extends Migration
{
    public function up(): void
    {
        $usine = [
            'lmd_bulletin_label_domaine' => 'DOMAINE',
            'lmd_bulletin_label_mention' => 'MENTION',
            'lmd_bulletin_label_parcours' => 'PARCOURS',
        ];

        foreach ($usine as $cle => $valeur) {
            DB::table('settings')
                ->where('key', $cle)
                ->whereRaw('UPPER(TRIM(value)) = ?', [$valeur])
                ->update(['value' => '', 'default_value' => '']);
        }
    }

    public function down(): void
    {
        $usine = [
            'lmd_bulletin_label_domaine' => 'DOMAINE',
            'lmd_bulletin_label_mention' => 'MENTION',
            'lmd_bulletin_label_parcours' => 'PARCOURS',
        ];

        foreach ($usine as $cle => $valeur) {
            DB::table('settings')
                ->where('key', $cle)
                ->where(fn ($q) => $q->whereNull('value')->orWhere('value', ''))
                ->update(['value' => $valeur, 'default_value' => $valeur]);
        }
    }
};
