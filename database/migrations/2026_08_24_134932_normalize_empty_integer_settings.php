<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Les settings de type integer qui portent '' (graines defectueuses) rendaient
 * la page de parametrage inutilisable : le formulaire affiche la valeur CASTEE
 * (« 0 »), le navigateur renvoie « 0 », la garde d'idempotence compare au brut
 * '' -- jamais egaux -- et la validation `min:` rejetait alors une valeur que
 * l'utilisateur n'avait pas touchee. CHAQUE enregistrement echouait, quel que
 * soit le champ modifie. Constate sur les cinq reglages PDF de presentation.
 *
 * On repare la DONNEE plutot que de comparer au caste dans le controleur : une
 * premiere tentative en ce sens plantait les types json (castValue y rend un
 * tableau) et transformait le 422 en rollback muet.
 *
 * '0' et non la valeur par defaut metier : l'ecran affichait deja 0, la
 * migration fige ce que l'ecole voyait, elle n'invente rien. Les minimums de
 * validation s'appliqueront a la prochaine modification volontaire.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')
            ->where('type', 'integer')
            ->where(fn ($q) => $q->whereNull('value')->orWhere('value', ''))
            ->update(['value' => '0']);
    }

    public function down(): void
    {
        // Rien a defaire : on ne sait plus quelles lignes etaient vides, et
        // les remettre a vide recreerait la panne.
    }
};
