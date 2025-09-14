<?php

namespace App\Services;

use App\Models\ESBTPInscription;
use App\Models\ESBTPReliquatDetail;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPFraisSubscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReliquatPaymentDispatchService
{
    /**
     * Dispatcher intelligemment un paiement entre reliquats et frais courants
     *
     * @param ESBTPPaiement $paiement
     * @param ESBTPInscription $inscription
     * @return array Détail de la répartition
     */
    public function dispatcherPaiement(ESBTPPaiement $paiement, ESBTPInscription $inscription)
    {
        $montantRestant = $paiement->montant;
        $repartition = [
            'reliquats_payes' => [],
            'frais_courants_payes' => [],
            'montant_initial' => $paiement->montant,
            'montant_dispatch' => 0
        ];

        DB::beginTransaction();
        try {
            // Étape 1 : Prioriser le paiement des reliquats
            $reliquatsActifs = ESBTPReliquatDetail::where('inscription_destination_id', $inscription->id)
                ->where('is_active', true)
                ->where('montant_restant', '>', 0)
                ->orderBy('created_at', 'asc') // FIFO pour les reliquats
                ->get();

            foreach ($reliquatsActifs as $reliquat) {
                if ($montantRestant <= 0) break;

                $montantApplicable = min($montantRestant, $reliquat->montant_restant);

                // Mettre à jour le reliquat
                $reliquat->montant_paye += $montantApplicable;
                $reliquat->montant_restant -= $montantApplicable;
                $reliquat->updated_at = now();

                if ($reliquat->montant_restant <= 0) {
                    $reliquat->is_solde = true;
                    $reliquat->date_solde = now();
                }

                $reliquat->save();

                $repartition['reliquats_payes'][] = [
                    'reliquat_id' => $reliquat->id,
                    'montant_paye' => $montantApplicable,
                    'reste_a_payer' => $reliquat->montant_restant,
                    'frais_category' => $reliquat->fraisSubscription->fraisCategory->name ?? 'N/A'
                ];

                $montantRestant -= $montantApplicable;
                $repartition['montant_dispatch'] += $montantApplicable;

                Log::info("Paiement reliquat", [
                    'reliquat_id' => $reliquat->id,
                    'montant' => $montantApplicable,
                    'reste_reliquat' => $reliquat->montant_restant
                ]);
            }

            // Étape 2 : Appliquer le reste aux frais courants de l'inscription
            if ($montantRestant > 0) {
                $fraisCourants = ESBTPFraisSubscription::where('inscription_id', $inscription->id)
                    ->where('is_active', true)
                    ->whereRaw('amount > montant_paye')
                    ->orderBy('is_mandatory', 'desc') // Prioriser les frais obligatoires
                    ->orderBy('created_at', 'asc')
                    ->get();

                foreach ($fraisCourants as $frais) {
                    if ($montantRestant <= 0) break;

                    $soldeRestantFrais = $frais->amount - $frais->montant_paye;
                    $montantApplicable = min($montantRestant, $soldeRestantFrais);

                    // Mettre à jour les frais courants
                    $frais->montant_paye += $montantApplicable;
                    $frais->save();

                    $repartition['frais_courants_payes'][] = [
                        'frais_id' => $frais->id,
                        'montant_paye' => $montantApplicable,
                        'reste_a_payer' => $frais->amount - $frais->montant_paye,
                        'frais_category' => $frais->fraisCategory->name ?? 'N/A'
                    ];

                    $montantRestant -= $montantApplicable;
                    $repartition['montant_dispatch'] += $montantApplicable;

                    Log::info("Paiement frais courant", [
                        'frais_id' => $frais->id,
                        'montant' => $montantApplicable,
                        'reste_frais' => $frais->amount - $frais->montant_paye
                    ]);
                }
            }

            // Étape 3 : Mettre à jour les totaux de l'inscription
            $this->mettreAJourTotauxInscription($inscription);

            DB::commit();

            Log::info("Dispatch paiement terminé", [
                'inscription_id' => $inscription->id,
                'montant_initial' => $paiement->montant,
                'montant_dispatch' => $repartition['montant_dispatch'],
                'reliquats_payes_count' => count($repartition['reliquats_payes']),
                'frais_courants_payes_count' => count($repartition['frais_courants_payes'])
            ]);

            return $repartition;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur dispatch paiement", [
                'inscription_id' => $inscription->id,
                'paiement_id' => $paiement->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Recalculer et mettre à jour les totaux d'une inscription
     */
    private function mettreAJourTotauxInscription(ESBTPInscription $inscription)
    {
        // Total des frais courants
        $totalFraisCourants = ESBTPFraisSubscription::where('inscription_id', $inscription->id)
            ->where('is_active', true)
            ->sum('amount');

        $totalPayeFraisCourants = ESBTPFraisSubscription::where('inscription_id', $inscription->id)
            ->where('is_active', true)
            ->sum('montant_paye');

        // Total des reliquats
        $totalReliquats = ESBTPReliquatDetail::where('inscription_destination_id', $inscription->id)
            ->where('is_active', true)
            ->sum('montant_restant_initial');

        $totalPayeReliquats = ESBTPReliquatDetail::where('inscription_destination_id', $inscription->id)
            ->where('is_active', true)
            ->sum('montant_paye');

        // Mettre à jour l'inscription
        $inscription->montant_total_attendu = $totalFraisCourants + $totalReliquats;
        $inscription->montant_total_paye = $totalPayeFraisCourants + $totalPayeReliquats;
        $inscription->solde_restant = $inscription->montant_total_attendu - $inscription->montant_total_paye;
        $inscription->save();

        Log::info("Totaux inscription mis à jour", [
            'inscription_id' => $inscription->id,
            'total_attendu' => $inscription->montant_total_attendu,
            'total_paye' => $inscription->montant_total_paye,
            'solde_restant' => $inscription->solde_restant
        ]);
    }

    /**
     * Synchroniser les reliquats suite à un paiement sur l'inscription source
     */
    public function synchroniserReliquatsDepuisSource(ESBTPInscription $inscriptionSource)
    {
        $reliquatsAffecter = ESBTPReliquatDetail::where('inscription_source_id', $inscriptionSource->id)
            ->where('is_active', true)
            ->get();

        DB::beginTransaction();
        try {
            foreach ($reliquatsAffecter as $reliquat) {
                // Recalculer le montant restant basé sur l'inscription source
                $fraisSource = ESBTPFraisSubscription::find($reliquat->frais_subscription_id);

                if ($fraisSource) {
                    $nouveauSoldeSource = $fraisSource->amount - $fraisSource->montant_paye;

                    if ($nouveauSoldeSource <= 0 && $reliquat->montant_restant > 0) {
                        // Le frais source est maintenant soldé, mettre à jour le reliquat
                        $reliquat->montant_restant = 0;
                        $reliquat->is_solde = true;
                        $reliquat->date_solde = now();
                        $reliquat->notes = ($reliquat->notes ?? '') . " | Soldé par paiement sur inscription source le " . now()->format('Y-m-d H:i:s');
                        $reliquat->save();

                        // Mettre à jour l'inscription destination
                        $this->mettreAJourTotauxInscription($reliquat->inscriptionDestination);

                        Log::info("Reliquat synchronisé depuis source", [
                            'reliquat_id' => $reliquat->id,
                            'inscription_source_id' => $inscriptionSource->id,
                            'nouveau_solde' => 0
                        ]);
                    }
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur synchronisation reliquats", [
                'inscription_source_id' => $inscriptionSource->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Obtenir un résumé des reliquats pour une inscription
     */
    public function getResumeReliquats(ESBTPInscription $inscription)
    {
        $reliquats = ESBTPReliquatDetail::where('inscription_destination_id', $inscription->id)
            ->where('is_active', true)
            ->with(['fraisSubscription.fraisCategory', 'inscriptionSource.etudiant'])
            ->get();

        $resume = [
            'total_reliquats' => $reliquats->sum('montant_restant_initial'),
            'total_paye_reliquats' => $reliquats->sum('montant_paye'),
            'total_restant_reliquats' => $reliquats->sum('montant_restant'),
            'nb_reliquats_actifs' => $reliquats->where('montant_restant', '>', 0)->count(),
            'nb_reliquats_soldes' => $reliquats->where('is_solde', true)->count(),
            'details' => $reliquats->map(function($reliquat) {
                return [
                    'id' => $reliquat->id,
                    'frais_name' => $reliquat->fraisSubscription->fraisCategory->name ?? 'N/A',
                    'montant_initial' => $reliquat->montant_restant_initial,
                    'montant_paye' => $reliquat->montant_paye,
                    'montant_restant' => $reliquat->montant_restant,
                    'is_solde' => $reliquat->is_solde,
                    'date_creation' => $reliquat->created_at->format('Y-m-d'),
                    'inscription_source_annee' => $reliquat->inscriptionSource->annee_academique ?? 'N/A'
                ];
            })
        ];

        return $resume;
    }
}