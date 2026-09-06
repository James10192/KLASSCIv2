<?php

namespace App\Services\Maintenance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Repare les libelles ecrits en UTF-8 puis relus comme du latin-1.
 *
 * Le symptome est toujours le meme : « Geologie » ressort « GÃ©ologie ». Les deux
 * octets de l'accent ont ete pris pour deux caracteres, et chacun a ete re-encode.
 * L'operation inverse est exacte : on relit les caracteres comme des octets, et on
 * decode ces octets en UTF-8.
 *
 * Elle n'est tentee que sous trois conditions, et c'est ce qui la rend sure :
 *
 *  - tous les points de code tiennent sur un octet, sinon la conversion perdrait
 *    de l'information au lieu de la retrouver ;
 *  - les octets obtenus forment de l'UTF-8 valide ;
 *  - le resultat porte au moins une lettre accentuee.
 *
 * Un libelle deja sain echoue a la deuxieme condition : « Geologie » relu en octets
 * donne « G\xe9ologie », qui n'est pas de l'UTF-8 valide. La reparation est donc
 * idempotente, et la relancer ne peut pas degrader ce qu'elle a deja corrige.
 */
class ReparateurEncodage
{
    /**
     * Les colonnes de LIBELLE, et rien d'autre.
     *
     * Aucune colonne de montant, d'identifiant, de code ou de mot de passe : une
     * reparation d'encodage n'a rien a faire ailleurs que dans du texte destine a
     * etre lu. Les tables absentes d'une instance sont ignorees.
     *
     * @var array<string, list<string>>
     */
    public const COLONNES = [
        'esbtp_classes' => ['name', 'description'],
        'esbtp_filieres' => ['name', 'description'],
        'esbtp_niveau_etudes' => ['name'],
        'esbtp_lmd_domaines' => ['name'],
        'esbtp_lmd_mentions' => ['name'],
        'esbtp_lmd_parcours' => ['name'],
        'esbtp_unites_enseignement' => ['name'],
        'esbtp_matieres' => ['name'],
        'esbtp_etudiants' => ['nom', 'prenoms', 'lieu_naissance'],
    ];

    /**
     * Releve ce qui est abime. N'ecrit rien.
     *
     * @return list<array{table: string, colonne: string, id: int, avant: string, apres: string}>
     */
    public function releve(): array
    {
        $trouve = [];

        foreach (self::COLONNES as $table => $colonnes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $presentes = array_values(array_filter(
                $colonnes,
                fn (string $c) => Schema::hasColumn($table, $c)
            ));

            if ($presentes === []) {
                continue;
            }

            DB::table($table)
                ->select(array_merge(['id'], $presentes))
                ->orderBy('id')
                ->chunk(500, function ($lignes) use ($table, $presentes, &$trouve) {
                    foreach ($lignes as $ligne) {
                        foreach ($presentes as $colonne) {
                            $valeur = $ligne->{$colonne} ?? null;
                            if (! is_string($valeur)) {
                                continue;
                            }

                            $repare = $this->reparer($valeur);
                            if ($repare === null) {
                                continue;
                            }

                            $trouve[] = [
                                'table' => $table,
                                'colonne' => $colonne,
                                'id' => (int) $ligne->id,
                                'avant' => $valeur,
                                'apres' => $repare,
                            ];
                        }
                    }
                });
        }

        return $trouve;
    }

    /**
     * Ecrit les reparations relevees, une ligne a la fois.
     *
     * Pas de mise a jour en masse : chaque ligne porte sa propre valeur, et une
     * ecriture ciblee par identifiant ne peut pas deborder sur une voisine.
     *
     * @param  list<array{table: string, colonne: string, id: int, avant: string, apres: string}>  $reparations
     */
    public function appliquer(array $reparations): int
    {
        $ecrites = 0;

        DB::transaction(function () use ($reparations, &$ecrites) {
            foreach ($reparations as $r) {
                $ecrites += DB::table($r['table'])
                    ->where('id', $r['id'])
                    ->where($r['colonne'], $r['avant']) // refuse d'ecraser une valeur modifiee entre-temps
                    ->update([$r['colonne'] => $r['apres']]);
            }
        });

        return $ecrites;
    }

    /**
     * La valeur reparee, ou null si la chaine n'est pas doublement encodee.
     */
    public function reparer(string $valeur): ?string
    {
        if ($valeur === '' || ! mb_check_encoding($valeur, 'UTF-8')) {
            return null;
        }

        // Un point de code au-dela de 255 ne tient pas sur un octet : la conversion
        // le remplacerait par « ? ». On ne touche pas a ces chaines.
        $caracteres = preg_split('//u', $valeur, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($caracteres as $caractere) {
            if (mb_ord($caractere, 'UTF-8') > 255) {
                return null;
            }
        }

        $octets = mb_convert_encoding($valeur, 'ISO-8859-1', 'UTF-8');

        if (! is_string($octets) || $octets === $valeur || ! mb_check_encoding($octets, 'UTF-8')) {
            return null;
        }

        // Sans lettre accentuee, rien ne prouve qu'on a retrouve quelque chose :
        // on refuse plutot que de reecrire au jugé.
        if (! preg_match('/[\x{00C0}-\x{00FF}\x{0152}\x{0153}]/u', $octets)) {
            return null;
        }

        return $octets;
    }
}
