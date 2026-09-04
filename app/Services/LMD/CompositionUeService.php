<?php

namespace App\Services\LMD;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Ecritures et lectures de la composition d'une unite d'enseignement, maquette
 * par maquette.
 *
 * Pourquoi ce service existe : les cinq ecritures du pivot `esbtp_ue_matiere`
 * passaient par la relation Eloquent, qui ne connait que le couple
 * (unite, matiere). Des lors qu'une troisieme colonne entre dans la cle,
 * `syncWithoutDetaching()` retrouve la ligne par la matiere SEULE et fait un
 * UPDATE dessus : demander un element propre a une maquette REECRIT la ligne
 * commune. Symetriquement, `detach()` supprime TOUTES les lignes de cette
 * matiere, toutes maquettes confondues. Les deux degats sont silencieux.
 *
 * Toutes les ecritures passent donc ici, explicitement sur le triplet
 * (unite, matiere, maquette).
 *
 * Regle d'exclusion mutuelle : un couple (unite, matiere) est SOIT commun a
 * toutes les maquettes, SOIT reserve a une ou plusieurs maquettes precises,
 * jamais les deux. Le moteur ne sait pas exprimer cette regle par une contrainte
 * (elle porte sur l'ensemble des lignes d'un couple, pas sur une ligne) : elle
 * est tenue ici, a l'ecriture, et doublee d'une deduplication a la lecture ou la
 * ligne reservee prime sur la commune. Sans cette seconde garde, une matiere
 * presente des deux facons compterait deux fois dans la moyenne de l'UE et
 * deux fois dans ses credits — moyenne fausse, aucune erreur.
 *
 * Les fonctions de planification sont pures : elles decrivent ce qu'il faut
 * ecrire sans toucher a la base, et c'est sur elles que portent les tests.
 */
class CompositionUeService
{
    /**
     * Une ligne qui vaut pour toutes les maquettes de l'unite.
     *
     * 0 et non NULL : voir la migration qui ajoute la colonne — une contrainte
     * d'unicite ordinaire ignore les lignes ou une colonne vaut NULL.
     */
    public const TOUTES_MAQUETTES = 0;

    /**
     * Retient une seule ligne par matiere.
     *
     * @param  list<array{matiere_id:int, parcours_id:int}>  $lignes
     * @param  int|null  $parcoursId  maquette de lecture ; null = toutes
     * @return list<array{matiere_id:int, parcours_id:int}> dans l'ordre d'entree
     */
    public static function resoudre(array $lignes, ?int $parcoursId = null): array
    {
        $retenues = [];

        foreach ($lignes as $ligne) {
            $maquette = (int) $ligne['parcours_id'];

            // Une maquette de lecture ne voit que ce qui la concerne : le commun,
            // et ce qui lui est reserve. C'est ce filtre qui empeche un element
            // reserve a Batiment d'apparaitre dans Travaux Publics.
            if ($parcoursId !== null
                && $maquette !== self::TOUTES_MAQUETTES
                && $maquette !== $parcoursId) {
                continue;
            }

            $matiereId = (int) $ligne['matiere_id'];
            $tenante = $retenues[$matiereId] ?? null;

            if ($tenante === null || self::primeSur($ligne, $tenante)) {
                $retenues[$matiereId] = $ligne;
            }
        }

        // L'ordre d'entree est celui du tri demande par l'appelant (ordre au
        // bulletin, puis intitule) : le preserver evite de re-trier partout.
        $ordre = [];
        foreach ($lignes as $ligne) {
            $matiereId = (int) $ligne['matiere_id'];
            if (isset($retenues[$matiereId]) && $retenues[$matiereId] === $ligne) {
                $ordre[] = $ligne;
                unset($retenues[$matiereId]);
            }
        }

        return $ordre;
    }

