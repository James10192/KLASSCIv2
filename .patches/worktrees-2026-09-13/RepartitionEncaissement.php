<?php

namespace App\Services\Frais;

use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPPaiementAllocation;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Repartit UN versement encaisse au guichet sur les frais qu'il regle.
 *
 * L'ecran d'encaissement demandait d'abord un frais, puis un montant. Tout ce
 * qui debordait de ce frais restait colle dessus, et le calcul du restant, qui
 * fait `max(0, du - paye)` par categorie, ECRETAIT l'excedent : sur ISLG,
 * 105 000 F d'un etudiant sont ainsi devenus invisibles, ni imputes ni
 * signales. On demande donc desormais le montant d'abord, puis ou il va.
 *
 * Ce service tient les trois moments de cette bascule — ce que chaque frais
 * reclame encore, comment un montant se repartit dessus, et l'ecriture des
 * lignes — pour que le controleur n'ait rien a arbitrer.
 *
 * La regle de service est celle de {@see RepartitionTropPercu} : servir dans
 * l'ordre choisi par l'ecole, ne jamais donner a un frais plus qu'il ne
 * reclame, et laisser le surplus en avance sur le frais designe une fois tout
 * solde. Les deux doivent dire la meme chose : l'une repartit a l'encaissement,
 * l'autre rattrape les versements deja encaisses. Tant qu'elles vivent
 * separement, toute correction apportee ici doit y etre reportee — la reprise
 * de RepartitionTropPercu sur ce service est le prochain pas.
 */
class RepartitionEncaissement
{
    /**
     * Deux montants a moins d'un centime l'un de l'autre sont le meme montant.
     *
     * Les montants transitent en flottants (JSON du navigateur, colonne
     * decimale) : comparer a l'egalite stricte ferait echouer une repartition
     * juste sur un arrondi.
     */
    private const CENTIME = 0.009;

    /**
     * Ce que chaque frais de cette inscription reclame encore.
     *
     * Le du vient de la SOUSCRIPTION — c'est le montant reellement assigne a cet
     * etudiant, variante choisie et depot en nature compris. Le paye vient de
     * `netPaidByCategory()`, qui lit les allocations quand il y en a, et deduit
     * les avoirs qui les annulent. Une deuxieme source du meme chiffre serait
     * fatalement en desaccord avec celle-ci un jour ou l'autre.
     *
     * L'ordre est celui de l'ecole (`sort_order`), le meme que celui de l'ecran :
     * c'est lui qui dit quel frais se solde en premier. Aucune priorite n'est
     * ecrite ici — une ecole qui veut solder la tenue avant la scolarite n'a qu'a
     * reordonner ses categories.
     *
     * @return Collection<int, array{frais_category_id:int, nom:string, du:float, paye:float, reste:float, depose_en_nature:bool, montant_non_defini:bool}>
     */
    public function restes(ESBTPInscription $inscription): Collection
    {
        $paye = ESBTPPaiement::netPaidByCategory($inscription->id);

        return ESBTPFraisSubscription::query()
            ->where('inscription_id', $inscription->id)
            ->where('is_active', true)
            ->with('fraisCategory')
            ->get()
            ->sortBy(fn (ESBTPFraisSubscription $s) => [
                $s->fraisCategory->sort_order ?? PHP_INT_MAX,
                $s->fraisCategory->id ?? 0,
            ])
            ->values()
            ->map(function (ESBTPFraisSubscription $sub) use ($paye): array {
                $du = round($sub->chargedAmount(), 2);
                $regle = round((float) ($paye[$sub->frais_category_id] ?? 0), 2);

                return [
                    'frais_category_id' => (int) $sub->frais_category_id,
                    'nom' => $sub->fraisCategory->name ?? 'Frais',
                    'du' => $du,
                    'paye' => $regle,
                    'reste' => round(max(0, $du - $regle), 2),
                    'depose_en_nature' => (bool) $sub->satisfied_in_kind,
                    'montant_non_defini' => $sub->montantNonDefini(),
                ];
            });
    }

