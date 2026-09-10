<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const REGLAGES = [
        [
            'key' => 'inscriptions.rdv.enabled',
            'value' => '0',
            'type' => 'boolean',
            'default_value' => '0',
            'sort_order' => 180,
            'description' => "Ouvre la prise de rendez-vous pour les inscriptions sur place. Independant des deux canaux en ligne. Desactive par defaut.",
        ],
        [
            'key' => 'inscriptions.rdv.obligatoire',
            'value' => '0',
            'type' => 'boolean',
            'default_value' => '0',
            'sort_order' => 181,
            'description' => "Un rendez-vous est-il exigé pour se presenter au guichet ? Non par defaut : les familles sans creneau restent recues dans les trous.",
        ],
        [
            'key' => 'inscriptions.rdv.ouverture',
            'value' => '',
            'type' => 'string',
            'default_value' => '',
            'sort_order' => 182,
            'description' => "Premier jour ou l'on peut prendre rendez-vous (AAAA-MM-JJ). Distinct de la fenetre de depot en ligne. Vide = generation refusee.",
        ],
        [
            'key' => 'inscriptions.rdv.fermeture',
            'value' => '',
            'type' => 'string',
            'default_value' => '',
            'sort_order' => 183,
            'description' => "Dernier jour ou l'on peut prendre rendez-vous (AAAA-MM-JJ). Vide = generation refusee.",
        ],
        [
            'key' => 'inscriptions.rdv.jours_ouverts',
            'value' => '1,2,3,4,5',
            'type' => 'string',
            'default_value' => '1,2,3,4,5',
            'sort_order' => 184,
            'description' => "Jours ouverts, ISO 1=lundi ... 7=dimanche, separes par des virgules. Defaut : lundi a vendredi.",
        ],
        [
            'key' => 'inscriptions.rdv.heure_debut',
            'value' => '',
            'type' => 'string',
            'default_value' => '',
            'sort_order' => 185,
            'description' => "Heure d'ouverture du guichet (HH:MM). Vide = generation refusee.",
        ],
        [
            'key' => 'inscriptions.rdv.heure_fin',
            'value' => '',
            'type' => 'string',
            'default_value' => '',
            'sort_order' => 186,
            'description' => "Heure de fermeture du guichet (HH:MM). Vide = generation refusee.",
        ],
        [
            'key' => 'inscriptions.rdv.pause_debut',
            'value' => '',
            'type' => 'string',
            'default_value' => '',
            'sort_order' => 187,
            'description' => "Debut de la pause (HH:MM). Vide = pas de pause. Les deux bornes de pause vont ensemble.",
        ],
        [
            'key' => 'inscriptions.rdv.pause_fin',
            'value' => '',
            'type' => 'string',
            'default_value' => '',
            'sort_order' => 188,
            'description' => "Fin de la pause (HH:MM). Vide = pas de pause.",
        ],
        [
            'key' => 'inscriptions.rdv.duree_minutes',
            'value' => '30',
            'type' => 'integer',
            'default_value' => '30',
            'sort_order' => 189,
            'description' => "Duree d'un creneau, en minutes.",
        ],
        [
            'key' => 'inscriptions.rdv.capacite',
            'value' => '10',
            'type' => 'integer',
            'default_value' => '10',
            'sort_order' => 190,
            'description' => "Nombre de familles recues par creneau.",
        ],
        [
            'key' => 'inscriptions.rdv.delai_min_heures',
            'value' => '12',
            'type' => 'integer',
            'default_value' => '12',
            'sort_order' => 191,
            'description' => "Delai minimum entre la reservation et le creneau, en heures.",
        ],
        [
            'key' => 'inscriptions.rdv.delai_modif_heures',
            'value' => '12',
            'type' => 'integer',
            'default_value' => '12',
            'sort_order' => 192,
            'description' => "Jusqu'a combien d'heures avant le creneau une famille peut deplacer ou annuler.",
        ],
        [
            'key' => 'inscriptions.rdv.grace_no_show_minutes',
            'value' => '15',
            'type' => 'integer',
            'default_value' => '15',
            'sort_order' => 193,
            'description' => "Delai de grace, en minutes apres le debut du creneau, avant qu'une absence libere la place.",
        ],
    ];

    public function up(): void
    {
        $createur = DB::table('users')->min('id');
        $maintenant = now();

        foreach (self::REGLAGES as $reglage) {
            if (DB::table('settings')->where('key', $reglage['key'])->exists()) {
                continue;
            }

            DB::table('settings')->insert([
                'key' => $reglage['key'],
                'value' => $reglage['value'],
                'type' => $reglage['type'],
                'group' => 'scolarite',
                'category' => 'scolarite',
                'default_value' => $reglage['default_value'],
                'description' => $reglage['description'],
                'is_required' => 0,
                'validation_rules' => null,
                'is_active' => 1,
                'sort_order' => $reglage['sort_order'],
                'created_by' => $createur,
                'updated_by' => $createur,
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