    /**
     * Ce qu'il faut ecrire pour enregistrer un element dans une unite.
     *
     * @param  list<array{parcours_id:int}>  $lignesDuCouple  lignes existantes pour CE couple (unite, matiere)
     * @param  bool  $strict  vrai pour le geste « ajouter a cette maquette uniquement » :
     *                        il refuse plutot que de toucher une ligne qu'il n'a pas visee
     * @return array{inserer: int|null, mettre_a_jour: list<int>, refus: string|null}
     */
    public static function planifierPose(array $lignesDuCouple, int $parcoursDeTravail, bool $strict = false): array
    {
        $maquettes = array_map(static fn ($ligne) => (int) $ligne['parcours_id'], $lignesDuCouple);

        // La ligne visee existe deja : on ne touche qu'elle.
        if (in_array($parcoursDeTravail, $maquettes, true)) {
            return self::plan(mettreAJour: [$parcoursDeTravail]);
        }

        $reservees = array_values(array_filter($maquettes, static fn ($m) => $m !== self::TOUTES_MAQUETTES));
        $commun = in_array(self::TOUTES_MAQUETTES, $maquettes, true);

        if ($parcoursDeTravail !== self::TOUTES_MAQUETTES && $commun) {
            // L'element est deja dans cette maquette, par le commun. Ecrire une
            // ligne reservee en plus le compterait deux fois. Depuis un ecran
            // d'edition on modifie donc la ligne commune ; le geste explicite de
            // reservation, lui, refuse et le dit.
            return $strict
                ? self::plan(refus: 'deja_commun')
                : self::plan(mettreAJour: [self::TOUTES_MAQUETTES]);
        }

        if ($parcoursDeTravail === self::TOUTES_MAQUETTES && $reservees !== []) {
            // Symetrique : poser une ligne commune sur un element deja reserve le
            // ferait compter deux fois. On met a jour les lignes qui existent, on
            // n'elargit pas la portee d'un element au passage d'un enregistrement.
            return $strict
                ? self::plan(refus: 'deja_reserve')
                : self::plan(mettreAJour: array_values(array_unique($reservees)));
        }

        return self::plan(inserer: $parcoursDeTravail);
    }

    /**
     * Ce qu'il faut ecrire pour retirer un element d'UNE maquette.
     *
     * Retirer d'une maquette un element commun ne peut pas se resumer a supprimer
     * la ligne commune : cela le retirerait aussi des autres maquettes, en
     * silence. La ligne commune est donc remplacee par autant de lignes reservees
     * qu'il reste de maquettes concernees.
     *
     * @param  list<array{parcours_id:int}>  $lignesDuCouple
     * @param  list<int>  $maquettesDeLUnite  parcours auxquels l'unite est rattachee
     * @return array{supprimer: list<int>, reserver: list<int>, reste: bool}
     */
    public static function planifierRetrait(array $lignesDuCouple, int $parcoursDeTravail, array $maquettesDeLUnite = []): array
    {
        $maquettes = array_map(static fn ($ligne) => (int) $ligne['parcours_id'], $lignesDuCouple);

        // Aucune maquette de travail : le retrait porte sur l'unite entiere,
        // c'est le comportement d'avant le partage.
        if ($parcoursDeTravail === self::TOUTES_MAQUETTES) {
            return [
                'supprimer' => array_values(array_unique($maquettes)),
                'reserver' => [],
                'reste' => false,
            ];
        }

        if (in_array($parcoursDeTravail, $maquettes, true)) {
            $restantes = array_values(array_filter($maquettes, static fn ($m) => $m !== $parcoursDeTravail));

            return [
                'supprimer' => [$parcoursDeTravail],
                'reserver' => [],
                'reste' => $restantes !== [],
            ];
        }

        if (in_array(self::TOUTES_MAQUETTES, $maquettes, true)) {
            $aReserver = array_values(array_filter(
                array_unique(array_map('intval', $maquettesDeLUnite)),
                static fn ($m) => $m !== $parcoursDeTravail && $m !== self::TOUTES_MAQUETTES
            ));

            $autresReservees = array_values(array_filter($maquettes, static fn ($m) => $m !== self::TOUTES_MAQUETTES));

            return [
                'supprimer' => [self::TOUTES_MAQUETTES],
                'reserver' => $aReserver,
                'reste' => $aReserver !== [] || $autresReservees !== [],
            ];
        }

        // L'element n'est pas dans cette maquette : rien a faire.
        return ['supprimer' => [], 'reserver' => [], 'reste' => $maquettes !== []];
    }

