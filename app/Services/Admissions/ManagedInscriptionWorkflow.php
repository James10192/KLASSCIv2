<?php

namespace App\Services\Admissions;

use App\Enums\EtatPieceDossier;
use App\Enums\StatutReservationRdv;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPCandidatureWorkflow;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPPieceDeposee;
use App\Models\User;
use App\Services\CataloguePiecesDossier;
use App\Services\DossierPiecesEtudiant;
use App\Services\FraisScopeResolver;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

/**
 * Parcours intermediaire d'une candidature acceptee jusqu'a l'inscription.
 *
 * Ce service ne remplace ni la candidature, ni la caisse, ni le catalogue des
 * pieces, ni le workflow d'inscription existants. Il les relie dans l'ordre
 * choisi par le tenant et garde le dossier transitoire necessaire avant que la
 * classe — donc l'inscription academique — existe.
 *
 * La finalisation (choix de classe atomique, frais, facture, conversion) vit
 * dans {@see FinalizeManagedInscription} et nulle part ailleurs.
 */
final class ManagedInscriptionWorkflow
{
    public function __construct(
        private readonly InscriptionWorkflowSettings $settings,
        private readonly CataloguePiecesDossier $catalogue,
        private readonly DossierPiecesEtudiant $dossiers,
        private readonly AdmissionActivationNotifier $notifier,
        private readonly FraisScopeResolver $scopes,
    ) {
    }

    /**
     * Dossier existant, quel que soit l'état de la candidature (un dossier
     * converti reste consultable), sinon création si la candidature est acceptée.
     */
    public function ensure(ESBTPCandidature $candidature): ESBTPCandidatureWorkflow
    {
        if (! $this->settings->usesManagedWorkflow()) {
            throw ValidationException::withMessages([
                'workflow' => "Le parcours d'inscription pilote n'est pas activé sur cet établissement.",
            ]);
        }

        $existant = ESBTPCandidatureWorkflow::query()->where('candidature_id', $candidature->id)->first();
        if ($existant) {
            return $existant;
        }

        if ($candidature->statut !== ESBTPCandidature::STATUT_ACCEPTEE) {
            throw ValidationException::withMessages([
                'candidature' => "La candidature doit être acceptée avant d'entrer dans le parcours d'inscription.",
            ]);
        }

        try {
            return ESBTPCandidatureWorkflow::create([
                'candidature_id' => $candidature->id,
                'state' => $this->initialState(),
            ]);
        } catch (QueryException $e) {
            if ((string) $e->getCode() !== '23000') {
                throw $e;
            }
            // Deux guichets ouvrent le même dossier au même instant : l'index
            // unique a tranché, on relit la ligne gagnante.
            return ESBTPCandidatureWorkflow::query()->where('candidature_id', $candidature->id)->firstOrFail();
        }
    }

    /**
     * Frais que la caisse peut encaisser pour ce dossier, lus dans la
     * configuration des frais du périmètre (jamais un montant saisi au hasard).
     *
     * @return Collection<int, array{category_id:int, name:string, amount:float}>
     */
    public function fraisEncaissables(ESBTPCandidature $candidature): Collection
    {
        $anneeId = $candidature->annee_universitaire_id;
        if (! $anneeId || ! $candidature->filiere_id || ! $candidature->niveau_id) {
            return collect();
        }

        $scopes = ESBTPClasse::query()
            ->with(['filiere', 'niveau', 'parcours.mention.domaine'])
            ->where('is_active', true)
            ->where('filiere_id', $candidature->filiere_id)
            ->where('niveau_etude_id', $candidature->niveau_id)
            ->get()
            ->map(fn (ESBTPClasse $classe) => $this->scopes->resolveForClasse($classe))
            ->whenEmpty(fn () => collect([[
                'systeme' => FraisScopeResolver::SYSTEME_BTS,
                'filiere_id' => $candidature->filiere_id,
                'niveau_id' => $candidature->niveau_id,
            ]]))
            ->unique(fn (array $s) => ($s['systeme'] ?? '').'|'.($s['filiere_id'] ?? '').'|'.($s['parcours_id'] ?? '').'|'.($s['niveau_id'] ?? ''));

        return $scopes
            ->flatMap(fn (array $scope) => ESBTPFraisConfiguration::getConfigurationsForScope($scope, (int) $anneeId, 'effective', true))
            ->filter(fn (ESBTPFraisConfiguration $c) => $c->fraisCategory)
            ->map(function (ESBTPFraisConfiguration $c) use ($candidature) {
                // Même tarif que celui que la souscription facturera : selon le
                // statut d'affectation du candidat (affecté, non affecté…).
                $c->montant_du_candidat = (float) $c->getMontantByStatus(
                    $candidature->affectation_status ?: ESBTPInscription::DEFAULT_AFFECTATION_STATUS
                );

                return $c;
            })
            ->filter(fn (ESBTPFraisConfiguration $c) => $c->montant_du_candidat > 0)
            ->groupBy('frais_category_id')
            ->map(fn (Collection $configs) => [
                'category_id' => (int) $configs->first()->frais_category_id,
                'name' => (string) $configs->first()->fraisCategory->name,
                // Classe pas encore choisie : le plafond est le plus élevé des
                // tarifs possibles ; la finalisation refuse un versement qui
                // dépasserait le tarif de la classe finalement choisie.
                'amount' => (float) $configs->max('montant_du_candidat'),
                'sort' => (int) ($configs->first()->fraisCategory->sort_order ?? 9999),
            ])
            ->sortBy('sort')
            ->values();
    }

