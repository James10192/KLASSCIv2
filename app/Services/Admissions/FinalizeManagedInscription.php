<?php

namespace App\Services\Admissions;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPCandidatureWorkflow;
use App\Models\ESBTPFacture;
use App\Models\ESBTPFactureDetail;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPInscriptionPiece;
use App\Models\ESBTPPieceDeposee;
use App\Services\CataloguePiecesDossier;
use App\Services\ESBTPInscriptionService;
use App\Services\InscriptionWorkflowService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Source canonique de finalisation d'un dossier d'admission déjà rattaché à
 * un étudiant provisoire.
 *
 * La candidature, le paiement et les pièces existent AVANT l'inscription.
 * Cette classe est donc volontairement distincte de createInscription(), qui
 * crée lui-même l'étudiant. En revanche elle réutilise les mêmes services de
 * frais et le même InscriptionWorkflowService pour ne pas dupliquer les règles
 * financières, la capacité de classe ou la conversion prospect -> étudiant.
 */
final class FinalizeManagedInscription
{
    public function __construct(
        private readonly ESBTPInscriptionService $inscriptions,
        private readonly InscriptionWorkflowService $workflow,
        private readonly CataloguePiecesDossier $catalogue,
    ) {
    }

    public function handle(ESBTPCandidatureWorkflow $workflow, ?int $userId = null): ESBTPInscription
    {
        return DB::transaction(function () use ($workflow, $userId) {
            $workflow = ESBTPCandidatureWorkflow::query()
                ->lockForUpdate()
                ->with(['candidature', 'etudiant.user', 'paiement', 'selectedClass'])
                ->findOrFail($workflow->id);

            if ($workflow->final_inscription_id) {
                return ESBTPInscription::findOrFail($workflow->final_inscription_id);
            }

            $this->assertReady($workflow);

            $candidature = $workflow->candidature;
            $etudiant = $workflow->etudiant;
            $classe = $workflow->selectedClass;
            $anneeId = $candidature->annee_universitaire_id
                ?: ESBTPAnneeUniversitaire::query()->where('is_current', true)->value('id');

            if (! $anneeId) {
                throw ValidationException::withMessages([
                    'annee' => "Aucune année universitaire n'est disponible pour finaliser l'inscription.",
                ]);
            }

            $dejaInscrit = ESBTPInscription::query()
                ->where('etudiant_id', $etudiant->id)
                ->where('annee_universitaire_id', $anneeId)
                ->whereNotIn('status', ['annulee', 'annulée'])
                ->first();

            if ($dejaInscrit) {
                throw ValidationException::withMessages([
                    'inscription' => "Cet étudiant possède déjà une inscription pour l'année universitaire sélectionnée.",
                ]);
            }

            $disponibilite = $this->workflow->checkClassAvailability($classe->id);
            if (! ($disponibilite['available'] ?? false)) {
                throw ValidationException::withMessages([
                    'classe_id' => $disponibilite['message'] ?? "La classe sélectionnée n'est plus disponible.",
                ]);
            }

            $inscription = ESBTPInscription::create([
                'etudiant_id' => $etudiant->id,
                'annee_universitaire_id' => $anneeId,
                'filiere_id' => $classe->filiere_id,
                'niveau_id' => $classe->niveau_etude_id,
                'classe_id' => $classe->id,
                'affectation_status' => $candidature->affectation_status ?: ESBTPInscription::DEFAULT_AFFECTATION_STATUS,
                'date_inscription' => now()->toDateString(),
                'type_inscription' => 'PREMIERE',
                'status' => 'en_attente',
                'workflow_step' => 'en_validation',
                'paiement_validation_id' => $workflow->paiement_id,
                'comptabilite_activee' => true,
                'statut_etablissement' => ESBTPInscription::STATUT_ETABLISSEMENT_NOUVEAU,
                'est_transfert' => (bool) $candidature->est_transfert,
                'etablissement_origine' => $candidature->etablissement_sup_origine ?: $candidature->etablissement_origine,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            // Le paiement de préinscription précède l'inscription académique :
            // on le rattache maintenant sans recréer une seconde écriture.
            $workflow->paiement->forceFill([
                'inscription_id' => $inscription->id,
                'updated_by' => $userId,
            ])->save();

            // Même moteur de frais que l'inscription historique. La source des
            // barèmes reste donc unique, y compris pour les options et statuts.
            $generatedFees = $this->inscriptions->generateFeesForInscription(
                $inscription,
                [],
                $inscription->affectation_status ?: ESBTPInscription::DEFAULT_AFFECTATION_STATUS,
            );
            $this->inscriptions->saveGeneratedFeesAsSubscriptions($inscription, $generatedFees);

            $this->createInvoice($inscription, $userId);
            $this->attachProvisionalDocuments($workflow, $inscription, $userId);

            $etudiant->forceFill([
                'classe_id' => $classe->id,
                'filiere_id' => $classe->filiere_id,
                'niveau_etude_id' => $classe->niveau_etude_id,
                'annee_universitaire_id' => $anneeId,
                'statut' => 'actif',
                'updated_by' => $userId,
            ])->save();

            // Dernière porte canonique : contrôle financier, capacité de classe
            // et transition prospect -> étudiant restent ceux du flux existant.
            $converted = $this->workflow->convertProspectToStudent(
                $inscription->fresh(['etudiant.user', 'classe']),
                'Finalisation du parcours de candidature en ligne',
            );

            if (! ($converted['success'] ?? false)) {
                throw ValidationException::withMessages([
                    'finalisation' => $converted['message'] ?? "L'inscription n'a pas pu être finalisée.",
                ]);
            }

            $candidature->forceFill([
                'statut' => ESBTPCandidature::STATUT_CONVERTIE,
                'etudiant_id' => $etudiant->id,
                'inscription_id' => $inscription->id,
                'traite_at' => now(),
                'traite_par' => $userId,
            ])->save();

            $workflow->forceFill([
                'final_inscription_id' => $inscription->id,
                'state' => ESBTPCandidatureWorkflow::STATE_COMPLETED,
            ])->save();

            return $inscription->fresh();
        });
    }

    private function assertReady(ESBTPCandidatureWorkflow $workflow): void
    {
        if (! $workflow->paymentRecorded()) {
            throw ValidationException::withMessages(['paiement' => 'Le paiement de préinscription doit être validé.']);
        }
        if (! $workflow->documentsValidated()) {
            throw ValidationException::withMessages(['pieces' => 'Le contrôle physique des pièces doit être terminé.']);
        }
        if (! $workflow->accessActivated()) {
            throw ValidationException::withMessages(['activation' => "L'espace étudiant doit d'abord être activé."]);
        }
        if (! $workflow->profileCompleted()) {
            throw ValidationException::withMessages(['profil' => "L'étudiant doit compléter ses informations avant la finalisation."]);
        }
        if (! $workflow->selected_class_id || ! $workflow->selectedClass) {
            throw ValidationException::withMessages(['classe_id' => 'Une classe doit être choisie.']);
        }
        if (! $workflow->etudiant || ! $workflow->candidature || ! $workflow->paiement) {
            throw ValidationException::withMessages(['dossier' => "Le dossier provisoire est incomplet et ne peut pas être converti."]);
        }
    }

    private function createInvoice(ESBTPInscription $inscription, ?int $userId): ESBTPFacture
    {
        $subscriptions = ESBTPFraisSubscription::with(['fraisCategory', 'selectedOption'])
            ->where('inscription_id', $inscription->id)
            ->charged()
            ->get();

        $facture = new ESBTPFacture();
        $facture->numero_facture = 'FAC-'.date('Ymd').'-'.str_pad((string) $inscription->id, 5, '0', STR_PAD_LEFT);
        $facture->etudiant_id = $inscription->etudiant_id;
        $facture->inscription_id = $inscription->id;
        $facture->annee_universitaire_id = $inscription->annee_universitaire_id;
        $facture->date_emission = now();
        $facture->date_echeance = now()->addDays(15);
        $facture->montant_ht = $subscriptions->sum('amount');
        $facture->taux_taxe = 0;
        $facture->montant_taxe = 0;
        $facture->montant_ttc = $facture->montant_ht;
        $facture->montant_regle = 0;
        $facture->montant_du = $facture->montant_ttc;
        $facture->statut = 'émise';
        $facture->notes = "Facture générée automatiquement à l'inscription";
        $facture->createur_id = $userId;
        $facture->save();

        foreach ($subscriptions as $subscription) {
            $designation = $subscription->fraisCategory->name ?? 'Frais';
            if ($subscription->selectedOption) {
                $designation .= ' - '.$subscription->selectedOption->name;
            }

            ESBTPFactureDetail::create([
                'facture_id' => $facture->id,
                'designation' => $designation,
                'description' => null,
                'quantite' => 1,
                'montant' => $subscription->amount,
                'total_ligne' => $subscription->amount,
                'prix_unitaire' => $subscription->amount ?? 0,
            ]);
        }

        return $facture;
    }

    private function attachProvisionalDocuments(
        ESBTPCandidatureWorkflow $workflow,
        ESBTPInscription $inscription,
        ?int $userId,
    ): void {
        ESBTPPieceDeposee::query()
            ->where('etudiant_id', $inscription->etudiant_id)
            ->whereNull('inscription_id')
            ->update([
                'inscription_id' => $inscription->id,
                'updated_by' => $userId,
                'updated_at' => now(),
            ]);

        foreach ($this->catalogue->pourInscription($inscription) as $piece) {
            $depose = ESBTPPieceDeposee::query()
                ->where('etudiant_id', $inscription->etudiant_id)
                ->where('inscription_id', $inscription->id)
                ->where('piece_dossier_id', $piece->id)
                ->get()
                ->filter(fn (ESBTPPieceDeposee $depot) => $depot->compteDansLeStock($piece))
                ->sum('quantite_deposee');

            ESBTPInscriptionPiece::updateOrCreate(
                [
                    'inscription_id' => $inscription->id,
                    'piece_dossier_id' => $piece->id,
                ],
                [
                    'etudiant_id' => $inscription->etudiant_id,
                    'quantite_consommee' => min((int) $depose, max(1, (int) $piece->exemplaires_par_inscription)),
                    'non_applicable' => false,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ],
            );
        }
    }
}
