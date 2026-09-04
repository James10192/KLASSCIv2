<?php

namespace App\Services\Frais;

use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Services\ApplicableFraisResolver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Remet les souscriptions obligatoires d'accord avec le bareme en vigueur.
 *
 * Trois ecarts possibles, pas un :
 *  - AJOUTER  : un frais obligatoire s'applique mais n'a jamais ete souscrit ;
 *  - RETIRER  : une souscription porte un frais qui ne s'applique plus ;
 *  - AJUSTER  : la souscription existe mais fige un tarif que l'ecole a change
 *               depuis. C'est l'ecart le plus courant et c'est celui qui restait
 *               invisible : une souscription gele le montant au jour de
 *               l'inscription, donc corriger la CATEGORIE ne rattrapait aucun
 *               etudiant deja inscrit — la caisse continuait de reclamer
 *               l'ancien prix, et « Regenerer les frais » repondait
 *               « aucun ecart ».
 *
 * L'ajustement se demande (`$ajusterMontants`), il ne s'impose pas : il touche a
 * ce qu'on reclame a quelqu'un. Les appelants qui regenerent en passant (depot
 * en nature, par exemple) ne doivent pas rejouer le bareme dans le dos de la
 * caisse.
 */
class SouscriptionsObligatoiresManquantes
{
    /**
     * Nombre d'inscriptions chargees a la fois. Une regeneration a l'echelle
     * d'une annee touche plusieurs milliers de lignes sur les grosses
     * instances : les lire d'un bloc tiendrait toute la table en memoire.
     */
    private const TAILLE_LOT = 200;

    /**
     * Bareme deja resolu, par signature de scope, le temps d'un appel.
     *
     * Deux etudiants d'une meme classe au meme statut ont le meme bareme. Sans
     * ce cache, chaque inscription rejouait une requete par categorie de frais :
     * sur une annee entiere, c'est ce qui rendait la regeneration globale
     * inutilisable.
     *
     * @var array<string, Collection>
     */
    private array $baremeParScope = [];

    public function __construct(private readonly ApplicableFraisResolver $resolver)
    {
    }

