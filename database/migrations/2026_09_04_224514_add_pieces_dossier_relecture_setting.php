<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Le sixième réglage : cocher, est-ce déjà valider ?
 *
 * La question n'a pas la même réponse partout, et elle décide de ce que vaut le
 * geste de guichet.
 *
 * Dans une petite école, la personne qui reçoit l'extrait de naissance est
 * exactement celle qui juge s'il est recevable. Lui demander de cocher « remise »
 * puis d'attendre qu'un second la valide, c'est inventer une file d'attente
 * là où il n'y a qu'une personne : le dossier ne se solderait jamais, et
 * l'écran signalerait indéfiniment un manque que personne ne vient lever.
 * C'est le défaut : UN GESTE SUFFIT.
 *
 * Dans une école qui sépare le guichet du contrôle — un agent reçoit, un
 * responsable relit — la même case doit au contraire ne rien conclure : elle dit
 * « remise », et la validation reste un acte distinct, avec son auteur et son
 * horodatage. C'est ce que ce réglage ouvre.
 *
 * Ce n'est PAS une question de sévérité, c'est une question d'organigramme. Une
 * école n'est pas plus rigoureuse parce qu'elle exige deux gestes d'une seule
 * personne : elle est seulement plus lente.
 */
return new class extends Migration
{
    private const CLE = 'pieces_dossier.relecture';

    public function up(): void
    {
        if (DB::table('settings')->where('key', self::CLE)->exists()) {
            return;
        }

        // settings.created_by porte une cle etrangere vers users en ON DELETE
        // SET NULL : coder « 1 » en dur casserait la migration sur une base
        // fraiche, ou aucun utilisateur n'existe encore.
        $createur = DB::table('users')->min('id');
        $maintenant = now();

        DB::table('settings')->insert([
            'key' => self::CLE,
            'value' => '0',
            'type' => 'boolean',
            'group' => 'scolarite',
            'category' => 'scolarite',
            'default_value' => '0',
            'description' => "Une piece cochee au guichet doit-elle etre relue par quelqu'un d'autre avant de compter ? Non : cocher vaut validation, un seul geste, c'est le cas des ecoles ou la personne qui recoit la piece est celle qui la juge. Oui : cocher marque « remise », et la validation reste un acte distinct, avec son auteur. Defaut : non.",
            'is_required' => 0,
            'validation_rules' => null,
            'is_active' => 1,
            'sort_order' => 175,
            'created_by' => $createur,
            'updated_by' => $createur,
            'created_at' => $maintenant,
            'updated_at' => $maintenant,
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->where('key', self::CLE)->delete();
    }
};
