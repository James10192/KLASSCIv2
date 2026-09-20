<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * La plage horaire d'une journee de cours : premiere et derniere heure.
 *
 * Grilles de disponibilite, saisie des seances et emploi du temps etaient
 * bornes en dur a 18h. Une ecole qui donne des cours du soir (18h-22h) ne
 * pouvait ni les saisir dans la fenetre ni voir ses enseignants disponibles.
 *
 * Defauts : 7h et 18h, les bornes qu'utilisaient deja la saisie des seances et
 * le generateur d'emploi du temps. Aucune ecole ne perd de creneau ; les grilles
 * qui partaient de 8h gagnent la ligne de 7h, ou une seance de 7h30 etait
 * jusqu'ici invisible. Lu par App\Services\Planning\PlageHoraireJournee.
 */
return new class extends Migration
{
    private const REGLAGES = [
        'planning.heure_debut' => [
            'valeur' => '7',
            'ordre' => 210,
            'description' => "Heure du premier créneau de cours de la journée (0 à 23). Les grilles de disponibilité des enseignants, la saisie des séances et l'emploi du temps commencent à cette heure. Défaut : 7.",
        ],
        'planning.heure_fin' => [
            'valeur' => '18',
            'ordre' => 211,
            'description' => "Heure à laquelle se termine le dernier créneau de cours (1 à 23). Une école qui donne des cours du soir jusqu'à 22h pose 22 : les grilles et la saisie des séances s'étendent d'autant. Doit être supérieure à l'heure de début, sinon la plage par défaut s'applique. Défaut : 18.",
        ],
    ];

    public function up(): void
    {
        // settings.created_by porte une cle etrangere vers users : « 1 » en dur
        // casserait la migration sur une base fraiche, sans utilisateur.
        $createur = DB::table('users')->min('id');
        $maintenant = now();

        foreach (self::REGLAGES as $cle => $reglage) {
            if (DB::table('settings')->where('key', $cle)->exists()) {
                continue;
            }

            DB::table('settings')->insert([
                'key' => $cle,
                'value' => $reglage['valeur'],
                'type' => 'integer',
                'group' => 'academique',
                'category' => 'academique',
                'default_value' => $reglage['valeur'],
                'description' => $reglage['description'],
                'is_required' => 0,
                // Colonne JSON (CHECK json_valid sur MariaDB) : un tableau encode,
                // jamais une chaine de regles.
                'validation_rules' => json_encode(['nullable', 'integer', 'min:0', 'max:23']),
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