    /**
     * @param  array<int, int>|null  $inscriptionIds
     * @return array{
     *     total: int,
     *     inscriptions: int,
     *     lignes: array,
     *     lignes_retrait: array,
     *     lignes_ajustement: array,
     *     total_ajouter: int,
     *     total_retirer: int,
     *     total_ajuster: int,
     *     applique: bool
     * }
     */
    public function executer(
        bool $appliquer = false,
        ?int $anneeId = null,
        ?array $inscriptionIds = null,
        bool $ajusterMontants = false
    ): array {
        $this->baremeParScope = [];

        $requete = ESBTPInscription::query()
            ->whereIn('status', ['active', 'en_attente'])
            ->when($anneeId, fn ($q) => $q->where('annee_universitaire_id', $anneeId))
            ->when($inscriptionIds, fn ($q) => $q->whereIn('id', $inscriptionIds))
            ->with(['etudiant', 'classe']);

        $lignes = [];
        $lignesRetrait = [];
        $lignesAjustement = [];
        $aCreer = [];
        $aRetirer = [];
        $aAjuster = [];

        $requete->chunkById(self::TAILLE_LOT, function (Collection $inscriptions) use (
            &$lignes,
            &$lignesRetrait,
            &$lignesAjustement,
            &$aCreer,
            &$aRetirer,
            &$aAjuster,
            $ajusterMontants
        ): void {
            $dejaSouscrit = ESBTPFraisSubscription::query()
                ->whereIn('inscription_id', $inscriptions->pluck('id'))
                ->with('fraisCategory')
                ->get()
                ->groupBy('inscription_id');

            foreach ($inscriptions as $inscription) {
                $subs = $dejaSouscrit->get($inscription->id, collect());
                $possedees = $subs->pluck('frais_category_id')->all();
                $voulues = $this->baremePour($inscription);
                $etudiant = $inscription->etudiant;
                $nom = $etudiant ? trim(($etudiant->nom ?? '').' '.($etudiant->prenoms ?? '')) : null;

                $identite = [
                    'inscription_id' => $inscription->id,
                    'etudiant' => $nom,
                    'matricule' => $etudiant->matricule ?? null,
                    'classe' => $inscription->classe->name ?? null,
                ];

                foreach ($voulues as $categoryId => $fee) {
                    if (in_array($categoryId, $possedees, true)) {
                        continue;
                    }
                    $montant = (float) $fee['amount'];
                    if ($montant <= 0) {
                        continue;
                    }
                    $categorie = $fee['category'];
                    $lignes[] = $identite + [
                        'categorie' => $categorie->name,
                        'categorie_id' => $categorie->id,
                        'montant' => $montant,
                        'accepte_en_nature' => (bool) $categorie->accepts_in_kind,
                        'action' => 'ajouter',
                    ];
                    $aCreer[] = [
                        'inscription_id' => $inscription->id,
                        'frais_category_id' => $categorie->id,
                        'amount' => $montant,
                    ];
                }

                // Deux requetes par inscription : on ne les paie que si des
                // souscriptions existent, seul cas ou ce releve sert.
                $paye = $subs->isEmpty()
                    ? collect()
                    : ESBTPPaiement::netPaidByCategory($inscription->id);

                foreach ($subs as $sub) {
                    $categorie = $sub->fraisCategory;
                    if (! $categorie || ! $categorie->is_mandatory) {
                        continue;
                    }

                    if ($voulues->has($categorie->id)) {
                        $ecart = $ajusterMontants
                            ? $this->ecartDeMontant($sub, $voulues->get($categorie->id), $paye)
                            : null;

                        if ($ecart !== null) {
                            $lignesAjustement[] = $identite + $ecart['ligne'];
                            $aAjuster[] = $ecart['mutation'];
                        }

                        continue;
                    }

                    if ($sub->satisfied_in_kind) {
                        continue;
                    }
                    if ((float) ($paye[$categorie->id] ?? 0) > 0) {
                        continue;
                    }
                    $lignesRetrait[] = $identite + [
                        'categorie' => $categorie->name,
                        'categorie_id' => $categorie->id,
                        'montant' => (float) $sub->amount,
                        'accepte_en_nature' => (bool) $categorie->accepts_in_kind,
                        'action' => 'retirer',
                        'motif' => ($categorie->audience ?? '') === 'nouveaux_etablissement'
                            ? 'Réservé aux nouveaux'
                            : 'Ne s\'applique plus',
                    ];
                    $aRetirer[] = $sub->id;
                }
            }
        });

        $resultat = [
            // `total` reste le nombre d'AJOUTS : des appelants s'en servent pour
            // annoncer « n frais manquants ont ete ajoutes ». Y verser les
            // ajustements leur ferait dire faux.
            'total' => count($aCreer),
            'total_ajouter' => count($aCreer),
            'total_retirer' => count($aRetirer),
            'total_ajuster' => count($aAjuster),
            'inscriptions' => count(array_unique(array_merge(
                array_column($aCreer, 'inscription_id'),
                array_column($lignesRetrait, 'inscription_id'),
                array_column($lignesAjustement, 'inscription_id'),
            ))),
            'lignes' => $lignes,
            'lignes_retrait' => $lignesRetrait,
            'lignes_ajustement' => $lignesAjustement,
            'applique' => false,
        ];

        if (! $appliquer || ($aCreer === [] && $aRetirer === [] && $aAjuster === [])) {
            return $resultat;
        }

        $auteur = auth()->id() ?? \App\Models\User::query()->min('id');

        DB::transaction(function () use ($aCreer, $aRetirer, $aAjuster, $auteur): void {
            foreach ($aCreer as $ligne) {
                ESBTPFraisSubscription::create($ligne + [
                    'is_active' => true,
                    'subscribed_at' => now(),
                    'created_by' => $auteur,
                    'notes' => 'Régénération des frais obligatoires',
                ]);
            }
            if ($aRetirer !== []) {
                ESBTPFraisSubscription::query()->whereIn('id', $aRetirer)->delete();
            }
            foreach ($aAjuster as $mutation) {
                // Par le modele et un par un : la table est auditee, et un
                // changement de montant doit laisser trace de qui l'a fait.
                ESBTPFraisSubscription::query()
                    ->whereKey($mutation['souscription_id'])
                    ->first()
                    ?->update(['amount' => $mutation['amount']]);
            }
        });

        Log::warning('[frais] regeneration des souscriptions obligatoires', [
            'ajoutes' => count($aCreer),
            'retires' => count($aRetirer),
            'ajustes' => count($aAjuster),
            'annee_id' => $anneeId,
        ]);

        $resultat['applique'] = true;

        return $resultat;
    }

