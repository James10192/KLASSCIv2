<?php

namespace App\Services\Frais;

use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPPaiementAllocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Repartit un versement sur les frais qu'il couvre reellement.
 *
 * Un paiement ne portait qu'une seule categorie. L'etudiant qui reglait
 * plusieurs frais d'un geste voyait donc tout atterrir sur celle que le caissier
 * avait choisie — et le calcul du restant, qui fait `max(0, du - paye)` par
 * categorie, ECRETAIT l'excedent. Sur ISLG, 105 000 F d'un etudiant sont ainsi
 * devenus invisibles : ni imputes, ni signales.
 *
 * On ne cree AUCUN paiement : on dit seulement ou l'argent deja encaisse est
 * alle. Le frais que le caissier avait designe est servi en premier — c'est
 * l'intention explicite du versement — puis les autres dans l'ordre ou ils sont
 * dus. On n'alloue jamais plus que ce qu'un frais reclame.
 *
 * Quand tous les frais sont soldes et qu'il reste de l'argent, le surplus
 * demeure sur la categorie d'origine : c'est une avance, elle appartient a
 * l'etudiant et n'a pas a etre repartie sur des dettes qui n'existent pas.
 */
class RepartitionTropPercu
{
    /**
     * @param  bool  $reinitialiser  Repart de zero : oublie les allocations deja
     *                               ecrites sur le perimetre et recalcule tout.
     *                               A utiliser quand l'ordre de service a change
     *                               — sans lui, les versements deja repartis sont
     *                               ignores et la nouvelle priorite reste lettre morte.
     * @return array{inscriptions: int, paiements: int, allocations: int, effacees: int, lignes: array, applique: bool, reinitialise: bool}
     */
    public function executer(
        bool $appliquer = false,
        ?int $inscriptionId = null,
        ?int $anneeId = null,
        bool $reinitialiser = false
    ): array {
        $inscriptions = ESBTPInscription::query()
            ->when($inscriptionId, fn ($q) => $q->where('id', $inscriptionId))
            ->when($anneeId, fn ($q) => $q->where('annee_universitaire_id', $anneeId))
            // Les inscriptions VIVANTES, pas seulement les validees.
            // Le filtre ne retenait que « active » et sautait les inscriptions en
            // attente. Sur ISLG, c'est 7 inscriptions sur 8 : l'argent y est deja
            // encaisse et mal impute, la validation n'y change rien.
            ->when(! $inscriptionId, fn ($q) => $q->whereIn('status', ['active', 'en_attente']))
            ->with('etudiant')
            ->get();

        $lignes = [];
        $aEcrire = [];

        foreach ($inscriptions as $inscription) {
            foreach ($this->planifier($inscription, $reinitialiser) as $entree) {
                $lignes[] = $entree['ligne'];

                foreach ($entree['allocations'] as $allocation) {
                    $aEcrire[] = $allocation;
                }
            }
        }

        $aEffacer = $reinitialiser
            ? $this->allocationsDuPerimetre($inscriptions->pluck('id')->all())->count()
            : 0;

        // Sans reinitialisation, il n'y a rien a faire quand rien n'est a ecrire.
        // AVEC, il reste peut-etre des allocations a effacer : une repartition qui
        // ne dit plus rien de plus que le paiement doit rendre celui-ci a sa
        // categorie d'origine, donc effacer ce qui avait ete ecrit.
        if (! $appliquer || ($aEcrire === [] && $aEffacer === 0)) {
            return $this->resultat($lignes, count($aEcrire), false, $aEffacer, $reinitialiser);
        }

        $idsInscriptions = $inscriptions->pluck('id')->all();

        DB::transaction(function () use ($aEcrire, $reinitialiser, $idsInscriptions): void {
            if ($reinitialiser) {
                $this->allocationsDuPerimetre($idsInscriptions)->delete();
            }

            foreach ($aEcrire as $a) {
                ESBTPPaiementAllocation::updateOrCreate(
                    ['paiement_id' => $a['paiement_id'], 'frais_category_id' => $a['frais_category_id']],
                    ['montant' => $a['montant']]
                );
            }
        });

        Log::warning('[frais] repartition de versements sur plusieurs frais', [
            'allocations' => count($aEcrire),
            'effacees' => $aEffacer,
            'reinitialise' => $reinitialiser,
            'inscription_id' => $inscriptionId,
            'annee_id' => $anneeId,
        ]);

        return $this->resultat($lignes, count($aEcrire), true, $aEffacer, $reinitialiser);
    }

    /**
     * Les allocations posees sur le perimetre traite.
     *
     * Exactement l'ensemble que ce service sait produire — les versements
     * VALIDES et ENCAISSES des inscriptions retenues. On n'efface jamais une
     * allocation portee par un avoir ou un paiement rejete : ce service ne les
     * a pas ecrites, il ne saurait pas les reecrire.
     *
     * @param  array<int, int>  $inscriptionIds
     */
    private function allocationsDuPerimetre(array $inscriptionIds): \Illuminate\Database\Eloquent\Builder
    {
        return ESBTPPaiementAllocation::query()
            ->whereIn('paiement_id', ESBTPPaiement::query()
                ->whereIn('inscription_id', $inscriptionIds)
                ->valides()
                ->encaissements()
                ->horsReliquat()
                ->select('id'));
    }