    /**
     * Repartit un montant sur des restes, sans jamais depasser aucun d'eux.
     *
     * Le frais designe par le caissier passe en premier : c'est l'intention
     * explicite du versement, elle prime sur l'ordre d'echeance. Le reliquat
     * eventuel, une fois tous les frais soldes, demeure sur ce meme frais : il
     * appartient a l'etudiant et n'a pas a etre reparti sur des dettes qui
     * n'existent pas.
     *
     * @param  array<int, float>  $restes  reste a payer par categorie, dans l'ordre de l'ecole
     * @return array<int, float>  montant alloue par categorie
     */
    public function repartirAutomatiquement(array $restes, float $montant, ?int $categorieDesignee = null): array
    {
        $aRepartir = round($montant, 2);
        $allocations = [];

        foreach ($this->ordreDeService($restes, $categorieDesignee) as $categoryId) {
            if ($aRepartir <= self::CENTIME) {
                break;
            }

            $part = min($aRepartir, (float) $restes[$categoryId]);

            if ($part <= self::CENTIME) {
                continue;
            }

            $allocations[$categoryId] = round(($allocations[$categoryId] ?? 0) + $part, 2);
            $aRepartir = round($aRepartir - $part, 2);
        }

        if ($aRepartir > self::CENTIME) {
            $avance = $this->porteurDeLAvance($allocations, $restes, $categorieDesignee);

            if ($avance !== null) {
                $allocations[$avance] = round(($allocations[$avance] ?? 0) + $aRepartir, 2);
            }
        }

        return $allocations;
    }

    /**
     * Revalide la repartition annoncee par le navigateur.
     *
     * Le garde-fou de l'ecran ne protege rien a lui seul : cet endpoint
     * manipule de l'argent et rien n'empeche un appel direct. Tout est donc
     * recalcule ici contre les dus reels de l'inscription, et un refus nomme le
     * frais qui deborde et de combien.
     *
     * Quand le navigateur n'annonce aucune repartition, le serveur la calcule
     * lui-meme avec la meme regle. C'est ce qui evite qu'un appel muet retombe
     * sur l'ancien comportement — celui qui collait tout sur un frais et rendait
     * le trop-percu invisible.
     *
     * @param  mixed  $lignesBrutes  ce que le formulaire a poste
     * @return array<int, array{frais_category_id:int, montant:float}>
     *
     * @throws ValidationException
     */
    public function valider(ESBTPInscription $inscription, float $montant, mixed $lignesBrutes, ?int $categorieDesignee = null): array
    {
        $restes = $this->restes($inscription)->keyBy('frais_category_id');
        $plafonds = $restes->map(fn (array $frais): float => (float) $frais['reste'])->all();

        $lignes = $this->normaliser($lignesBrutes);

        if ($lignes === []) {
            return $this->formater($this->repartirAutomatiquement($plafonds, $montant, $categorieDesignee));
        }

        $noms = $this->nommer(array_keys($lignes), $restes);
        $affecte = round(array_sum($lignes), 2);

        if ($affecte > $montant + self::CENTIME) {
            $this->refuser(sprintf(
                'La repartition affecte %s FCFA alors que le versement n\'est que de %s FCFA.',
                $this->fcfa($affecte),
                $this->fcfa($montant)
            ));
        }

        $totalDu = round(array_sum($plafonds), 2);
        $couvert = $this->partServieDesDettes($lignes, $plafonds);
        $toutEstSolde = $couvert >= $totalDu - self::CENTIME;

        // Un frais ne recoit plus que son du QUE si plus rien n'est du ailleurs :
        // le surplus est alors une avance assumee, pas un debordement.
        if (! $toutEstSolde) {
            $this->refuserLePremierDebordement($lignes, $plafonds, $noms);
        }

        $reliquat = round($montant - $affecte, 2);

        if ($reliquat > self::CENTIME) {
            if (! $toutEstSolde) {
                $this->refuser(sprintf(
                    '%s FCFA ne sont affectes a aucun frais alors qu\'il reste %s FCFA a payer.',
                    $this->fcfa($reliquat),
                    $this->fcfa(round($totalDu - $couvert, 2))
                ));
            }

            $avance = $this->porteurDeLAvance($lignes, $plafonds, $categorieDesignee);

            if ($avance !== null) {
                $lignes[$avance] = round(($lignes[$avance] ?? 0) + $reliquat, 2);
            }
        }

        return $this->formater($lignes);
    }

    /**
     * La categorie qui recoit la plus grosse part.
     *
     * C'est elle que le paiement portera dans `frais_category_id` : tout le code
     * qui lit encore cette colonne — recu, journal de caisse, filtres,
     * echeanciers — reste ainsi devant la reponse la moins fausse possible.
     *
     * @param  array<int, array{frais_category_id:int, montant:float}>  $lignes
     */
    public function categorieDominante(array $lignes, ?int $defaut = null): ?int
    {
        $dominante = null;
        $plusGrande = -1.0;

        foreach ($lignes as $ligne) {
            if ($ligne['montant'] > $plusGrande + self::CENTIME) {
                $plusGrande = $ligne['montant'];
                $dominante = $ligne['frais_category_id'];
            }
        }

        return $dominante ?? $defaut;
    }