    /**
     * Le bareme applicable a cette inscription, memoise par scope.
     */
    private function baremePour(ESBTPInscription $inscription): Collection
    {
        $cle = implode('|', [
            $inscription->classe_id ?? '-',
            $inscription->filiere_id ?? '-',
            $inscription->niveau_id ?? '-',
            $inscription->annee_universitaire_id ?? '-',
            $inscription->affectation_status ?? '-',
            $inscription->statut_etablissement ?? '-',
        ]);

        return $this->baremeParScope[$cle] ??= $this->resolver
            ->resolveMandatoryFeesForInscription($inscription)
            ->keyBy(fn (array $fee) => $fee['category']->id);
    }

    /**
     * L'ecart entre le montant fige par la souscription et le tarif en vigueur.
     *
     * Rend `null` quand il n'y a rien a corriger, ou quand corriger serait une
     * decision qui ne nous appartient pas.
     *
     * @param  array{category: \App\Models\ESBTPFraisCategory, amount: float}  $fee
     * @return array{ligne: array, mutation: array}|null
     */
    private function ecartDeMontant(
        ESBTPFraisSubscription $sub,
        array $fee,
        Collection $paye
    ): ?array {
        // Une souscription desactivee ne reclame rien : la reevaluer la
        // ressusciterait dans les totaux de la caisse.
        if (! $sub->is_active) {
            return null;
        }

        // Frais solde en nature : le du a ete eteint par un depot de
        // fournitures, pas par un montant. Le reevaluer recreerait une dette
        // que l'etudiant a deja honoree autrement.
        if ($sub->satisfied_in_kind) {
            return null;
        }

        // Le montant vient d'une option choisie (pack, formule) et non du
        // bareme de la categorie : ce n'est pas a nous de le trancher.
        if ($sub->selected_option_id) {
            return null;
        }

        $nouveau = (float) $fee['amount'];

        // Un bareme resolu a zero veut dire « pas de configuration pour ce
        // scope », pas « gratuit ». Ecraser un tarif existant avec ce zero
        // effacerait silencieusement la dette de l'etudiant.
        if ($nouveau <= 0) {
            return null;
        }

        $actuel = (float) $sub->amount;
        if (abs($nouveau - $actuel) < 0.01) {
            return null;
        }

        $categorie = $fee['category'];
        $dejaPaye = (float) ($paye[$categorie->id] ?? 0);

        return [
            'ligne' => [
                'categorie' => $categorie->name,
                'categorie_id' => $categorie->id,
                'montant' => $nouveau,
                'montant_actuel' => $actuel,
                'ecart' => $nouveau - $actuel,
                'deja_paye' => $dejaPaye,
                'restera_du' => max(0.0, $nouveau - $dejaPaye),
                // Il avait solde l'ancien tarif : le relever lui cree une dette
                // qu'il n'a pas contractee. On l'applique, mais on le dit.
                'cree_une_dette' => $actuel - $dejaPaye <= 0.009 && $nouveau - $dejaPaye > 0.009,
                // Il a paye plus que le nouveau tarif : la difference devient un
                // trop-percu a rembourser ou a reaffecter.
                'trop_percu' => $dejaPaye - $nouveau > 0.009,
                'accepte_en_nature' => (bool) $categorie->accepts_in_kind,
                'action' => 'ajuster',
            ],
            'mutation' => [
                'souscription_id' => $sub->id,
                'amount' => $nouveau,
            ],
        ];
    }
}