    /**
     * Enregistre les valeurs d'un element dans une unite, vu depuis une maquette.
     *
     * @param  array{coefficient_ecue: float|null, credit_ecue: int|null, ordre_bulletin: int}  $valeurs
     */
    public function poser(int $ueId, int $matiereId, int $parcoursDeTravail, array $valeurs): void
    {
        $this->appliquerPose($ueId, $matiereId, $parcoursDeTravail, $valeurs, strict: false);
    }

    /**
     * Ajoute un element a UNE maquette seulement.
     *
     * Refuse si l'element est deja commun a l'unite : l'y ajouter une seconde
     * fois le compterait deux fois, et le retirer du commun est un autre geste,
     * qui doit etre demande.
     */
    public function reserver(int $ueId, int $matiereId, int $parcoursId, array $valeurs): void
    {
        $this->appliquerPose($ueId, $matiereId, $parcoursId, $valeurs, strict: true);
    }

    /**
     * Retire un element d'une maquette (ou de l'unite entiere si aucune maquette
     * de travail n'est donnee).
     *
     * @return bool vrai s'il reste au moins une ligne pour ce couple — l'appelant
     *              ne doit liberer `esbtp_matieres.unite_enseignement_id` que
     *              lorsqu'il n'en reste aucune, sinon le repli par cle etrangere
     *              ressusciterait l'element dans toutes les maquettes.
     */
    public function retirer(int $ueId, int $matiereId, int $parcoursDeTravail): bool
    {
        return DB::transaction(function () use ($ueId, $matiereId, $parcoursDeTravail) {
            $lignes = $this->lignesDuCouple($ueId, $matiereId, verrouiller: true);
            $plan = self::planifierRetrait(
                $lignes,
                $parcoursDeTravail,
                $this->maquettesDeLUnite($ueId)
            );

            $parMaquette = [];
            foreach ($lignes as $ligne) {
                $parMaquette[(int) $ligne['parcours_id']] = $ligne;
            }

            foreach ($plan['reserver'] as $maquette) {
                $source = $parMaquette[self::TOUTES_MAQUETTES] ?? null;
                if ($source === null || isset($parMaquette[$maquette])) {
                    continue;
                }
                $this->inserer($ueId, $matiereId, $maquette, [
                    'coefficient_ecue' => $source['coefficient_ecue'],
                    'credit_ecue' => $source['credit_ecue'],
                    'ordre_bulletin' => $source['ordre_bulletin'],
                ]);
            }

            foreach ($plan['supprimer'] as $maquette) {
                $this->requete($ueId, $matiereId)->where('parcours_id', $maquette)->delete();
            }

            return $plan['reste'];
        });
    }

    /**
     * Lignes de composition d'une unite, telles qu'elles sont en base.
     *
     * @return list<array{matiere_id:int, parcours_id:int, coefficient_ecue: float|null, credit_ecue: int|null, ordre_bulletin: int}>
     */
    public function lignesDeLUnite(int $ueId): array
    {
        return DB::table('esbtp_ue_matiere')
            ->where('unite_enseignement_id', $ueId)
            ->orderBy('ordre_bulletin')
            ->orderBy('id')
            ->get()
            ->map(fn ($ligne) => $this->normaliser($ligne))
            ->all();
    }