    /**
     * @param  array<int, array>  $lignes
     */
    private function resultat(
        array $lignes,
        int $allocations,
        bool $applique,
        int $effacees = 0,
        bool $reinitialise = false
    ): array {
        return [
            'inscriptions' => count(array_unique(array_column($lignes, 'inscription_id'))),
            'paiements' => count($lignes),
            'allocations' => $allocations,
            'effacees' => $effacees,
            'lignes' => $lignes,
            'applique' => $applique,
            'reinitialise' => $reinitialise,
        ];
    }

    /**
     * Ce qu'il faudrait ecrire pour cette inscription, sans rien ecrire.
     *
     * @return array<int, array{ligne: array, allocations: array}>
     */
    private function planifier(ESBTPInscription $inscription, bool $reinitialiser = false): array
    {
        $paiements = ESBTPPaiement::query()
            ->where('inscription_id', $inscription->id)
            ->valides()
            ->encaissements()
            // Le meme perimetre que le lecteur : un reliquat eteint une dette
            // d'une annee anterieure, pas un frais de l'annee en cours.
            //
            // Sans ce filtre, un reliquat devenait candidat : il consommait du
            // reste en memoire et recevait une allocation que netPaidByCategory()
            // jetait ensuite — son `paiement_id` n'entre pas dans son perimetre.
            // Le frais qu'il avait « couvert » etait donc rendu indisponible au
            // versement reel qui suivait, lequel partait sur un autre frais.
            // Ecrire et lire doivent voir exactement les memes versements.
            ->horsReliquat()
            // On repart de zero : les versements deja repartis redeviennent des
            // candidats, sinon changer l'ordre de service ne changerait rien.
            ->when(! $reinitialiser, fn ($q) => $q->whereDoesntHave('allocations'))
            ->orderBy('date_paiement')
            ->orderBy('id')
            ->get();

        if ($paiements->isEmpty()) {
            return [];
        }

        $reste = $this->resteParCategorie($inscription, $reinitialiser);

        if ($reste === []) {
            return [];
        }

        $sorties = [];

        foreach ($paiements as $paiement) {
            $allocations = $this->repartirUnVersement($paiement, $reste);

            if ($allocations === []) {
                continue;
            }

            $etudiant = $inscription->etudiant;

            $sorties[] = [
                'ligne' => [
                    'inscription_id' => $inscription->id,
                    'paiement_id' => $paiement->id,
                    'numero_recu' => $paiement->numero_recu,
                    'etudiant' => $etudiant ? trim(($etudiant->nom ?? '').' '.($etudiant->prenoms ?? '')) : null,
                    'matricule' => $etudiant->matricule ?? null,
                    'montant' => (float) $paiement->montant,
                    'reparti_sur' => count($allocations),
                    'details' => $allocations,
                ],
                'allocations' => $allocations,
            ];
        }

        return $sorties;
    }

    /**
     * Ce que chaque frais reclame encore, une fois deduit ce qui lui est deja
     * alloue par des versements anterieurs.
     *
     * @return array<int, float>
     */
    private function resteParCategorie(ESBTPInscription $inscription, bool $reinitialiser = false): array
    {
        // En reinitialisation, ces allocations vont etre effacees : les deduire
        // reviendrait a compter deux fois l'argent qu'on est en train de reimputer.
        $dejaAlloue = $reinitialiser
            ? collect()
            : ESBTPPaiementAllocation::query()
                ->whereIn('paiement_id', ESBTPPaiement::query()
                    ->where('inscription_id', $inscription->id)
                    ->valides()
                    ->encaissements()
                    ->horsReliquat()
                    ->select('id'))
                ->groupBy('frais_category_id')
                ->selectRaw('frais_category_id, SUM(montant) as total')
                ->pluck('total', 'frais_category_id');

        // L'ordre de service est celui que L'ECOLE a choisi.
        //
        // `sort_order` est la colonne par laquelle elle range ses categories de
        // frais, et c'est deja l'ordre dans lequel l'ecran d'encaissement les
        // presente.
        //
        // On ne code donc aucune priorite ici. Une ecole qui veut solder la
        // tenue avant la scolarite n'a qu'a reordonner ses categories (endpoint
        // `frais/ordonner-categories`) puis rejouer la repartition avec `reset` ;
        // ecrire « inscription puis tenue » en dur imposerait la reponse d'un
        // etablissement a tous les autres.
        //
        // Les frais deposes en nature sortent d'eux-memes : chargedAmount() rend
        // zero pour eux, et le filtre plus bas les ecarte. C'est la verification
        // « l'etudiant a-t-il deja depose ? » — elle se fait par la donnee, pas
        // par un cas particulier.
        $souscriptions = ESBTPFraisSubscription::query()
            ->where('inscription_id', $inscription->id)
            ->where('is_active', true)
            ->with('fraisCategory')
            ->get()
            ->sortBy(fn ($s) => [$s->fraisCategory->sort_order ?? 9999, $s->fraisCategory->id ?? 0])
            ->values();

        $reste = [];

        foreach ($souscriptions as $sub) {
            $du = (float) $sub->chargedAmount() - (float) ($dejaAlloue[$sub->frais_category_id] ?? 0);

            if ($du > 0.009) {
                $reste[$sub->frais_category_id] = $du;
            }
        }

        return $reste;
    }

