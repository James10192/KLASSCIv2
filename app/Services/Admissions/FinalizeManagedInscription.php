<?php

namespace App\Services\Admissions;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPCandidatureWorkflow;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFacture;
use App\Models\ESBTPFactureDetail;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPInscriptionPiece;
use App\Models\ESBTPPaiement;
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
 *
 * Le choix de classe et la finalisation vivent dans LA MÊME transaction : la
 * classe n'est jamais verrouillée sur un dossier dont l'inscription n'a pas pu
 * être créée, et la ligne de la classe est verrouillée pendant le comptage des
 * places pour que deux confirmations simultanées ne prennent pas la même place.
 */
final class FinalizeManagedInscription
{
    public function __construct(
        private readonly ESBTPInscriptionService $inscriptions,
        private readonly InscriptionWorkflowService $workflow,
        private readonly CataloguePiecesDossier $catalogue,
        private readonly ManagedInscriptionWorkflow $managed,
        private readonly InscriptionWorkflowSettings $settings,
    ) {
    }

    /**
     * Choix de classe par l'étudiant puis finalisation, atomiquement.
     */
    public function chooseAndFinalize(ESBTPCandidatureWorkflow $workflow, int $classId, ?int $userId = null): ESBTPInscription
    {
        return DB::transaction(function () use ($workflow, $classId, $userId) {
            $workflow = $this->lockWorkflow($workflow);

            if ($workflow->final_inscription_id) {
                throw ValidationException::withMessages([
                    'classe_id' => 'Votre inscription est déjà finalisée.',
                ]);
            }

            if ($this->settings->classChoiceOnce() && $workflow->classIsLocked()) {
                throw ValidationException::withMessages([
                    'classe_id' => 'Votre choix de classe a déjà été confirmé.',
                ]);
            }

            $classe = $this->lockEligibleClass($workflow, $classId);

            $workflow->forceFill([
                'selected_class_id' => $classe->id,
                'class_selected_at' => now(),
                'class_selected_by' => $userId,
                'class_locked_at' => $this->settings->classChoiceOnce() ? now() : null,
            ])->save();
            $workflow->setRelation('selectedClass', $classe);

            return $this->finalizeLocked($workflow, $userId, $classe);
        });
    }

    public function handle(ESBTPCandidatureWorkflow $workflow, ?int $userId = null): ESBTPInscription
    {
        return DB::transaction(function () use ($workflow, $userId) {
            $workflow = $this->lockWorkflow($workflow);

            if ($workflow->final_inscription_id) {
                return ESBTPInscription::findOrFail($workflow->final_inscription_id);
            }

            if (! $workflow->selected_class_id) {
                throw ValidationException::withMessages(['classe_id' => 'Une classe doit être choisie.']);
            }

            $classe = $this->lockEligibleClass($workflow, (int) $workflow->selected_class_id);

            return $this->finalizeLocked($workflow, $userId, $classe);
        });
    }

    private function lockWorkflow(ESBTPCandidatureWorkflow $workflow): ESBTPCandidatureWorkflow
    {
        return ESBTPCandidatureWorkflow::query()
            ->lockForUpdate()
            ->with(['candidature', 'etudiant.user', 'paiement'])
            ->findOrFail($workflow->id);
    }

    /**
     * Revalide la classe sous verrou : active, du bon périmètre, et avec une
     * place libre pour l'année du dossier (pas l'année « courante » globale).
     */
    private function lockEligibleClass(ESBTPCandidatureWorkflow $workflow, int $classId): ESBTPClasse
    {
        $candidature = $workflow->candidature;
        $classe = ESBTPClasse::query()->lockForUpdate()->find($classId);

        if (! $classe
            || ! $classe->is_active
            || (int) $classe->filiere_id !== (int) $candidature?->filiere_id
            || (int) $classe->niveau_etude_id !== (int) $candidature?->niveau_id) {
            throw ValidationException::withMessages([
                'classe_id' => "Cette classe n'est pas disponible pour ce dossier.",
            ]);
        }

        $disponibilite = $this->workflow->checkClassAvailability($classe->id, $this->anneeId($candidature));
        if (! ($disponibilite['available'] ?? false)) {
            throw ValidationException::withMessages([
                'classe_id' => "Cette classe vient d'être complétée. Choisissez une autre classe.",
            ]);
        }

        return $classe;
    }

