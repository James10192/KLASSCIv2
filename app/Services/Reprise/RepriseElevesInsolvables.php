<?php

namespace App\Services\Reprise;

use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\User;
use App\Services\Frais\ServirLesFrais;
use App\Services\Inscriptions\NormalisationTypeInscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Reprise de l'annee ecoulee pour les eleves qui doivent encore de l'argent.
 *
 * L'ecole arrive sur KLASSCI avec un arriere ne dans son logiciel precedent. Tant
 * que l'annee ecoulee n'existe pas ici, la dette n'existe pas non plus : l'ecran
 * de reinscription lit ce que reclament les souscriptions et ce qu'ont eteint les
 * versements ({@see \App\Http\Controllers\ESBTP\ESBTPReinscriptionController}), et
 * sur un eleve sans passe il ne trouve rien. On recree donc le passe — inscription,
 * souscriptions, versements — et la dette reapparait d'elle-meme.
 *
 * On ne cree PAS les reliquats. `esbtp_reliquats_details` exige un
 * `inscription_destination_id` : la reinscription a venir, qui n'existe pas encore.
 * C'est `ReeinscriptionService::gererReliquats()` qui les posera, au moment ou
 * l'eleve se reinscrira. Les poser ici serait impossible, et inutile puisque ce
 * n'est pas ce que l'ecran lit a ce stade.
 *
 * REPARTITION DE LA DETTE — le document source ne donne qu'un montant global par
 * eleve. On ne l'eclate pas au prorata : ce serait une cle inventee, indefendable
 * devant un parent qui conteste. On applique la regle que la caisse applique deja
 * a tout versement, {@see ServirLesFrais} : servir les frais dans l'ordre que
 * L'ECOLE a range (`sort_order`). Ce qui n'est pas couvert est la dette, repartie
 * exactement comme la caisse l'aurait repartie.
 *
 * Deux egalites doivent tomber juste sur chaque ligne, sans quoi la ligne sort du
 * lot et n'est pas ecrite :
 *   1. somme des souscriptions      == montant reclame (total du PDF moins remise)
 *   2. somme des restes apres service == reste du PDF
 * On ne rattrape jamais un ecart en gonflant ou en rabotant une categorie.
 */
class RepriseElevesInsolvables
{
    /** En deca, c'est un residu d'arrondi, pas un ecart. */
    private const EPSILON = 0.009;

    public function __construct(private readonly ServirLesFrais $servir) {}

