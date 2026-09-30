<?php

namespace App\Services\Admissions;

use App\Enums\EtatPieceDossier;
use App\Enums\StatutReservationRdv;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPCandidatureWorkflow;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\ESBTPInscriptionPiece;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPPieceDeposee;
use App\Models\User;
use App\Services\CataloguePiecesDossier;
use App\Services\DossierPiecesEtudiant;
use App\Services\ESBTPInscriptionService;
use App\Services\InscriptionWorkflowService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
 */
final class ManagedInscriptionWorkflow
{
    public function __construct(
        private readonly InscriptionWorkflowSettings $settings,
        private readonly CataloguePiecesDossier $catalogue,
        private readonly DossierPiecesEtudiant $dossiers,
        private readonly ESBTPInscriptionService $inscriptions,
        private readonly InscriptionWorkflowService $workflow,
    ) {
    }

    public function ensure(ESBTPCandidature $candidature): ESBTPCandidatureWorkflow
    {
        if (! $this->settings->usesManagedWorkflow()) {
            throw ValidationException::withMessages([
                'workflow' => "Le parcours d'inscription pilote n'est pas activé sur cet établissement.",
            ]);
        }

        if ($candidature->statut !== ESBTPCandidature::STATUT_ACCEPTEE) {
            throw ValidationException::withMessages([
                'candidature' => "La candidature doit être acceptée avant d'entrer dans le parcours d'inscription.",
            ]);
        }

        return ESBTPCandidatureWorkflow::firstOrCreate(
            ['candidature_id' => $candidature->id],
            ['state' => $this->initialState()]
        );
    }

    /** @return Collection<int, ESBTPCandidature> */
    public function cashierQueue(): Collection
    {
        if (! $this->settings->usesManagedWorkflow()) {
            return collect();
        }

        $candidatures = ESBTPCandidature::query()
            ->with(['filiere:id,name,code', 'niveau:id,name', 'anneeUniversitaire:id,name'])
            ->where('statut', ESBTPCandidature::STATUT_ACCEPTEE)
            ->whereNull('inscription_id')
            ->orderBy('traite_at')
            ->orderBy('id')
            ->get();

        if ($this->settings->mode() !== InscriptionWorkflowSettings::MODE_CAISSE_AVANT_PIECES) {
            return $candidatures;
        }

        $paid = ESBTPCandidatureWorkflow::query()
            ->whereIn('candidature_id', $candidatures->pluck('id'))
            ->whereNotNull('paid_at')
            ->pluck('candidature_id')
            ->all();

        return $candidatures
            ->reject(fn (ESBTPCandidature $c) => in_array($c->id, $paid, true))
            ->values();
    }