    /**
     * Ecrit les lignes de repartition du versement.
     *
     * On n'ecrit RIEN quand la repartition ne dit rien de plus que le paiement
     * lui-meme — un seul frais, pour la totalite du versement, celui que le
     * paiement porte deja. Sans ce repli, un versement mono-frais s'afficherait
     * partout comme « 1 frais » au lieu de son nom, et la table d'allocations
     * doublerait la colonne du paiement sans rien y ajouter.
     *
     * @param  array<int, array{frais_category_id:int, montant:float}>  $lignes
     * @return int  nombre de lignes ecrites
     */
    public function enregistrer(ESBTPPaiement $paiement, array $lignes): int
    {
        if ($lignes === [] || $this->ditLaMemeChoseQueLePaiement($paiement, $lignes)) {
            return 0;
        }

        foreach ($lignes as $ligne) {
            ESBTPPaiementAllocation::create([
                'paiement_id' => $paiement->id,
                'frais_category_id' => $ligne['frais_category_id'],
                'montant' => $ligne['montant'],
            ]);
        }

        return count($lignes);
    }

    /**
     * L'empreinte d'une repartition, pour reconnaitre deux versements jumeaux.
     *
     * Deux versements du meme montant sur la meme inscription mais repartis
     * autrement ne sont PAS un double-clic : sans cette empreinte, le second
     * serait silencieusement absorbe par la protection anti-doublon et l'argent
     * disparaitrait.
     *
     * @param  array<int, array{frais_category_id:int, montant:float}>  $lignes
     */
    public function empreinte(array $lignes): string
    {
        $paires = [];

        foreach ($lignes as $ligne) {
            $paires[] = $ligne['frais_category_id'].':'.number_format($ligne['montant'], 2, '.', '');
        }

        sort($paires);

        return implode('|', $paires);
    }

    /**
     * L'ordre dans lequel les frais sont servis.
     *
     * @param  array<int, float>  $restes
     * @return array<int, int>
     */
    private function ordreDeService(array $restes, ?int $categorieDesignee): array
    {
        $ordre = array_keys($restes);

        if ($categorieDesignee !== null && array_key_exists($categorieDesignee, $restes)) {
            return array_merge(
                [$categorieDesignee],
                array_values(array_diff($ordre, [$categorieDesignee]))
            );
        }

        return $ordre;
    }

    /**
     * Sur quel frais poser le surplus quand tout est solde.
     *
     * Le frais designe d'abord — c'est celui que le caissier a mis en avant —
     * sinon celui qui a deja recu le plus, sinon le premier de l'ecole. Sans
     * aucun frais souscrit il n'y a rien a designer : le versement reste
     * mono-categorie, comme avant.
     *
     * @param  array<int, float>  $allocations
     * @param  array<int, float>  $restes
     */
    private function porteurDeLAvance(array $allocations, array $restes, ?int $categorieDesignee): ?int
    {
        if ($categorieDesignee !== null && ($allocations !== [] || $restes !== [])) {
            return $categorieDesignee;
        }

        if ($allocations !== []) {
            arsort($allocations);

            return (int) array_key_first($allocations);
        }

        return $restes === [] ? null : (int) array_key_first($restes);
    }

    /**
     * La part des dettes reellement servie par cette repartition.
     *
     * Un frais sans souscription n'a pas de du connu : on ne lui oppose aucun
     * plafond plutot que d'inventer zero, qui refuserait un encaissement
     * legitime chez les etablissements qui ne souscrivent pas leurs etudiants.
     *
     * @param  array<int, float>  $lignes
     * @param  array<int, float>  $plafonds
     */
    private function partServieDesDettes(array $lignes, array $plafonds): float
    {
        $couvert = 0.0;

        foreach ($lignes as $categoryId => $part) {
            $couvert = round($couvert + min($part, $this->plafondDe($categoryId, $part, $plafonds)), 2);
        }

        return $couvert;
    }