    /**
     * Identifiants des matieres liees a cette unite, toutes maquettes confondues.
     *
     * Sert a faire taire le repli par cle etrangere : des que le pivot parle
     * d'une matiere dans cette unite, c'est lui qui fait foi. Sans cela, un
     * element retire d'une maquette y reviendrait par la cle etrangere.
     *
     * @return list<int>
     */
    public function matieresLiees(int $ueId): array
    {
        return DB::table('esbtp_ue_matiere')
            ->where('unite_enseignement_id', $ueId)
            ->pluck('matiere_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Maquettes auxquelles une unite est rattachee (pivot esbtp_lmd_parcours_ue).
     *
     * @return list<int>
     */
    public function maquettesDeLUnite(int $ueId): array
    {
        return DB::table('esbtp_lmd_parcours_ue')
            ->where('unite_enseignement_id', $ueId)
            ->pluck('parcours_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function appliquerPose(int $ueId, int $matiereId, int $parcoursDeTravail, array $valeurs, bool $strict): void
    {
        DB::transaction(function () use ($ueId, $matiereId, $parcoursDeTravail, $valeurs, $strict) {
            // Verrou sur les lignes du couple : deux enregistrements concurrents
            // calculeraient sinon leur plan sur le meme etat, et tous deux
            // insereraient — violation de l'unicite, ou doublon si elle manque.
            $lignes = $this->lignesDuCouple($ueId, $matiereId, verrouiller: true);
            $plan = self::planifierPose($lignes, $parcoursDeTravail, $strict);

            if ($plan['refus'] !== null) {
                throw ValidationException::withMessages([
                    'ecues' => [$this->messageDeRefus($plan['refus'])],
                ]);
            }

            foreach ($plan['mettre_a_jour'] as $maquette) {
                $this->requete($ueId, $matiereId)
                    ->where('parcours_id', $maquette)
                    ->update($this->colonnes($valeurs) + ['updated_at' => now()]);
            }

            if ($plan['inserer'] !== null) {
                $this->inserer($ueId, $matiereId, $plan['inserer'], $valeurs);
            }
        });
    }

    private function inserer(int $ueId, int $matiereId, int $parcoursId, array $valeurs): void
    {
        DB::table('esbtp_ue_matiere')->insert($this->colonnes($valeurs) + [
            'unite_enseignement_id' => $ueId,
            'matiere_id' => $matiereId,
            'parcours_id' => $parcoursId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return list<array{parcours_id:int, coefficient_ecue: float|null, credit_ecue: int|null, ordre_bulletin: int}>
     */
    private function lignesDuCouple(int $ueId, int $matiereId, bool $verrouiller = false): array
    {
        $requete = $this->requete($ueId, $matiereId)->orderBy('id');
        if ($verrouiller) {
            $requete->lockForUpdate();
        }

        return $requete->get()->map(fn ($ligne) => $this->normaliser($ligne))->all();
    }

    private function requete(int $ueId, int $matiereId)
    {
        return DB::table('esbtp_ue_matiere')
            ->where('unite_enseignement_id', $ueId)
            ->where('matiere_id', $matiereId);
    }

    private function normaliser(object $ligne): array
    {
        return [
            'matiere_id' => (int) $ligne->matiere_id,
            'parcours_id' => (int) ($ligne->parcours_id ?? self::TOUTES_MAQUETTES),
            'coefficient_ecue' => $ligne->coefficient_ecue !== null ? (float) $ligne->coefficient_ecue : null,
            'credit_ecue' => $ligne->credit_ecue !== null ? (int) $ligne->credit_ecue : null,
            'ordre_bulletin' => (int) ($ligne->ordre_bulletin ?? 0),
        ];
    }

    private function colonnes(array $valeurs): array
    {
        return [
            'coefficient_ecue' => $valeurs['coefficient_ecue'] ?? null,
            'credit_ecue' => $valeurs['credit_ecue'] ?? null,
            'ordre_bulletin' => (int) ($valeurs['ordre_bulletin'] ?? 0),
        ];
    }

    private function messageDeRefus(string $motif): string
    {
        return $motif === 'deja_commun'
            ? "Cet élément est déjà présent dans toutes les maquettes de cette unité. Retirez-le d'abord des autres maquettes si vous voulez le réserver à celle-ci."
            : "Cet élément est déjà réservé à une ou plusieurs maquettes de cette unité. Modifiez-le depuis la maquette concernée.";
    }

    /**
     * @return array{inserer: int|null, mettre_a_jour: list<int>, refus: string|null}
     */
    private static function plan(?int $inserer = null, array $mettreAJour = [], ?string $refus = null): array
    {
        return ['inserer' => $inserer, 'mettre_a_jour' => $mettreAJour, 'refus' => $refus];
    }

    private static function primeSur(array $candidate, array $tenante): bool
    {
        $c = (int) $candidate['parcours_id'];
        $t = (int) $tenante['parcours_id'];

        if ($c === $t) {
            return false;
        }

        // Une ligne reservee prime sur la commune : elle porte le coefficient et
        // le credit voulus PAR cette maquette.
        if ($t === self::TOUTES_MAQUETTES) {
            return true;
        }

        if ($c === self::TOUTES_MAQUETTES) {
            return false;
        }

        // Deux reservations concurrentes : cas d'une lecture sans maquette de
        // travail. On retient la plus petite pour que la lecture soit stable.
        return $c < $t;
    }
}