    private function finalizeLocked(ESBTPCandidatureWorkflow $workflow, ?int $userId, ESBTPClasse $classe): ESBTPInscription
    {
        // La conversion canonique journalise l'auteur (Auth::id(), entier
        // obligatoire) : sans session, elle lèverait une TypeError au milieu
        // de sa propre transaction.
        if (! \Illuminate\Support\Facades\Auth::check()) {
            throw ValidationException::withMessages(['finalisation' => 'Une session authentifiée est requise pour finaliser.']);
        }

        $this->assertReady($workflow);

        $candidature = $workflow->candidature;
        $etudiant = $workflow->etudiant;
        $anneeId = $this->anneeId($candidature);

        $dejaInscrit = ESBTPInscription::query()
            ->where('etudiant_id', $etudiant->id)
            ->where('annee_universitaire_id', $anneeId)
            ->whereNotIn('status', ['annulee', 'annulée'])
            ->exists();

        if ($dejaInscrit) {
            throw ValidationException::withMessages([
                'inscription' => "Cet étudiant possède déjà une inscription pour l'année universitaire sélectionnée.",
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
            // Seules valeurs admises par l'enum MySQL : 'première_inscription',
            // 'réinscription', 'transfert'. Toute autre valeur est refusée en
            // mode strict et faisait échouer chaque finalisation.
            'type_inscription' => 'première_inscription',
            'status' => 'en_attente',
            'workflow_step' => 'en_validation',
            'paiement_validation_id' => $workflow->paiement_id,
            // Colonnes NOT NULL sans défaut : le flux historique les pose à 0 et
            // laisse les souscriptions porter les montants réels.
            'montant_scolarite' => 0,
            'frais_inscription' => 0,
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

        $this->assertPaymentLandsOnAFee($inscription, $workflow->paiement);

        $this->createInvoice($inscription, $userId);
        $this->attachProvisionalDocuments($inscription, $userId);

        $etudiant->forceFill([
            // esbtp_etudiants ne porte ni filière ni niveau : ils vivent sur
            // l'inscription et la classe.
            'classe_id' => $classe->id,
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
    }

    private function anneeId(?ESBTPCandidature $candidature): int
    {
        $anneeId = $candidature?->annee_universitaire_id
            ?: ESBTPAnneeUniversitaire::query()->where('is_current', true)->value('id');

        if (! $anneeId) {
            throw ValidationException::withMessages([
                'annee' => "Aucune année universitaire n'est disponible pour finaliser l'inscription.",
            ]);
        }

        return (int) $anneeId;
    }

    private function assertReady(ESBTPCandidatureWorkflow $workflow): void
    {
        if (! $workflow->etudiant || ! $workflow->candidature || ! $workflow->paiement) {
            throw ValidationException::withMessages(['dossier' => "Le dossier provisoire est incomplet et ne peut pas être converti."]);
        }
        if (! $workflow->paymentRecorded() || $workflow->paiement->status !== 'validé') {
            throw ValidationException::withMessages(['paiement' => 'Le paiement de préinscription doit être validé.']);
        }
        if (! $workflow->documentsValidated()) {
            throw ValidationException::withMessages(['pieces' => 'Le contrôle physique des pièces doit être terminé.']);
        }
        // Une pièce refusée ou retirée APRÈS la validation globale ne doit pas
        // passer : la complétude est recalculée au moment de convertir.
        $manquantes = $this->managed->missingMandatoryPieces($workflow);
        if ($manquantes !== []) {
            throw ValidationException::withMessages([
                'pieces' => 'Dossier physique devenu incomplet : '.implode(', ', $manquantes).'.',
            ]);
        }
        if (! $workflow->accessActivated()) {
            throw ValidationException::withMessages(['activation' => "L'espace étudiant doit d'abord être activé."]);
        }
        if (! $workflow->profileCompleted()) {
            throw ValidationException::withMessages(['profil' => "L'étudiant doit compléter ses informations avant la finalisation."]);
        }
    }

    /**
     * Le solde d'un étudiant se calcule PAR FRAIS. Un versement dont le frais
     * n'a pas été souscrit par l'inscription ne serait déduit de rien : le
     * dossier afficherait la totalité comme restant due. On refuse plutôt que
     * de produire ce faux solde.
     */
    private function assertPaymentLandsOnAFee(ESBTPInscription $inscription, ESBTPPaiement $paiement): void
    {
        $categoryId = (int) $paiement->frais_category_id;

        $souscrit = $categoryId > 0 && ESBTPFraisSubscription::query()
            ->where('inscription_id', $inscription->id)
            ->where('frais_category_id', $categoryId)
            ->charged()
            ->exists();

        if (! $souscrit) {
            throw ValidationException::withMessages([
                'paiement' => "Le versement de préinscription n'est rattaché à aucun frais de cette classe. "
                    .'Un agent comptable doit le ventiler avant la finalisation.',
            ]);
        }
    }

    private function createInvoice(ESBTPInscription $inscription, ?int $userId): ESBTPFacture
    {
        $subscriptions = ESBTPFraisSubscription::with(['fraisCategory', 'selectedOption'])
            ->where('inscription_id', $inscription->id)
            ->charged()
            ->get();

        $paye = ESBTPPaiement::netPaidByCategory($inscription->id);
        $regle = $subscriptions->sum(fn (ESBTPFraisSubscription $s) => min(
            (float) $s->chargedAmount(),
            (float) ($paye[$s->frais_category_id] ?? 0)
        ));

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
        // Le versement de préinscription est déjà encaissé : la facture le
        // montre comme réglé au lieu d'annoncer la totalité restant due.
        $facture->montant_regle = round($regle, 2);
        $facture->montant_du = max(0, round($facture->montant_ttc - $regle, 2));
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

    private function attachProvisionalDocuments(ESBTPInscription $inscription, ?int $userId): void
    {
        ESBTPPieceDeposee::query()
            ->where('etudiant_id', $inscription->etudiant_id)
            ->whereNull('inscription_id')
            ->update([
                'inscription_id' => $inscription->id,
                'updated_by' => $userId,
                'updated_at' => now(),
            ]);

        $depots = ESBTPPieceDeposee::query()
            ->where('etudiant_id', $inscription->etudiant_id)
            ->where('inscription_id', $inscription->id)
            ->get()
            ->groupBy('piece_dossier_id');

        foreach ($this->catalogue->pourInscription($inscription) as $piece) {
            $depose = collect($depots->get($piece->id, []))
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