    /**
     * @param  array<int, float>  $lignes
     * @param  array<int, float>  $plafonds
     * @param  array<int, string>  $noms
     *
     * @throws ValidationException
     */
    private function refuserLePremierDebordement(array $lignes, array $plafonds, array $noms): void
    {
        foreach ($lignes as $categoryId => $part) {
            $plafond = $this->plafondDe($categoryId, $part, $plafonds);

            if ($part > $plafond + self::CENTIME) {
                $this->refuser(sprintf(
                    'Le frais « %s » recoit %s FCFA alors qu\'il ne reste que %s FCFA a payer dessus : %s FCFA de trop.',
                    $noms[$categoryId] ?? 'Frais',
                    $this->fcfa($part),
                    $this->fcfa($plafond),
                    $this->fcfa(round($part - $plafond, 2))
                ));
            }
        }
    }

    /**
     * @param  array<int, float>  $plafonds
     */
    private function plafondDe(int $categoryId, float $part, array $plafonds): float
    {
        return array_key_exists($categoryId, $plafonds) ? $plafonds[$categoryId] : $part;
    }

    /**
     * Ramene ce que le formulaire a poste a `categorie => montant`.
     *
     * Les lignes a zero sont ecartees plutot que refusees : l'ecran affiche une
     * case par frais, et laisser une case vide est la facon normale de ne pas
     * servir ce frais.
     *
     * @return array<int, float>
     *
     * @throws ValidationException
     */
    private function normaliser(mixed $brutes): array
    {
        if (! is_array($brutes)) {
            return [];
        }

        $lignes = [];

        foreach ($brutes as $cle => $valeur) {
            if (is_array($valeur)) {
                $categoryId = (int) ($valeur['frais_category_id'] ?? 0);
                $montant = round((float) ($valeur['montant'] ?? 0), 2);
            } else {
                $categoryId = (int) $cle;
                $montant = round((float) $valeur, 2);
            }

            if ($categoryId <= 0 || $montant <= self::CENTIME) {
                continue;
            }

            // La table refuse deux lignes pour le meme frais (contrainte
            // `paiement_allocation_unique`) : mieux vaut le dire ici que laisser
            // la base lever une erreur illisible au milieu de la transaction.
            if (array_key_exists($categoryId, $lignes)) {
                $this->refuser('Le meme frais apparait deux fois dans la repartition.');
            }

            $lignes[$categoryId] = $montant;
        }

        return $lignes;
    }

    /**
     * Le nom de chaque frais cite, pour que le refus soit lisible.
     *
     * @param  array<int, int>  $categoryIds
     * @param  Collection<int, array>  $restes
     * @return array<int, string>
     *
     * @throws ValidationException
     */
    private function nommer(array $categoryIds, Collection $restes): array
    {
        $noms = [];

        foreach ($restes as $frais) {
            $noms[$frais['frais_category_id']] = $frais['nom'];
        }

        // La base n'est interrogee que pour les frais que l'inscription ne
        // souscrit pas : partout ailleurs la souscription porte deja le nom, et
        // c'est celui-la qui doit apparaitre dans un refus.
        $inconnus = array_values(array_diff($categoryIds, array_keys($noms)));

        if ($inconnus === []) {
            return $noms;
        }

        $depuisLaBase = ESBTPFraisCategory::whereIn('id', $inconnus)->pluck('name', 'id')->all();

        foreach ($inconnus as $categoryId) {
            if (! array_key_exists($categoryId, $depuisLaBase)) {
                $this->refuser('La repartition designe un frais qui n\'existe pas.');
            }

            $noms[$categoryId] = $depuisLaBase[$categoryId];
        }

        return $noms;
    }

    /**
     * @param  array<int, float>  $lignes
     * @return array<int, array{frais_category_id:int, montant:float}>
     */
    private function formater(array $lignes): array
    {
        $sortie = [];

        foreach ($lignes as $categoryId => $montant) {
            if ($montant <= self::CENTIME) {
                continue;
            }

            $sortie[] = [
                'frais_category_id' => (int) $categoryId,
                'montant' => round((float) $montant, 2),
            ];
        }

        return $sortie;
    }

    /**
     * @param  array<int, array{frais_category_id:int, montant:float}>  $lignes
     */
    private function ditLaMemeChoseQueLePaiement(ESBTPPaiement $paiement, array $lignes): bool
    {
        return count($lignes) === 1
            && $lignes[0]['frais_category_id'] === (int) $paiement->frais_category_id
            && abs($lignes[0]['montant'] - (float) $paiement->montant) < 0.01;
    }

    private function fcfa(float $montant): string
    {
        return number_format($montant, 0, ',', ' ');
    }

    /**
     * @throws ValidationException
     */
    private function refuser(string $message): never
    {
        throw ValidationException::withMessages(['allocations' => $message]);
    }
}