    public function recordPayment(ESBTPCandidature $candidature, array $data, int $userId): ESBTPCandidatureWorkflow
    {
        return DB::transaction(function () use ($candidature, $data, $userId) {
            $workflow = $this->ensure($candidature)->newQuery()->lockForUpdate()->findOrFail(
                $this->ensure($candidature)->id
            );

            if ($workflow->paymentRecorded()) {
                return $workflow->fresh(['paiement', 'etudiant']);
            }

            $this->assertAppointment($candidature);
            $etudiant = $this->ensureProvisionalStudent($candidature, $workflow, $userId);
            $anneeId = $candidature->annee_universitaire_id
                ?: ESBTPAnneeUniversitaire::query()->where('is_current', true)->value('id');

            if (! $anneeId) {
                throw ValidationException::withMessages([
                    'annee' => "Aucune année universitaire n'est disponible pour enregistrer la préinscription.",
                ]);
            }

            $paiement = new ESBTPPaiement([
                'inscription_id' => null,
                'etudiant_id' => $etudiant->id,
                'annee_universitaire_id' => $anneeId,
                'type_paiement' => 'preinscription',
                'montant' => (float) $data['montant'],
                'reference_paiement' => $data['reference_paiement'] ?? ESBTPPaiement::genererNumeroRecu('PRE'),
                'mode_paiement' => $data['mode_paiement'],
                'numero_transaction' => $data['numero_transaction'] ?? null,
                'date_paiement' => now()->toDateString(),
                'statut' => 'completé',
                'status' => 'validé',
                'motif' => 'Préinscription',
                'numero_recu' => ESBTPPaiement::genererNumeroRecu('PRE'),
                'createur_id' => $userId,
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

    public function receivePiece(
        ESBTPCandidatureWorkflow $workflow,
        int $pieceId,
        int $quantity,
        int $userId,
    ): ESBTPPieceDeposee {
        $workflow->loadMissing('candidature');
        $etudiant = $workflow->etudiant ?: $this->ensureProvisionalStudent(
            $workflow->candidature,
            $workflow,
            $userId,
        );

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
        if ((int) $depot->etudiant_id !== (int) $workflow->etudiant_id) {
            throw ValidationException::withMessages(['piece' => "Ce dépôt n'appartient pas à ce dossier."]);
        }

        if (! $accepted && trim((string) $motif) === '') {
            throw ValidationException::withMessages(['motif' => 'Le motif est obligatoire pour refuser une pièce.']);
        }

        $depot->forceFill([
            'etat' => $accepted ? EtatPieceDossier::VALIDEE->value : EtatPieceDossier::REFUSEE->value,
            'motif' => $accepted ? null : trim((string) $motif),
            'decidee_par' => $userId,
            'decidee_at' => now(),
            'updated_by' => $userId,
        ])->save();

        return $depot->fresh();
    }

    public function validateDocuments(ESBTPCandidatureWorkflow $workflow, int $userId): ESBTPCandidatureWorkflow
    {
        $pieces = $this->provisionalPieces($workflow);
        $missing = $pieces
            ->filter(fn (array $row) => $row['piece']->is_obligatoire && ! $row['satisfaite'])
            ->map(fn (array $row) => $row['piece']->libelle)
            ->values();

        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'pieces' => 'Dossier incomplet : '.implode(', ', $missing->all()).'.',
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
     * Crée un jeton à usage unique et tente l'envoi par e-mail. Le jeton brut
     * n'est jamais stocké. WhatsApp reste un canal de livraison du même lien ;
     * tant qu'aucun template Meta approuvé n'est configuré, on journalise le
     * canal indisponible au lieu d'envoyer un message qui serait rejeté.
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
            'state' => ESBTPCandidatureWorkflow::STATE_AWAITING_ACTIVATION,
        ])->save();

        $url = route('esbtp.admissions.workflow.activation.form', ['token' => $token]);
        $sent = false;
        $email = $workflow->candidature?->email ?: $workflow->etudiant?->email_personnel;

        if ($this->settings->notifyEmail() && $email) {
            try {
                Mail::raw(
                    "Votre dossier ESBTP a franchi l'étape de préinscription.\n\nActivez votre espace KLASSCI et choisissez votre mot de passe : {$url}\n\nCe lien expire dans 48 heures et ne fonctionne qu'une fois.",
                    function ($message) use ($email) {
                        $message->to($email)->subject('Activation de votre espace étudiant KLASSCI');
                    }
                );
                $sent = true;
            } catch (\Throwable $e) {
                Log::warning('Activation KLASSCI : échec envoi e-mail', [
                    'workflow_id' => $workflow->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        if ($this->settings->notifyWhatsapp()) {
            Log::info('Activation KLASSCI : lien prêt pour le canal WhatsApp', [
                'workflow_id' => $workflow->id,
                'telephone' => $workflow->candidature?->telephone,
                'template_requis' => true,
            ]);
        }

        return ['token' => $token, 'url' => $url, 'email_sent' => $sent];
    }

    public function workflowForActivationToken(string $token): ESBTPCandidatureWorkflow
    {
        $workflow = ESBTPCandidatureWorkflow::query()
            ->with(['candidature', 'etudiant.user'])
            ->where('activation_token_hash', hash('sha256', $token))
            ->first();

        if (! $workflow
            || $workflow->activation_token_used_at
            || ! $workflow->activation_token_expires_at
            || $workflow->activation_token_expires_at->isPast()) {
            throw ValidationException::withMessages(['token' => "Ce lien d'activation est invalide ou expiré."]);
        }

        return $workflow;
    }

    public function activate(string $token, string $password): ESBTPCandidatureWorkflow
    {
        return DB::transaction(function () use ($token, $password) {
            $workflow = $this->workflowForActivationToken($token);
            $user = $workflow->etudiant?->user;

            if (! $user) {
                throw ValidationException::withMessages(['compte' => "Le compte étudiant n'a pas pu être préparé."]);
            }

            $user->forceFill([
                'password' => $password,
                'is_active' => true,
                'must_change_password' => false,
                'email_verified_at' => $user->email ? now() : $user->email_verified_at,
                'first_login_at' => $user->first_login_at ?: now(),
            ])->save();

            $workflow->forceFill([
                'activation_token_used_at' => now(),
                'access_activated_at' => now(),
                'activation_token_hash' => null,
                'activation_token_expires_at' => null,
            ])->save();

            $this->advance($workflow);

            return $workflow->fresh(['candidature', 'etudiant.user']);
        });
    }

    /** @return Collection<int, ESBTPClasse> */
    public function eligibleClasses(ESBTPCandidatureWorkflow $workflow): Collection
    {
        $workflow->loadMissing('candidature');
        $c = $workflow->candidature;

        if (! $c) {
            return collect();
        }

        return ESBTPClasse::query()
            ->with(['filiere:id,name,code', 'niveau:id,name'])
            ->where('is_active', true)
            ->where('filiere_id', $c->filiere_id)
            ->where('niveau_etude_id', $c->niveau_id)
            ->orderBy('name')
            ->get()
            ->filter(fn (ESBTPClasse $classe) => ($this->workflow->checkClassAvailability($classe->id)['available'] ?? false))
            ->values();
    }

    public function chooseClass(
        ESBTPCandidatureWorkflow $workflow,
        int $classId,
        int $userId,
        bool $override = false,
    ): ESBTPCandidatureWorkflow {
        if ($this->settings->classChoiceOnce() && $workflow->classIsLocked() && ! $override) {
            throw ValidationException::withMessages(['classe_id' => 'Votre choix de classe a déjà été confirmé.']);
        }

        $classe = $this->eligibleClasses($workflow)->firstWhere('id', $classId);
        if (! $classe) {
            throw ValidationException::withMessages(['classe_id' => "Cette classe n'est pas disponible pour ce dossier."]);
        }

        $workflow->forceFill([
            'selected_class_id' => $classe->id,
            'class_selected_at' => now(),
            'class_selected_by' => $userId,
            'class_locked_at' => $this->settings->classChoiceOnce() && ! $override ? now() : $workflow->class_locked_at,
            'state' => ESBTPCandidatureWorkflow::STATE_READY_TO_FINALIZE,
        ])->save();

        return $workflow->fresh('selectedClass');
    }

    public function finalize(ESBTPCandidatureWorkflow $workflow, ?int $userId = null): ESBTPInscription
    {
        return DB::transaction(function () use ($workflow, $userId) {
            $workflow = ESBTPCandidatureWorkflow::query()
                ->lockForUpdate()
                ->with(['candidature', 'etudiant.user', 'paiement', 'selectedClass'])
                ->findOrFail($workflow->id);

            if ($workflow->final_inscription_id) {
                return ESBTPInscription::findOrFail($workflow->final_inscription_id);
            }

            if (! $workflow->paymentRecorded()) {
                throw ValidationException::withMessages(['paiement' => 'Le paiement de préinscription doit être validé.']);
            }
            if (! $workflow->documentsValidated()) {
                throw ValidationException::withMessages(['pieces' => 'Le contrôle physique des pièces doit être terminé.']);
            }
            if (! $workflow->accessActivated()) {
                throw ValidationException::withMessages(['activation' => "L'espace étudiant doit d'abord être activé."]);
            }
            if (! $workflow->selected_class_id || ! $workflow->selectedClass) {
                throw ValidationException::withMessages(['classe_id' => 'Une classe doit être choisie.']);
            }

            $c = $workflow->candidature;
            $e = $workflow->etudiant;
            $classe = $workflow->selectedClass;
            $anneeId = $c->annee_universitaire_id ?: ESBTPAnneeUniversitaire::query()->where('is_current', true)->value('id');

            $inscription = ESBTPInscription::create([
                'etudiant_id' => $e->id,
                'annee_universitaire_id' => $anneeId,
                'filiere_id' => $classe->filiere_id,
                'niveau_id' => $classe->niveau_etude_id,
                'classe_id' => $classe->id,
                'affectation_status' => $c->affectation_status ?: ESBTPInscription::DEFAULT_AFFECTATION_STATUS,
                'date_inscription' => now()->toDateString(),
                'type_inscription' => 'première_inscription',
                'status' => 'en_attente',
                'workflow_step' => 'en_validation',
                'paiement_validation_id' => $workflow->paiement_id,
                'comptabilite_activee' => true,
                'statut_etablissement' => ESBTPInscription::STATUT_ETABLISSEMENT_NOUVEAU,
                'est_transfert' => (bool) $c->est_transfert,
                'etablissement_origine' => $c->etablissement_sup_origine ?: $c->etablissement_origine,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $workflow->paiement->forceFill(['inscription_id' => $inscription->id])->save();

            // Une fois la classe connue, on repasse par la source canonique des
            // frais. Aucun barème n'est recopié dans ce workflow.
            $this->inscriptions->regenererFraisInscription($inscription);

            // Les dépôts provisoires deviennent ceux de cette inscription, puis
            // la consommation annuelle est matérialisée comme sur le module
            // Pièces du dossier existant.
            ESBTPPieceDeposee::query()
                ->where('etudiant_id', $e->id)
                ->whereNull('inscription_id')
                ->update(['inscription_id' => $inscription->id, 'updated_by' => $userId, 'updated_at' => now()]);

            foreach ($this->catalogue->pourInscription($inscription) as $piece) {
                $depose = ESBTPPieceDeposee::query()
                    ->where('etudiant_id', $e->id)
                    ->where('inscription_id', $inscription->id)
                    ->where('piece_dossier_id', $piece->id)
                    ->get()
                    ->filter(fn (ESBTPPieceDeposee $d) => $d->compteDansLeStock($piece))
                    ->sum('quantite_deposee');

                ESBTPInscriptionPiece::updateOrCreate(
                    ['inscription_id' => $inscription->id, 'piece_dossier_id' => $piece->id],
                    [
                        'etudiant_id' => $e->id,
                        'quantite_consommee' => min((int) $depose, max(1, (int) $piece->exemplaires_par_inscription)),
                        'non_applicable' => false,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]
                );
            }

            $e->forceFill([
                'classe_id' => $classe->id,
                'annee_universitaire_id' => $anneeId,
                'statut' => 'actif',
                'updated_by' => $userId,
            ])->save();

            // Validation finale : même garde paiement/capacité et même passage
            // prospect -> étudiant que le flux historique.
            $converted = $this->workflow->convertProspectToStudent(
                $inscription->fresh(['etudiant.user', 'classe']),
                'Finalisation du parcours de candidature en ligne'
            );

            if (! ($converted['success'] ?? false)) {
                throw ValidationException::withMessages([
                    'finalisation' => $converted['message'] ?? "L'inscription n'a pas pu être finalisée.",
                ]);
            }

            $c->forceFill([
                'statut' => ESBTPCandidature::STATUT_CONVERTIE,
                'etudiant_id' => $e->id,
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

    public function belongsToAuthenticatedStudent(ESBTPCandidatureWorkflow $workflow, User $user): bool
    {
        return (int) ($workflow->etudiant?->user_id ?? 0) === (int) $user->id;
    }

    private function initialState(): string
    {
        return $this->settings->firstPhysicalStep() === 'pieces'
            ? ESBTPCandidatureWorkflow::STATE_AWAITING_DOCUMENTS
            : ESBTPCandidatureWorkflow::STATE_AWAITING_PAYMENT;
    }

    private function assertAppointment(ESBTPCandidature $candidature): void
    {
        if (! $this->settings->requiresAppointment()) {
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

        $user = User::create([
            'name' => trim($etudiant->prenoms.' '.$etudiant->nom),
            'first_name' => $etudiant->prenoms,
            'last_name' => $etudiant->nom,
            'email' => $workflow->candidature?->email,
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

    private function maybeIssueActivation(ESBTPCandidatureWorkflow $workflow): void
    {
        $requiredMilestoneReached = match ($this->settings->accountActivationStep()) {
            InscriptionWorkflowSettings::ACTIVATION_AFTER_DOCUMENTS => $workflow->documentsValidated(),
            default => $workflow->paymentRecorded(),
        };

        if (! $requiredMilestoneReached || $workflow->accessActivated()) {
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

    private function advance(ESBTPCandidatureWorkflow $workflow): void
    {
        $workflow->refresh();

        if ($workflow->final_inscription_id) {
            $workflow->forceFill(['state' => ESBTPCandidatureWorkflow::STATE_COMPLETED])->save();
            return;
        }

        if (! $workflow->paymentRecorded()) {
            $workflow->forceFill(['state' => ESBTPCandidatureWorkflow::STATE_AWAITING_PAYMENT])->save();
            return;
        }

        if (! $workflow->documentsValidated()) {
            $workflow->forceFill(['state' => ESBTPCandidatureWorkflow::STATE_AWAITING_DOCUMENTS])->save();
            return;
        }

        if (! $workflow->accessActivated()) {
            $workflow->forceFill(['state' => ESBTPCandidatureWorkflow::STATE_AWAITING_ACTIVATION])->save();
            return;
        }

        $workflow->forceFill([
            'state' => $workflow->selected_class_id
                ? ESBTPCandidatureWorkflow::STATE_READY_TO_FINALIZE
                : ESBTPCandidatureWorkflow::STATE_AWAITING_STUDENT,
        ])->save();
    }
}