    public function recordPayment(ESBTPCandidature $candidature, array $data, int $userId): ESBTPCandidatureWorkflow
    {
        $workflowId = $this->ensure($candidature)->id;

        return DB::transaction(function () use ($candidature, $data, $userId, $workflowId) {
            $workflow = ESBTPCandidatureWorkflow::query()->lockForUpdate()->findOrFail($workflowId);

            // Deux caissiers, ou un double clic : le second voit le paiement
            // du premier sous verrou et ne crée rien.
            if ($workflow->paymentRecorded()) {
                return $workflow->fresh(['paiement', 'etudiant']);
            }

            if ($workflow->final_inscription_id || $candidature->fresh()->statut !== ESBTPCandidature::STATUT_ACCEPTEE) {
                throw ValidationException::withMessages(['candidature' => "Ce dossier n'est plus en attente de préinscription."]);
            }

            $this->assertAppointment($candidature);

            $anneeId = $candidature->annee_universitaire_id;
            if (! $anneeId) {
                throw ValidationException::withMessages([
                    'annee' => "La candidature n'indique pas d'année universitaire : impossible d'encaisser sans deviner l'année.",
                ]);
            }

            $frais = $this->fraisEncaissables($candidature)->firstWhere('category_id', (int) ($data['frais_category_id'] ?? 0));
            if (! $frais) {
                throw ValidationException::withMessages([
                    'frais_category_id' => "Ce frais n'est pas configuré pour la filière, le niveau et l'année de ce dossier.",
                ]);
            }

            $montant = round((float) $data['montant'], 2);
            if ($montant <= 0 || $montant > $frais['amount']) {
                throw ValidationException::withMessages([
                    'montant' => 'Le montant doit être compris entre 1 et '.number_format($frais['amount'], 0, ',', ' ').' FCFA ('.$frais['name'].').',
                ]);
            }

            $etudiant = $this->ensureProvisionalStudent($candidature, $workflow, $userId);

            $paiement = new ESBTPPaiement([
                'inscription_id' => null,
                'etudiant_id' => $etudiant->id,
                'annee_universitaire_id' => $anneeId,
                'frais_category_id' => $frais['category_id'],
                'type_paiement' => 'inscription',
                'montant' => $montant,
                'reference_paiement' => $data['reference_paiement'] ?? null,
                'mode_paiement' => $data['mode_paiement'],
                'numero_transaction' => $data['numero_transaction'] ?? null,
                'date_paiement' => now()->toDateString(),
                // Colonnes réelles de esbtp_paiements : `statut` et `createur_id`
                // n'existent pas sur cette table (ils appartiennent aux factures),
                // les écrire faisait échouer chaque encaissement.
                'status' => 'validé',
                'motif' => 'Préinscription — '.$frais['name'],
                'numero_recu' => ESBTPPaiement::genererNumeroRecu('PRE'),
                'validateur_id' => $userId,
                'date_validation' => now(),
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
            // La colonne appartient au nouveau parcours et reste volontairement
            // hors du fillable historique du modèle Paiement.
            $paiement->candidature_id = $candidature->id;
            $paiement->save();

            $workflow->forceFill([
                'etudiant_id' => $etudiant->id,
                'paiement_id' => $paiement->id,
                'paid_at' => now(),
                'paid_by' => $userId,
            ])->save();

            $this->advance($workflow);
            $this->maybeIssueActivation($workflow);

            return $workflow->fresh(['paiement', 'etudiant']);
        });
    }

    /**
     * Etat des pieces AVANT l'inscription academique. Les depots sont ceux du
     * module existant (ESBTPPieceDeposee) et le catalogue reste l'unique source.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function provisionalPieces(ESBTPCandidatureWorkflow $workflow): Collection
    {
        $workflow->loadMissing('candidature', 'etudiant');
        $candidature = $workflow->candidature;
        $pieces = $this->catalogue->pourScope($candidature?->filiere_id, $candidature?->niveau_id);

        if ($pieces->isEmpty() || ! $workflow->etudiant_id) {
            return $pieces->map(fn ($piece) => [
                'piece' => $piece,
                'requis' => max(1, (int) $piece->exemplaires_par_inscription),
                'depose' => 0,
                'manquant' => max(1, (int) $piece->exemplaires_par_inscription),
                'satisfaite' => false,
                'depots' => collect(),
            ])->values();
        }

        $depots = ESBTPPieceDeposee::query()
            ->with('document:id,titre,file_name')
            ->where('etudiant_id', $workflow->etudiant_id)
            ->whereIn('piece_dossier_id', $pieces->pluck('id'))
            ->orderByDesc('date_depot')
            ->orderByDesc('id')
            ->get()
            ->groupBy('piece_dossier_id');

        return $pieces->map(function ($piece) use ($depots) {
            $lignes = collect($depots->get($piece->id, []));
            $valides = $lignes->filter(fn (ESBTPPieceDeposee $depot) => $depot->compteDansLeStock($piece));
            $depose = (int) $valides->sum('quantite_deposee');
            $requis = max(1, (int) $piece->exemplaires_par_inscription);
            $manquant = max(0, $requis - $depose);

            return [
                'piece' => $piece,
                'requis' => $requis,
                'depose' => $depose,
                'manquant' => $manquant,
                'satisfaite' => $manquant === 0,
                'depots' => $lignes,
            ];
        })->values();
    }

    /** @return list<string> libellés des pièces obligatoires encore incomplètes */
    public function missingMandatoryPieces(ESBTPCandidatureWorkflow $workflow): array
    {
        return $this->provisionalPieces($workflow)
            ->filter(fn (array $row) => $row['piece']->is_obligatoire && ! $row['satisfaite'])
            ->map(fn (array $row) => (string) $row['piece']->libelle)
            ->values()
            ->all();
    }

    public function receivePiece(
        ESBTPCandidatureWorkflow $workflow,
        int $pieceId,
        int $quantity,
        int $userId,
    ): ESBTPPieceDeposee {
        return DB::transaction(function () use ($workflow, $pieceId, $quantity, $userId) {
            // Verrou : deux clics sur la première pièce ne créent pas deux
            // dossiers étudiants provisoires.
            $workflow = ESBTPCandidatureWorkflow::query()->lockForUpdate()->with('candidature')->findOrFail($workflow->id);
            $this->assertNotFinalized($workflow);
            // Le rendez-vous précède TOUTE étape physique, que la caisse ou les
            // pièces viennent en premier.
            $this->assertAppointment($workflow->candidature);

            $etudiant = $this->ensureProvisionalStudent($workflow->candidature, $workflow, $userId);

            return $this->deposer($workflow, $etudiant, $pieceId, $quantity, $userId);
        });
    }

    private function deposer(
        ESBTPCandidatureWorkflow $workflow,
        ESBTPEtudiant $etudiant,
        int $pieceId,
        int $quantity,
        int $userId,
    ): ESBTPPieceDeposee {
        $piece = $this->catalogue
            ->pourScope($workflow->candidature->filiere_id, $workflow->candidature->niveau_id)
            ->firstWhere('id', $pieceId);

        if (! $piece) {
            throw ValidationException::withMessages(['piece_id' => "Cette pièce n'appartient pas au dossier demandé."]);
        }

        $etat = $this->dossiers->exigeUneRelecture()
            ? EtatPieceDossier::DEPOSEE
            : EtatPieceDossier::VALIDEE;

        return ESBTPPieceDeposee::create([
            'etudiant_id' => $etudiant->id,
            'piece_dossier_id' => $piece->id,
            'inscription_id' => null,
            'quantite_deposee' => max(1, $quantity),
            'etat' => $etat->value,
            'decidee_par' => $etat === EtatPieceDossier::VALIDEE ? $userId : null,
            'decidee_at' => $etat === EtatPieceDossier::VALIDEE ? now() : null,
            'date_depot' => now()->toDateString(),
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    public function decidePiece(
        ESBTPCandidatureWorkflow $workflow,
        ESBTPPieceDeposee $depot,
        bool $accepted,
        ?string $motif,
        int $userId,
    ): ESBTPPieceDeposee {
        $this->assertNotFinalized($workflow);

        if ((int) $depot->etudiant_id !== (int) $workflow->etudiant_id || $depot->inscription_id !== null) {
            throw ValidationException::withMessages(['piece' => "Ce dépôt n'appartient pas à ce dossier."]);
        }

        if (! $accepted && trim((string) $motif) === '') {
            throw ValidationException::withMessages(['motif' => 'Le motif est obligatoire pour refuser une pièce.']);
        }

        return DB::transaction(function () use ($workflow, $depot, $accepted, $motif, $userId) {
            $depot->forceFill([
                'etat' => $accepted ? EtatPieceDossier::VALIDEE->value : EtatPieceDossier::REFUSEE->value,
                'motif' => $accepted ? null : trim((string) $motif),
                'decidee_par' => $userId,
                'decidee_at' => now(),
                'updated_by' => $userId,
            ])->save();

            // Une pièce refusée après la validation globale rouvre le contrôle :
            // le dossier ne reste jamais « complet » sur une pièce rejetée.
            if (! $accepted && $workflow->documentsValidated() && $this->missingMandatoryPieces($workflow->fresh()) !== []) {
                $workflow->forceFill(['documents_validated_at' => null, 'documents_validated_by' => null])->save();
                $this->advance($workflow);
            }

            return $depot->fresh();
        });
    }

    public function validateDocuments(ESBTPCandidatureWorkflow $workflow, int $userId): ESBTPCandidatureWorkflow
    {
        $workflow->loadMissing('candidature');
        $this->assertNotFinalized($workflow);
        $this->assertAppointment($workflow->candidature);

        $missing = $this->missingMandatoryPieces($workflow);
        if ($missing !== []) {
            throw ValidationException::withMessages([
                'pieces' => 'Dossier incomplet : '.implode(', ', $missing).'.',
            ]);
        }

        $workflow->forceFill([
            'documents_validated_at' => now(),
            'documents_validated_by' => $userId,
        ])->save();

        $this->advance($workflow);
        $this->maybeIssueActivation($workflow);

        return $workflow->fresh();
    }

    /**
     * Crée un jeton à usage unique et l'envoie par e-mail si l'adresse a été
     * prouvée. Le jeton brut n'est jamais stocké. Régénérer invalide l'ancien
     * jeton e-mail ET les anciens liens WhatsApp (voir linkVersion()).
     *
     * @return array{token:string,url:string,email_sent:bool}
     */
    public function issueActivation(ESBTPCandidatureWorkflow $workflow): array
    {
        $workflow->loadMissing('candidature', 'etudiant.user');
        $this->ensureUserAccount($workflow);

        $token = Str::random(64);
        $workflow->forceFill([
            'activation_token_hash' => hash('sha256', $token),
            'activation_token_expires_at' => now()->addHours(48),
            'activation_token_used_at' => null,
        ]);
        // L'état suit les jalons : un lien émis après paiement ne fait pas
        // sauter le contrôle des pièces encore à faire.
        $workflow->state = self::stateFor($workflow, $this->settings->mode());
        $workflow->save();

        $url = route('esbtp.admissions.workflow.activation.form', ['token' => $token]);

        // Jamais d'envoi sous verrou ni avant validation : dans une transaction,
        // l'e-mail part après le commit (et pas du tout en cas d'annulation).
        if (DB::transactionLevel() > 0) {
            DB::afterCommit(fn () => $this->notifier->sendEmail($workflow, $url));

            return ['token' => $token, 'url' => $url, 'email_sent' => false];
        }

        return ['token' => $token, 'url' => $url, 'email_sent' => $this->notifier->sendEmail($workflow, $url)];
    }

    /**
     * Version courte du jeton en cours : posée dans les liens WhatsApp signés,
     * elle les rend caducs dès qu'un nouveau lien est émis.
     */
    public static function linkVersion(ESBTPCandidatureWorkflow $workflow): string
    {
        return substr((string) $workflow->activation_token_hash, 0, 16);
    }

    public function workflowForActivationToken(string $token, bool $lock = false): ESBTPCandidatureWorkflow
    {
        $workflow = ESBTPCandidatureWorkflow::query()
            ->with(['candidature', 'etudiant.user'])
            ->when($lock, fn ($q) => $q->lockForUpdate())
            ->where('activation_token_hash', hash('sha256', $token))
            ->first();

        if (! $workflow
            || $workflow->activation_token_used_at
            || $workflow->accessActivated()
            || ! $workflow->activation_token_expires_at
            || $workflow->activation_token_expires_at->isPast()) {
            throw ValidationException::withMessages(['token' => "Ce lien d'activation est invalide ou expiré."]);
        }

        return $workflow;
    }

    /** Activation par le lien reçu PAR E-MAIL : l'adresse est donc prouvée. */
    public function activate(string $token, string $password): ESBTPCandidatureWorkflow
    {
        return DB::transaction(function () use ($token, $password) {
            $workflow = $this->workflowForActivationToken($token, true);

            return app(AdmissionAccountActivator::class)->activateWorkflow($workflow, $password, emailProven: true);
        });
    }

    /**
     * Classes ouvertes au dossier : universelles (filière + niveau + actives),
     * places comptées sur l'année DU DOSSIER, en une seule requête groupée.
     *
     * @return Collection<int, ESBTPClasse>
     */
    public function eligibleClasses(ESBTPCandidatureWorkflow $workflow): Collection
    {
        $workflow->loadMissing('candidature');
        $c = $workflow->candidature;

        if (! $c || ! $c->filiere_id || ! $c->niveau_id || ! $c->annee_universitaire_id) {
            return collect();
        }

        $classes = ESBTPClasse::query()
            ->with(['filiere:id,name,code', 'niveau:id,name'])
            ->where('is_active', true)
            ->where('filiere_id', $c->filiere_id)
            ->where('niveau_etude_id', $c->niveau_id)
            ->orderBy('name')
            ->get();

        $occupees = ESBTPInscription::query()
            ->selectRaw('classe_id, COUNT(*) as total')
            ->whereIn('classe_id', $classes->pluck('id'))
            ->where('annee_universitaire_id', $c->annee_universitaire_id)
            ->where('status', 'active')
            ->where('workflow_step', 'etudiant_cree')
            ->groupBy('classe_id')
            ->pluck('total', 'classe_id');

        return $classes
            ->each(function (ESBTPClasse $classe) use ($occupees) {
                $classe->places_restantes = $classe->places_totales
                    ? max(0, (int) $classe->places_totales - (int) ($occupees[$classe->id] ?? 0))
                    : null;
            })
            ->filter(fn (ESBTPClasse $classe) => $classe->places_restantes === null || $classe->places_restantes > 0)
            ->values();
    }

    /**
     * Affectation par l'administration (mode « l'administration choisit »).
     * Rien n'est verrouillé : la place est réservée sous verrou au moment de la
     * finalisation. Interdit une fois l'inscription créée.
     */
    public function assignClassByAdministration(ESBTPCandidatureWorkflow $workflow, int $classId, int $userId): ESBTPCandidatureWorkflow
    {
        $this->assertNotFinalized($workflow->fresh());

        $classe = $this->eligibleClasses($workflow)->firstWhere('id', $classId);
        if (! $classe) {
            throw ValidationException::withMessages(['classe_id' => "Cette classe n'est pas disponible pour ce dossier."]);
        }

        $workflow->forceFill([
            'selected_class_id' => $classe->id,
            'class_selected_at' => now(),
            'class_selected_by' => $userId,
        ])->save();
        $this->advance($workflow);

        return $workflow->fresh('selectedClass');
    }

    public function belongsToAuthenticatedStudent(ESBTPCandidatureWorkflow $workflow, User $user): bool
    {
        return (int) ($workflow->etudiant?->user_id ?? 0) === (int) $user->id;
    }

    /**
     * L'activation sert soit après le paiement, soit après les pièces, selon le
     * réglage du tenant. Appelée par les deux transitions.
     */
    /**
     * Un agent confirme au guichet l'e-mail et le numéro de l'étudiant.
     *
     * Le lien d'activation ne part que vers un contact prouvé. Un dossier dont
     * le code de vérification n'a jamais été demandé (déposé avant que l'école
     * active la vérification, ou repris d'un dépôt antérieur) n'avait sinon
     * aucune issue : aucun lien, et la confirmation de la file des demandes le
     * déclarait « pas à confirmer ». L'étudiant est devant l'agent : c'est le
     * moment où le contact se vérifie de vive voix.
     */
    public function confirmContactAtDesk(ESBTPCandidatureWorkflow $workflow, int $agentId): void
    {
        $candidature = $workflow->candidature()->lockForUpdate()->firstOrFail();
        if ($candidature->contact_confirme_at !== null) {
            return;
        }

        $candidature->forceFill([
            'contact_confirme_at' => now(),
            'contact_confirme_par' => $agentId,
        ])->save();

        \Illuminate\Support\Facades\Log::info('Parcours d\'inscription : contact confirmé au guichet', [
            'candidature_id' => $candidature->id,
            'workflow_id' => $workflow->id,
            'agent_id' => $agentId,
        ]);

        $workflow->setRelation('candidature', $candidature);
    }

    public function maybeIssueActivation(ESBTPCandidatureWorkflow $workflow): void
    {
        if (! $this->activationMilestoneReached($workflow) || $workflow->accessActivated()) {
            return;
        }

        // Ne régénère pas un lien encore valable à chaque rafraîchissement.
        if ($workflow->activation_token_hash
            && $workflow->activation_token_expires_at
            && $workflow->activation_token_expires_at->isFuture()) {
            return;
        }

        $this->issueActivation($workflow);
    }

    public function activationMilestoneReached(ESBTPCandidatureWorkflow $workflow): bool
    {
        return match ($this->settings->accountActivationStep()) {
            InscriptionWorkflowSettings::ACTIVATION_AFTER_DOCUMENTS => $workflow->documentsValidated(),
            default => $workflow->paymentRecorded(),
        };
    }

    private function initialState(): string
    {
        return $this->settings->firstPhysicalStep() === 'pieces'
            ? ESBTPCandidatureWorkflow::STATE_AWAITING_DOCUMENTS
            : ESBTPCandidatureWorkflow::STATE_AWAITING_PAYMENT;
    }

    private function assertNotFinalized(ESBTPCandidatureWorkflow $workflow): void
    {
        if ($workflow->final_inscription_id) {
            throw ValidationException::withMessages([
                'workflow' => "L'inscription est finalisée : toute correction passe par la fiche d'inscription (changement de classe, pièces du dossier).",
            ]);
        }
    }

    private function assertAppointment(?ESBTPCandidature $candidature): void
    {
        if (! $candidature || ! $this->settings->requiresAppointment()) {
            return;
        }

        $hasRdv = $candidature->reservations()
            ->whereIn('statut', [StatutReservationRdv::Confirmee->value, StatutReservationRdv::Honoree->value])
            ->exists();

        if (! $hasRdv) {
            throw ValidationException::withMessages([
                'rendez_vous' => "Cette candidature n'a pas de rendez-vous confirmé ou déjà honoré.",
            ]);
        }
    }

    private function ensureProvisionalStudent(
        ESBTPCandidature $candidature,
        ESBTPCandidatureWorkflow $workflow,
        int $userId,
    ): ESBTPEtudiant {
        if ($workflow->etudiant_id && ($existing = ESBTPEtudiant::find($workflow->etudiant_id))) {
            return $existing;
        }

        if ($candidature->etudiant_id && ($existing = ESBTPEtudiant::find($candidature->etudiant_id))) {
            $workflow->forceFill(['etudiant_id' => $existing->id])->save();
            return $existing;
        }

        do {
            $matricule = 'PRE-'.strtoupper(Str::random(10));
        } while (ESBTPEtudiant::withTrashed()->where('matricule', $matricule)->exists());

        $etudiant = ESBTPEtudiant::create([
            'matricule' => $matricule,
            'nom' => $candidature->nom,
            'prenoms' => $candidature->prenoms,
            'sexe' => $candidature->sexe,
            'date_naissance' => $candidature->date_naissance,
            'lieu_naissance' => $candidature->lieu_naissance,
            'nationalite' => $candidature->nationalite,
            'telephone' => $candidature->telephone,
            'email' => $candidature->email,
            'email_personnel' => $candidature->email,
            'ville' => $candidature->ville,
            'commune' => $candidature->commune,
            'statut' => 'inactif',
            'annee_universitaire_id' => $candidature->annee_universitaire_id,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $workflow->forceFill(['etudiant_id' => $etudiant->id])->save();
        $candidature->forceFill(['etudiant_id' => $etudiant->id])->save();

        return $etudiant;
    }

    private function ensureUserAccount(ESBTPCandidatureWorkflow $workflow): User
    {
        $workflow->loadMissing('candidature', 'etudiant.user');
        $etudiant = $workflow->etudiant;

        if (! $etudiant) {
            throw ValidationException::withMessages(['compte' => "Le dossier étudiant provisoire n'existe pas encore."]);
        }

        if ($etudiant->user) {
            return $etudiant->user;
        }

        $prenom = Str::slug(Str::before($etudiant->prenoms, ' '), '');
        $nom = Str::slug($etudiant->nom, '');
        $base = trim($prenom.'.'.$nom, '.');
        $base = $base !== '' ? $base : 'etudiant';
        $username = $base;
        $suffix = 1;
        while (User::withTrashed()->where('username', $username)->exists()) {
            $username = $base.'.'.$suffix++;
        }

        $email = $workflow->candidature?->email;
        if ($email && User::withTrashed()->where('email', $email)->exists()) {
            // L'adresse sert déjà un autre compte : on n'en crée pas un second
            // sur la même adresse ; le lien passera par WhatsApp.
            $email = null;
        }

        $user = User::create([
            'name' => trim($etudiant->prenoms.' '.$etudiant->nom),
            'first_name' => $etudiant->prenoms,
            'last_name' => $etudiant->nom,
            'email' => $email,
            'username' => $username,
            // Secret aleatoire non communique : le vrai mot de passe est choisi
            // uniquement depuis le lien d'activation à usage unique.
            'password' => Hash::make(Str::random(64)),
            'phone' => $workflow->candidature?->telephone,
            'is_active' => false,
            'must_change_password' => true,
        ]);

        if ($role = Role::where('name', 'etudiant')->first()) {
            $user->assignRole($role);
        }

        $etudiant->forceFill(['user_id' => $user->id])->save();

        return $user;
    }

    private function advance(ESBTPCandidatureWorkflow $workflow): void
    {
        $workflow->refresh();

        $workflow->forceFill(['state' => self::stateFor($workflow, $this->settings->mode())])->save();
    }

    /**
     * État affiché, déduit des jalons : une seule règle pour tous les écrans
     * et pour l'activateur.
     */
    public static function stateFor(ESBTPCandidatureWorkflow $workflow, string $mode): string
    {
        if ($workflow->final_inscription_id) {
            return ESBTPCandidatureWorkflow::STATE_COMPLETED;
        }

        $paiementAvant = $mode !== InscriptionWorkflowSettings::MODE_PIECES_AVANT_CAISSE;
        if ($paiementAvant && ! $workflow->paymentRecorded()) {
            return ESBTPCandidatureWorkflow::STATE_AWAITING_PAYMENT;
        }
        if (! $workflow->documentsValidated()) {
            return ESBTPCandidatureWorkflow::STATE_AWAITING_DOCUMENTS;
        }
        if (! $workflow->paymentRecorded()) {
            return ESBTPCandidatureWorkflow::STATE_AWAITING_PAYMENT;
        }
        if (! $workflow->accessActivated()) {
            return ESBTPCandidatureWorkflow::STATE_AWAITING_ACTIVATION;
        }

        return $workflow->selected_class_id && $workflow->profileCompleted()
            ? ESBTPCandidatureWorkflow::STATE_READY_TO_FINALIZE
            : ESBTPCandidatureWorkflow::STATE_AWAITING_STUDENT;
    }
}
