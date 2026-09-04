<?php

namespace App\Services\Frais;

use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Services\ApplicableFraisResolver;
use Illuminate\Database\Eloquent\Builder;
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
     * @param  Builder|null  $portee  Requete d'inscriptions deja filtree (la liste
     *                                a l'ecran), au lieu d'une annee ou d'une liste d'ids.
     * @param  array<int, string>|null  $clesRetenues  N'appliquer que ces lignes
     *                                (cf. `cle` de chaque ligne). null = tout.
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
        bool $ajusterMontants = false,
        ?Builder $portee = null,
        ?array $clesRetenues = null
    ): array {
        $this->baremeParScope = [];
        $retenues = $clesRetenues === null ? null : array_flip($clesRetenues);

        $requete = ($portee ? clone $portee : ESBTPInscription::query())
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
        $inscriptionsTouchees = [];

        $requete->chunkById(self::TAILLE_LOT, function (Collection $inscriptions) use (
            &$lignes,
            &$lignesRetrait,
            &$lignesAjustement,
            &$aCreer,
            &$aRetirer,
            &$aAjuster,
            &$inscriptionsTouchees,
            $ajusterMontants,
            $retenues
        ): void {
            $dejaSouscrit = ESBTPFraisSubscription::query()
                ->whereIn('inscription_id', $inscriptions->pluck('id'))
                ->with('fraisCategory')
                ->get()
                ->groupBy('inscription_id');

            // Les ajustements attendent la fin du lot : leur alerte « montant
            // deja retouche » se lit dans le journal d'audit, et une requete par
            // souscription serait ruineuse sur une annee entiere.
            $brouillonAjustements = [];

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
                    $cle = 'add:'.$inscription->id.':'.$categorie->id;
                    $lignes[] = $identite + [
                        'cle' => $cle,
                        'categorie' => $categorie->name,
                        'categorie_id' => $categorie->id,
                        'montant' => $montant,
                        'accepte_en_nature' => (bool) $categorie->accepts_in_kind,
                        'action' => 'ajouter',
                    ];
                    if ($this->retenue($retenues, $cle)) {
                        $aCreer[] = [
                            'inscription_id' => $inscription->id,
                            'frais_category_id' => $categorie->id,
                            'amount' => $montant,
                        ];
                        $inscriptionsTouchees[$inscription->id] = true;
                    }
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
                            $brouillonAjustements[] = $identite + $ecart;
                        }

                        continue;
                    }

                    if ($sub->satisfied_in_kind) {
                        continue;
                    }
                    if ((float) ($paye[$categorie->id] ?? 0) > 0) {
                        continue;
                    }
                    $cle = 'del:'.$sub->id;
                    $lignesRetrait[] = $identite + [
                        'cle' => $cle,
                        'categorie' => $categorie->name,
                        'categorie_id' => $categorie->id,
                        'montant' => (float) $sub->amount,
                        'accepte_en_nature' => (bool) $categorie->accepts_in_kind,
                        'action' => 'retirer',
                        'motif' => ($categorie->audience ?? '') === 'nouveaux_etablissement'
                            ? 'Réservé aux nouveaux'
                            : 'Ne s\'applique plus',
                    ];
                    if ($this->retenue($retenues, $cle)) {
                        $aRetirer[] = $sub->id;
                        $inscriptionsTouchees[$inscription->id] = true;
                    }
                }
            }

            $retouches = $this->montantsDejaRetouches(
                array_column(array_column($brouillonAjustements, 'mutation'), 'souscription_id')
            );

            foreach ($brouillonAjustements as $candidat) {
                $mutation = $candidat['mutation'];
                $ligne = $candidat['ligne'];
                unset($candidat['mutation'], $candidat['ligne']);

                $retouche = $retouches[$mutation['souscription_id']] ?? null;

                // Journal d'audit eteint : on ne peut plus distinguer un tarif
                // negocie d'un tarif perime. Plutot que d'annoncer « rien de
                // retouche » — ce qui aurait fait tout arriver coche et efface
                // les remises au premier clic — on traite CHAQUE ajustement
                // comme protege : il faudra le cocher nommement.
                $protegee = $retouche !== null || ! $this->auditActif();

                $lignesAjustement[] = $candidat + $ligne + [
                    // Quelqu'un a deja pose une decision sur ce montant : une
                    // remise, une bourse, un arrangement. La regeneration ne
                    // l'ecrase pas d'elle-meme, elle le signale et laisse
                    // decoche.
                    'montant_deja_retouche' => $protegee,
                    'retouche_le' => $retouche['le'] ?? null,
                    'retouche_par' => $retouche['par'] ?? null,
                    'motif_protection' => $retouche !== null ? 'retouche' : (! $this->auditActif() ? 'audit_eteint' : null),
                ];

                // Un montant retouche a la main ne s'ecrase QUE si sa ligne a
                // ete cochee nommement. Laisser cette garde au navigateur ne
                // protegeait rien : un appel direct sans selection, ou un apercu
                // tronque dont la ligne n'avait jamais ete affichee, effacait la
                // remise sans que personne ne l'ait vue passer.
                $applicable = $protegee
                    ? ($retenues !== null && isset($retenues[$ligne['cle']]))
                    : $this->retenue($retenues, $ligne['cle']);

                if ($applicable) {
                    $aAjuster[] = $mutation;
                    $inscriptionsTouchees[$mutation['inscription_id']] = true;
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
            // Les inscriptions REELLEMENT touchees : avec une selection
            // partielle, compter toutes les lignes detectees annoncerait plus
            // d'etudiants que le bouton n'en modifie.
            'inscriptions' => count($inscriptionsTouchees),
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
                // Une par une, par le modele. Une suppression de masse par le
                // constructeur de requetes n'instancie rien, donc n'emet aucun
                // evenement et n'ecrit aucune ligne d'audit : la question « qui
                // a retire ce frais, et quand ? » restait sans reponse, sur une
                // table sans suppression douce.
                ESBTPFraisSubscription::query()
                    ->whereIn('id', $aRetirer)
                    ->get()
                    ->each
                    ->delete();
            }
            foreach ($aAjuster as $mutation) {
                // Par le modele et un par un : la table est auditee, et un
                // changement de montant doit laisser trace de qui l'a fait.
                $souscription = ESBTPFraisSubscription::query()
                    ->whereKey($mutation['souscription_id'])
                    ->first();

                if (! $souscription) {
                    continue;
                }

                // Marque l'ecriture comme automatique : au passage suivant, elle
                // ne devra pas revenir signalee « retouchee a la main ».
                $souscription->realignementSurBareme = true;
                $souscription->update(['amount' => $mutation['amount']]);
            }
        });

        Log::warning('[frais] regeneration des souscriptions obligatoires', [
            'ajoutes' => count($aCreer),
            'retires' => count($aRetirer),
            'ajustes' => count($aAjuster),
            'annee_id' => $anneeId,
            // Sans l'auteur ni la portee, ce journal ne permettait pas de
            // repondre a « qui a lance ca, et sur qui ? ».
            'par_utilisateur' => $auteur,
            'portee' => $portee !== null ? 'liste filtree' : ($inscriptionIds !== null ? 'selection' : 'annee'),
            'inscriptions_touchees' => count($inscriptionsTouchees),
            'selection_partielle' => $clesRetenues !== null,
        ]);

        $resultat['applique'] = true;

        return $resultat;
    }

    /**
     * Le journal d'audit ecrit-il ? Toute la detection des montants negocies en
     * depend, et un tenant peut l'avoir eteint.
     */
    private function auditActif(): bool
    {
        return $this->auditActif ??= (bool) config('audit.enabled', true);
    }

    private ?bool $auditActif = null;

    /**
     * Cette ligne fait-elle partie de ce qu'on a demande d'appliquer ?
     *
     * `null` veut dire « tout », pour les appelants qui ne proposent aucune
     * selection (depot en nature, endpoint CLI).
     *
     * @param  array<string, int>|null  $retenues
     */
    private function retenue(?array $retenues, string $cle): bool
    {
        return $retenues === null || isset($retenues[$cle]);
    }

    /**
     * Les souscriptions dont le montant a deja ete change par quelqu'un.
     *
     * Un montant retouche porte une decision — une remise, une bourse, un
     * arrangement de rentree. La regeneration ne doit pas l'effacer d'un clic
     * sans que personne ne l'ait vu passer : elle le signale, et l'ecran laisse
     * la ligne decochee.
     *
     * Ses propres realignements sont exclus par leur marqueur d'audit : sans
     * cela, tout montant corrige une fois reviendrait signale au passage
     * suivant, et l'alerte se serait diluee jusqu'a ne plus rien vouloir dire.
     *
     * @param  array<int, int>  $souscriptionIds
     * @return array<int, array{le: string|null, par: int|null}>
     */
    private function montantsDejaRetouches(array $souscriptionIds): array
    {
        $souscriptionIds = array_values(array_unique(array_filter($souscriptionIds)));

        if ($souscriptionIds === []) {
            return [];
        }

        $audits = \OwenIt\Auditing\Models\Audit::query()
            ->where('auditable_type', ESBTPFraisSubscription::class)
            ->whereIn('auditable_id', $souscriptionIds)
            ->where('event', 'updated')
            ->orderByDesc('created_at')
            // Departage : plusieurs audits dans la meme seconde est le cas
            // NORMAL d'une regeneration de masse. Sans lui, l'alerte changeait
            // d'un apercu a l'autre sur des donnees identiques.
            ->orderByDesc('id')
            ->get(['auditable_id', 'new_values', 'tags', 'user_id', 'created_at']);

        $parSouscription = [];

        foreach ($audits as $audit) {
            $id = (int) $audit->auditable_id;

            if (isset($parSouscription[$id])) {
                continue; // le plus recent suffit a alerter
            }

            $valeurs = $audit->new_values;
            if (! is_array($valeurs) || ! array_key_exists('amount', $valeurs)) {
                continue;
            }

            if (str_contains((string) $audit->tags, ESBTPFraisSubscription::TAG_REALIGNEMENT)) {
                continue;
            }

            $parSouscription[$id] = [
                'le' => optional($audit->created_at)->format('d/m/Y'),
                'par' => $audit->user_id ? (int) $audit->user_id : null,
            ];
        }

        return $parSouscription;
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
                'cle' => 'adj:'.$sub->id,
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
                'inscription_id' => (int) $sub->inscription_id,
                'amount' => $nouveau,
            ],
        ];
    }
}
