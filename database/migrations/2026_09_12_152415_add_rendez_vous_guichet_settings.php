<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Les reglages du guichet d'inscription.
 *
 * Deposer en ligne ne finit rien : les pieces et le paiement se remettent sur
 * place. Sans creneau, les huit cents familles d'une rentree se presentent le
 * meme matin. L'ecole decrit ici sa journee de reception ; la prise de
 * rendez-vous elle-meme viendra avec l'ecran qui la sert.
 *
 * Le PREMIER jour n'est pas ici : c'est `inscriptions.physiques.debut`, que le
 * portail publie deja pour repondre a « je viens quand ? ». Deux dates pour une
 * seule rentree finiraient par diverger, et la famille lirait deux instructions
 * contradictoires sur le meme ecran.
 *
 * Le nombre de personnes recues dans la journee n'est pas ici non plus, et
 * c'est la decision de fond : les heures et la duree le determinent deja. Ce
 * que l'ecole declare, c'est `capacite_par_creneau` — combien de familles a la
 * fois — et son nombre par jour lui est RENDU, calcule sous les champs. Une
 * ecole qui annoncait « soixante par jour » sur une plage qui n'en produit que
 * vingt-huit ne se trompait pas : elle decrivait plusieurs guichets en
 * parallele sans le dire.
 */
return new class extends Migration
{
    public function up(): void
    {
        $lignes = $this->reglages();

        $existants = DB::table('settings')
            ->whereIn('key', array_column($lignes, 'key'))
            ->pluck('key')
            ->all();

        $aInserer = array_values(array_filter(
            $lignes,
            fn ($ligne) => ! in_array($ligne['key'], $existants, true)
        ));

        if ($aInserer !== []) {
            DB::table('settings')->insert($aInserer);
        }

        $this->purgerLeCache($lignes);
    }

    public function down(): void
    {
        $lignes = $this->reglages();

        DB::table('settings')->whereIn('key', array_column($lignes, 'key'))->delete();

        $this->purgerLeCache($lignes);
    }

    /**
     * Setting::get met en cache une heure, y compris l'ABSENCE d'une ligne.
     *
     * Ces insertions passent a cote d'Eloquent, donc a cote du crochet qui
     * invalide. Sans cette purge, une lecture faite entre le deploiement du
     * code et cette migration figerait « pas de reglage » pour une heure :
     * l'ecole ouvrirait la page, verrait des champs vides et quatre reproches
     * (« Renseignez l'heure d'ouverture… ») alors que les lignes existent en
     * base, et ressaisirait par-dessus ce qui venait d'etre seme.
     *
     * @param  array<int, array<string, mixed>>  $lignes
     */
    private function purgerLeCache(array $lignes): void
    {
        foreach (array_column($lignes, 'key') as $cle) {
            Cache::forget("setting_{$cle}");
        }

        // Setting::getGroup a son propre cache, sur la meme duree.
        Cache::forget('settings_group_scolarite');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function reglages(): array
    {
        // Premier utilisateur existant, null sur une base vide : la cle
        // etrangere settings.created_by est ON DELETE SET NULL. Coder « 1 » en
        // dur casserait toute la suite de tests sur une base fraiche.
        $createur = DB::table('users')->min('id');

        $commun = [
            'group' => 'scolarite',
            'category' => 'scolarite',
            'is_required' => 0,
            'validation_rules' => null,
            'is_active' => 1,
            'created_by' => $createur,
            'updated_by' => $createur,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return [
            $commun + [
                'key' => 'inscriptions.rdv.enabled',
                'value' => '0',
                'type' => 'boolean',
                'default_value' => '0',
                'description' => "Propose un rendez-vous aux familles apres leur depot en ligne, pour etaler la file au guichet. Desactive par defaut.",
                'sort_order' => 163,
            ],
            $commun + [
                'key' => 'inscriptions.rdv.dernier_jour',
                'value' => '',
                'type' => 'string',
                'default_value' => '',
                'description' => "Dernier jour de reception au guichet (AAAA-MM-JJ). Le premier jour est celui des inscriptions physiques. Sans cette date, aucun rendez-vous n'est propose.",
                'sort_order' => 164,
            ],
            $commun + [
                'key' => 'inscriptions.rdv.jours_ouverts',
                'value' => '1,2,3,4,5',
                'type' => 'string',
                'default_value' => '1,2,3,4,5',
                'description' => "Jours de la semaine ou le guichet recoit : 1 pour lundi jusqu'a 7 pour dimanche, separes par des virgules.",
                'sort_order' => 165,
            ],
            $commun + [
                'key' => 'inscriptions.rdv.heure_ouverture',
                'value' => '08:00',
                'type' => 'string',
                'default_value' => '08:00',
                'description' => "Heure a laquelle le guichet commence a recevoir (HH:MM).",
                'sort_order' => 166,
            ],
            $commun + [
                'key' => 'inscriptions.rdv.heure_fermeture',
                'value' => '16:00',
                'type' => 'string',
                'default_value' => '16:00',
                'description' => "Heure a laquelle le guichet cesse de recevoir (HH:MM).",
                'sort_order' => 167,
            ],
            $commun + [
                'key' => 'inscriptions.rdv.pause_debut',
                'value' => '12:00',
                'type' => 'string',
                'default_value' => '12:00',
                'description' => "Debut de la pause (HH:MM). Laisser vide, avec la fin, pour une journee continue.",
                'sort_order' => 168,
            ],
            $commun + [
                'key' => 'inscriptions.rdv.pause_fin',
                'value' => '13:00',
                'type' => 'string',
                'default_value' => '13:00',
                'description' => "Fin de la pause (HH:MM). Laisser vide, avec le debut, pour une journee continue.",
                'sort_order' => 169,
            ],
            $commun + [
                'key' => 'inscriptions.rdv.duree_minutes',
                'value' => '15',
                'type' => 'integer',
                'default_value' => '15',
                'description' => "Temps approximatif passe avec une famille, en minutes. Determine le nombre de creneaux de la journee.",
                'sort_order' => 170,
            ],
            $commun + [
                'key' => 'inscriptions.rdv.capacite_par_creneau',
                'value' => '1',
                'type' => 'integer',
                'default_value' => '1',
                'description' => "Combien de familles peuvent etre recues en meme temps, autrement dit combien de guichets sont tenus en parallele. Le nombre de personnes recues dans la journee en decoule et s'affiche sous les champs.",
                'sort_order' => 171,
            ],
        ];
    }
};