    /**
     * Repartit UN versement, et met a jour ce qui reste du.
     *
     * Rend un tableau vide quand la repartition ne dirait rien de plus que le
     * paiement lui-meme — un seul frais servi, pour la totalite du versement,
     * sur la categorie que le paiement porte deja.
     *
     * @param  array<int, float>  $reste
     * @return array<int, array{paiement_id: int, frais_category_id: int, montant: float}>
     */
    private function repartirUnVersement(ESBTPPaiement $paiement, array &$reste): array
    {
        $aRepartir = (float) $paiement->montant;

        // `frais_category_id` est NULLABLE sur esbtp_paiements, et sa cle
        // etrangere est en `set null` : un versement peut parfaitement n'en
        // porter aucune. `(int) null` vaut ZERO, pas « rien » — et zero n'est
        // l'identifiant d'aucune categorie. La branche du surplus ecrivait donc
        // une allocation sur `frais_category_id = 0`, la cle etrangere la
        // refusait (1452), et TOUTE la transaction `--apply` etait annulee. Un
        // seul versement sans categorie suffisait a faire echouer le lot entier
        // — et le versement sans categorie est precisement le cas de
        // trop-percu pour lequel ce service existe.
        //
        // Absent veut dire absent : on le garde a null et on ne fabrique pas de
        // categorie d'origine la ou l'ecole n'en a designe aucune.
        $categorieDuPaiement = $paiement->frais_category_id !== null
            ? (int) $paiement->frais_category_id
            : null;

        $allocations = [];

        // Le frais que le caissier a designe passe en premier : c'est
        // l'intention explicite du versement, elle prime sur l'ordre d'echeance.
        $ordre = array_keys($reste);

        if ($categorieDuPaiement !== null && isset($reste[$categorieDuPaiement])) {
            $ordre = array_merge(
                [$categorieDuPaiement],
                array_values(array_diff($ordre, [$categorieDuPaiement]))
            );
        }

        foreach ($ordre as $categoryId) {
            if ($aRepartir <= 0.009) {
                break;
            }

            $part = min($aRepartir, $reste[$categoryId]);

            if ($part <= 0.009) {
                continue;
            }

            $allocations[$categoryId] = round(($allocations[$categoryId] ?? 0) + $part, 2);
            $reste[$categoryId] = round($reste[$categoryId] - $part, 2);
            $aRepartir = round($aRepartir - $part, 2);
        }

        // Tous les frais soldes et il reste de l'argent : c'est une avance. Elle
        // demeure sur la categorie d'origine, ou elle se trouve deja.
        //
        // Quand le versement n'en porte pas, l'avance echoit au dernier frais
        // servi : c'est celui vers lequel l'argent allait encore. Faute de
        // dernier frais servi — rien n'etait du — il n'existe aucune categorie
        // ou poser cette avance, et en inventer une reviendrait a decider a la
        // place de l'ecole. On rend alors un tableau vide : le versement garde
        // son comportement d'avant, il reste compte dans le total de
        // l'inscription et sans imputation par frais, exactement comme
        // aujourd'hui.
        if ($aRepartir > 0.009) {
            $cible = $categorieDuPaiement ?? array_key_last($allocations);

            if ($cible === null) {
                return [];
            }

            $allocations[$cible] = round(($allocations[$cible] ?? 0) + $aRepartir, 2);
        }

        // La repartition ne dit rien de plus que le paiement : on n'ecrit pas.
        //
        // Un versement SANS categorie, lui, dit toujours quelque chose de plus :
        // sans allocation il n'est impute a aucun frais, donc meme une
        // allocation unique le rend visible la ou il ne l'etait pas.
        if ($categorieDuPaiement !== null
            && count($allocations) === 1
            && array_key_first($allocations) === $categorieDuPaiement
            && abs($allocations[$categorieDuPaiement] - (float) $paiement->montant) < 0.01) {
            return [];
        }

        $sortie = [];

        foreach ($allocations as $categoryId => $montant) {
            $sortie[] = [
                'paiement_id' => (int) $paiement->id,
                'frais_category_id' => (int) $categoryId,
                'montant' => $montant,
            ];
        }

        return $sortie;
    }
}