    /**
     * @param  array<int, array<string, mixed>>  $lignes  Les eleves du document source.
     * @param  int  $anneeId  L'annee ecoulee, celle qui porte la dette.
     * @return array{annee_id: int, lus: int, retenus: int, ecartes: int, applique: bool,
     *               totaux: array, lignes: array, ecarts: array}
     */
    public function executer(array $lignes, int $anneeId, bool $appliquer = false): array
    {
        $categories = ESBTPFraisCategory::query()
            ->where('is_active', true)
            ->where('is_mandatory', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($categories->isEmpty()) {
            throw new \RuntimeException(
                "Aucune categorie de frais obligatoire active : il n'y a rien a reclamer, "
                .'la reprise n\'a pas de sens en l\'etat.'
            );
        }

        $retenues = [];
        $ecarts = [];
        $apercu = [];

        foreach ($lignes as $ligne) {
            $resultat = $this->preparerLigne($ligne, $categories, $anneeId);

            if (isset($resultat['ecart'])) {
                $ecarts[] = $resultat['ecart'];

                continue;
            }

            $retenues[] = $resultat['plan'];
            $apercu[] = $resultat['apercu'];
        }

        $totaux = [
            'montant_reclame' => array_sum(array_column($apercu, 'montant_reclame')),
            'paye' => array_sum(array_column($apercu, 'paye')),
            'reste_du' => array_sum(array_column($apercu, 'reste_du')),
        ];

        if (! $appliquer || $retenues === []) {
            return [
                'annee_id' => $anneeId,
                'lus' => count($lignes),
                'retenus' => count($retenues),
                'ecartes' => count($ecarts),
                'applique' => false,
                'totaux' => $totaux,
                'lignes' => $apercu,
                'ecarts' => $ecarts,
            ];
        }

        $ecrits = $this->ecrire($retenues, $anneeId);

        Log::warning('[reprise] annee ecoulee recreee pour les eleves debiteurs', [
            'annee_id' => $anneeId,
            'eleves' => $ecrits['etudiants'],
            'inscriptions' => $ecrits['inscriptions'],
            'souscriptions' => $ecrits['souscriptions'],
            'versements' => $ecrits['paiements'],
            'ecartes' => count($ecarts),
        ]);

        return [
            'annee_id' => $anneeId,
            'lus' => count($lignes),
            'retenus' => count($retenues),
            'ecartes' => count($ecarts),
            'applique' => true,
            'totaux' => $totaux + $ecrits,
            'lignes' => $apercu,
            'ecarts' => $ecarts,
        ];
    }

    /**
     * Verifie une ligne et calcule sa repartition, sans rien ecrire.
     *
     * @return array{plan?: array, apercu?: array, ecart?: array}
     */
    private function preparerLigne(array $ligne, $categories, int $anneeId): array
    {
        $matricule = (string) ($ligne['matricule'] ?? '');
        $refuser = fn (string $motif, array $detail = []) => ['ecart' => [
            'matricule' => $matricule,
            'classe' => $ligne['classe_pdf'] ?? null,
            'motif' => $motif,
        ] + $detail];

        if ($matricule === '') {
            return $refuser('ligne sans matricule : rien pour l\'identifier');
        }

        $classe = ESBTPClasse::find($ligne['classe_id'] ?? null);
        if (! $classe) {
            return $refuser('classe introuvable', ['classe_id' => $ligne['classe_id'] ?? null]);
        }
        // La classe range son niveau sous `niveau_etude_id` ; l'inscription
        // l'attend sous `niveau_id`. Deux noms, une seule notion.
        if (! $classe->filiere_id || ! $classe->niveau_etude_id) {
            return $refuser('la classe ne porte pas de filiere ou de niveau exploitable');
        }

        $reclame = (float) ($ligne['montant_reclame'] ?? 0);
        $paye = (float) ($ligne['paye'] ?? 0);
        $resteAttendu = (float) ($ligne['reste_du'] ?? 0);
        $remise = (float) ($ligne['reduction'] ?? 0);

        if ($reclame <= 0) {
            return $refuser('montant reclame nul ou negatif');
        }
        if ($paye < 0 || $paye > $reclame + self::EPSILON) {
            return $refuser('montant paye hors bornes', ['paye' => $paye, 'reclame' => $reclame]);
        }

        // Les tarifs que l'ecole a configures pour CETTE classe.
        $tarifs = [];
        $sansTarif = [];
        foreach ($categories as $categorie) {
            $montant = $this->tarifPour($categorie, $classe, $anneeId);
            if ($montant <= 0) {
                $sansTarif[] = $categorie->name;

                continue;
            }
            $tarifs[$categorie->id] = $montant;
        }

        if ($tarifs === []) {
            return $refuser('aucun tarif configure pour cette classe : rien a repartir');
        }

        $totalTarifs = round(array_sum($tarifs), 2);
        $montantASouscrire = $this->appliquerLaRemise($tarifs, $remise);
        $totalSouscrit = round(array_sum($montantASouscrire), 2);

        // CONTROLE 1 — ce que la configuration reclame doit egaler ce que le
        // document dit avoir reclame. Un ecart ici veut dire que les tarifs de
        // KLASSCI ne sont pas ceux qui ont produit le document : on le signale,
        // on ne le rattrape pas.
        if (abs($totalSouscrit - $reclame) > self::EPSILON) {
            return $refuser('les tarifs configures ne reconstituent pas le montant reclame', [
                'tarifs_configures' => $totalTarifs,
                'remise' => $remise,
                'total_souscrit' => $totalSouscrit,
                'montant_reclame_pdf' => $reclame,
                'ecart' => round($totalSouscrit - $reclame, 2),
                'categories_sans_tarif' => $sansTarif,
            ]);
        }

        // Le paye eteint les frais dans l'ordre de l'ecole.
        $service = $this->servir->servir($paye, $montantASouscrire);
        $resteCalcule = round(array_sum($service['reste']), 2);

        // CONTROLE 2 — ce qui reste apres service doit egaler le reste du document.
        if (abs($resteCalcule - $resteAttendu) > self::EPSILON) {
            return $refuser('le reste calcule ne correspond pas au reste du document', [
                'reste_calcule' => $resteCalcule,
                'reste_du_pdf' => $resteAttendu,
                'ecart' => round($resteCalcule - $resteAttendu, 2),
            ]);
        }

        if ($service['reliquat'] > self::EPSILON) {
            return $refuser('un versement depasse ce que les frais reclament', [
                'excedent' => $service['reliquat'],
            ]);
        }

        $detail = [];
        foreach ($montantASouscrire as $categorieId => $montant) {
            $nom = $categories->firstWhere('id', $categorieId)?->name;
            $detail[] = [
                'categorie_id' => $categorieId,
                'categorie' => $nom,
                'souscrit' => $montant,
                'servi' => round($service['allocations'][$categorieId] ?? 0, 2),
                'reste' => round($service['reste'][$categorieId] ?? 0, 2),
            ];
        }

        return [
            'plan' => [
                'ligne' => $ligne,
                'classe' => $classe,
                'souscriptions' => $montantASouscrire,
                'allocations' => $service['allocations'],
            ],
            'apercu' => [
                'matricule' => $matricule,
                'nom' => $ligne['nom'] ?? null,
                'prenoms' => $ligne['prenoms'] ?? null,
                'classe' => $classe->name,
                'classe_id' => $classe->id,
                'montant_reclame' => $reclame,
                'remise' => $remise,
                'paye' => $paye,
                'reste_du' => $resteCalcule,
                'telephone_ecarte' => $ligne['telephone_anomalie'] ?? null,
                'detail' => $detail,
            ],
        ];
    }

    /**
     * Retranche la remise des frais les moins prioritaires.
     *
     * L'ordre de l'ecole dit ce qu'elle veut voir paye en premier. Une remise
     * soulage donc la fin de la liste, symetriquement au service. Elle n'est
     * jamais etalee au prorata : aucune categorie ne doit bouger sans raison.
     *
     * @param  array<int, float>  $tarifs
     * @return array<int, float>
     */
    private function appliquerLaRemise(array $tarifs, float $remise): array
    {
        if ($remise <= self::EPSILON) {
            return $tarifs;
        }

        $restant = round($remise, 2);
        foreach (array_reverse(array_keys($tarifs)) as $categorieId) {
            if ($restant <= self::EPSILON) {
                break;
            }
            $part = min($restant, $tarifs[$categorieId]);
            $tarifs[$categorieId] = round($tarifs[$categorieId] - $part, 2);
            $restant = round($restant - $part, 2);
        }

        // Une categorie ramenee a zero ne se souscrit pas : une souscription
        // nulle se confondrait avec une exemption.
        return array_filter($tarifs, fn ($m) => $m > self::EPSILON);
    }

    /**
     * Le tarif de l'ecole pour ce frais et cette classe, l'annee visee.
     *
     * Meme resolution que partout ailleurs : l'exception annuelle si elle existe,
     * sinon le tarif global, sinon le defaut de la categorie.
     */
    private function tarifPour(ESBTPFraisCategory $categorie, ESBTPClasse $classe, int $anneeId): float
    {
        $configuration = ESBTPFraisConfiguration::getApplicableConfiguration(
            $categorie->id,
            $classe->filiere_id,
            $classe->niveau_etude_id,
            $anneeId
        );

        if ($configuration) {
            return (float) $configuration->getMontantByStatus(
                ESBTPInscription::DEFAULT_AFFECTATION_STATUS
            );
        }

        return (float) ($categorie->default_amount ?? 0);
    }

    /**
     * @param  array<int, array>  $plans
     * @return array{etudiants: int, inscriptions: int, souscriptions: int, paiements: int}
     */
    private function ecrire(array $plans, int $anneeId): array
    {
        // `created_by` est NOT NULL sans defaut sur plusieurs de ces tables.
        // Lancee en console la commande n'a pas d'utilisateur authentifie, et
        // l'insertion echouerait sur un 1364.
        $auteur = auth()->id() ?? User::query()->min('id');

        return DB::transaction(function () use ($plans, $anneeId, $auteur): array {
            $compte = ['etudiants' => 0, 'inscriptions' => 0, 'souscriptions' => 0, 'paiements' => 0];

            foreach ($plans as $plan) {
                $ligne = $plan['ligne'];
                $classe = $plan['classe'];

                $etudiant = ESBTPEtudiant::firstOrNew(['matricule' => $ligne['matricule']]);
                if (! $etudiant->exists) {
                    $etudiant->fill([
                        'nom' => $ligne['nom'],
                        'prenoms' => $ligne['prenoms'],
                        // Le telephone n'est repris que s'il en est un. Le document
                        // porte parfois un nom de tuteur dans cette colonne.
                        'telephone' => $ligne['telephone'] ?? null,
                        // `actif` : ce sont precisement les eleves que l'ecole
                        // doit retrouver dans ses listes de reinscription.
                        'statut' => 'actif',
                        'created_by' => $auteur,
                    ]);
                    $etudiant->save();
                    $compte['etudiants']++;
                }

                $inscription = ESBTPInscription::firstOrNew([
                    'etudiant_id' => $etudiant->id,
                    'annee_universitaire_id' => $anneeId,
                ]);

                if (! $inscription->exists) {
                    $inscription->fill([
                        'filiere_id' => $classe->filiere_id,
                        'niveau_id' => $classe->niveau_etude_id,
                        'classe_id' => $classe->id,
                        'date_inscription' => $this->finDeLAnnee($anneeId),
                        'type_inscription' => NormalisationTypeInscription::PREMIERE,
                        'status' => 'active',
                        'workflow_step' => 'etudiant_cree',
                        // Colonnes historiques NOT NULL. On les tient coherentes
                        // avec les souscriptions, qui restent la seule verite.
                        'montant_scolarite' => array_sum($plan['souscriptions']),
                        'frais_inscription' => 0,
                        'observations' => 'Reprise de l\'annee ecoulee (arriere issu du logiciel precedent de l\'ecole).',
                        'created_by' => $auteur,
                    ]);
                    $inscription->save();
                    $compte['inscriptions']++;
                }

                foreach ($plan['souscriptions'] as $categorieId => $montant) {
                    $souscription = ESBTPFraisSubscription::firstOrNew([
                        'inscription_id' => $inscription->id,
                        'frais_category_id' => $categorieId,
                    ]);
                    if ($souscription->exists) {
                        continue;
                    }
                    $souscription->fill([
                        'amount' => $montant,
                        'is_active' => true,
                        'subscribed_at' => $this->finDeLAnnee($anneeId),
                        'created_by' => $auteur,
                        'notes' => $this->noteSouscription($ligne),
                    ]);
                    $souscription->save();
                    $compte['souscriptions']++;
                }

                foreach ($plan['allocations'] as $categorieId => $montant) {
                    if ($montant <= self::EPSILON) {
                        continue;
                    }
                    $reference = 'REPRISE-'.$ligne['matricule'];
                    $existe = ESBTPPaiement::where('inscription_id', $inscription->id)
                        ->where('frais_category_id', $categorieId)
                        ->where('reference_paiement', $reference)
                        ->exists();
                    if ($existe) {
                        continue;
                    }

                    ESBTPPaiement::create([
                        'inscription_id' => $inscription->id,
                        'etudiant_id' => $etudiant->id,
                        'annee_universitaire_id' => $anneeId,
                        'frais_category_id' => $categorieId,
                        'montant' => $montant,
                        'date_paiement' => $this->finDeLAnnee($anneeId),
                        'status' => 'validé',
                        'reference_paiement' => $reference,
                        // Surtout pas 'reliquat' : ce type est exclu des totaux
                        // par frais via ESBTPPaiement::horsReliquat().
                        'type_paiement' => null,
                        'motif' => 'Reprise de l\'annee ecoulee — encaissements deja realises par l\'ecole',
                        'created_by' => $auteur,
                    ]);
                    $compte['paiements']++;
                }
            }

            return $compte;
        });
    }

    private function noteSouscription(array $ligne): string
    {
        $note = 'Reprise de l\'annee ecoulee, montant issu des tarifs configures.';

        if ((float) ($ligne['reduction'] ?? 0) > 0) {
            $note .= sprintf(
                ' Remise de %s F accordee par l\'ecole : le montant reclame passe de %s F a %s F.'
                .' Enregistree en reduction du montant souscrit, et non en avoir :'
                .' aucune somme n\'est sortie de la caisse.',
                number_format((float) $ligne['reduction'], 0, ',', ' '),
                number_format((float) $ligne['total_frais'], 0, ',', ' '),
                number_format((float) $ligne['montant_reclame'], 0, ',', ' ')
            );
        }

        return $note;
    }

    private function finDeLAnnee(int $anneeId): string
    {
        $annee = \App\Models\ESBTPAnneeUniversitaire::find($anneeId);

        return $annee?->end_date
            ? \Illuminate\Support\Carbon::parse($annee->end_date)->toDateString()
            : now()->toDateString();
    }
}
