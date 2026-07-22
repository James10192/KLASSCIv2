<?php

namespace App\Services;

use App\Domain\AcademicPilotage\Models\GradeSheet;
use App\Domain\OfficialDocuments\Services\JuryPvIssuanceGuard;
use App\Domain\OfficialDocuments\Services\OfficialDocumentIntegrityService;
use App\Domain\OfficialDocuments\Services\OfficialDocumentService;
use App\Domain\OfficialDocuments\Services\PvNumberSequenceService;
use App\Helpers\SettingsHelper;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPLMDBulletin;
use App\Models\ESBTPLMDJury;
use App\Models\ESBTPLMDJuryDecision;
use App\Models\ESBTPLMDJuryMembre;
use App\Models\ESBTPLMDResultatECUE;
use App\Models\User;
use App\Services\LMD\LmdDecisionProjectionService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Jury de deliberation UEMOA, workflow complet.
 *
 * Composition (president, assesseurs, secretaire) + quorum settings tenant.
 * Calcul automatique de decisions selon moyenne + credits + compensation + seuil.
 * Override jury individuel avec motif obligatoire (DB constraint NOT NULL).
 * PV PDF avec numerotation sequentielle thread-safe (DB lockForUpdate).
 * Archivage legal 5 ans (setting lmd_pv_retention_years).
 */
class JuryDeliberationService
{
    public function __construct(
        private readonly OfficialDocumentService $officialDocuments,
        private readonly OfficialDocumentIntegrityService $integrity,
        private readonly JuryPvIssuanceGuard $issuanceGuard,
        private readonly PvNumberSequenceService $pvSequences,
        private readonly LmdDecisionProjectionService $projection,
    ) {}

    /**
     * Calcule la decision automatique pour un etudiant donnee.
     *
     * @return array{decision_auto:string, mention:?string, moyenne:?float, credits_obtenus:int, credits_attendus:int, raisons:array}
     */
    public function calculerDecisionAuto(ESBTPEtudiant $etudiant, ESBTPLMDJury $jury): array
    {
        $bulletin = $this->resolveBulletin($etudiant, $jury);
        $seuilValidation = (float) SettingsHelper::get('lmd_seuil_validation_ecue', 10);
        $noteEliminatoire = (float) SettingsHelper::get('lmd_note_eliminatoire', 0);

        $thresholds = [
            'passable' => (float) SettingsHelper::get('lmd_mention_p_threshold', 10),
            'assez_bien' => (float) SettingsHelper::get('lmd_mention_ab_threshold', 12),
            'bien' => (float) SettingsHelper::get('lmd_mention_b_threshold', 14),
            'tres_bien' => (float) SettingsHelper::get('lmd_mention_tb_threshold', 16),
            'excellent' => 18.0,
        ];

        $moyenne = $bulletin?->moyenne_generale !== null
            ? (float) $bulletin->moyenne_generale
            : null;

        $creditsDisponibles = $bulletin !== null
            && $bulletin->credits_capitalises !== null
            && $bulletin->credits_totaux !== null;
        $creditsObtenus = $creditsDisponibles ? (int) $bulletin->credits_capitalises : null;
        $creditsAttendus = $creditsDisponibles ? (int) $bulletin->credits_totaux : null;

        $raisons = [];
        $decision = 'ajourne';

        // ECUE eliminatoires
        $hasEliminatoire = false;
        if ($bulletin && $noteEliminatoire > 0) {
            $resultats = ESBTPLMDResultatECUE::where('bulletin_id', $bulletin->id)->get();
            foreach ($resultats as $r) {
                if ($r->moyenne !== null && (float) $r->moyenne < $noteEliminatoire) {
                    $hasEliminatoire = true;
                    $raisons[] = sprintf('ECUE %d note %s < eliminatoire %s', $r->matiere_id, $r->moyenne, $noteEliminatoire);
                    break;
                }
            }
        }

        // Decision principale
        if ($moyenne === null) {
            $decision = 'defere';
            $raisons[] = 'Moyenne non calculee';
        } elseif (!$creditsDisponibles) {
            $decision = 'defere';
            $raisons[] = 'Credits manquants sur bulletin';
        } elseif ($hasEliminatoire) {
            $decision = 'ajourne';
            $raisons[] = 'Note eliminatoire detectee';
        } elseif ($moyenne >= $seuilValidation && $creditsObtenus >= $creditsAttendus) {
            $decision = 'admis';
            $raisons[] = sprintf('Moyenne %.2f >= %.2f, credits %d/%d', $moyenne, $seuilValidation, $creditsObtenus, $creditsAttendus);
        } elseif ($moyenne >= $seuilValidation && $creditsObtenus < $creditsAttendus) {
            $decision = 'admis_sous_condition';
            $raisons[] = sprintf('Moyenne %.2f OK mais credits %d/%d insuffisants', $moyenne, $creditsObtenus, $creditsAttendus);
        } else {
            $decision = 'admission_rattrapage';
            $raisons[] = sprintf('Moyenne %.2f < %.2f, eligible 2e session', $moyenne, $seuilValidation);
        }

        // Mention
        $mention = null;
        if ($decision === 'admis' && $moyenne !== null) {
            if ($moyenne >= $thresholds['excellent']) {
                $mention = 'excellent';
            } elseif ($moyenne >= $thresholds['tres_bien']) {
                $mention = 'tres_bien';
            } elseif ($moyenne >= $thresholds['bien']) {
                $mention = 'bien';
            } elseif ($moyenne >= $thresholds['assez_bien']) {
                $mention = 'assez_bien';
            } elseif ($moyenne >= $thresholds['passable']) {
                $mention = 'passable';
            }
        }

        return [
            'decision_auto' => $decision,
            'mention' => $mention,
            'moyenne' => $moyenne,
            'credits_obtenus' => $creditsObtenus,
            'credits_attendus' => $creditsAttendus,
            'raisons' => $raisons,
            'bulletin_id' => $bulletin?->id,
        ];
    }

    /**
     * Applique en bulk les decisions auto pour tous les etudiants concernes par ce jury.
     * Idempotent : ne recree pas une decision deja presente sauf si override=false.
     */
    public function appliquerDecisionsAuto(ESBTPLMDJury $jury): int
    {
        return DB::transaction(function () use ($jury): int {
            $lockedJury = $this->lockMutableJury($jury->id, true);
            $students = $this->getEtudiantsForJury($lockedJury)->sortBy('id')->values();
            $existing = ESBTPLMDJuryDecision::query()
                ->where('jury_id', $lockedJury->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('etudiant_id');
            $count = 0;

            foreach ($students as $student) {
                $decision = $existing->get($student->id);
                if ($decision?->locked) {
                    throw new \LogicException('Une decision verrouillee ne peut pas etre modifiee.');
                }
                if ($decision?->override_par_jury) {
                    continue;
                }
                $calculation = $this->calculerDecisionAuto($student, $lockedJury);
                $attributes = [
                    'bulletin_id' => $this->requireBulletinId($calculation),
                    'decision_auto' => $calculation['decision_auto'],
                    'decision' => $calculation['decision_auto'],
                    'mention' => $calculation['mention'],
                    'moyenne_generale' => $calculation['moyenne'],
                    'credits_obtenus' => $calculation['credits_obtenus'],
                    'credits_attendus' => $calculation['credits_attendus'],
                    'override_par_jury' => false,
                    'updated_by' => auth()->id(),
                ];
                if ($decision) {
                    $decision->forceFill($attributes)->save();
                } else {
                    $attributes['jury_id'] = $lockedJury->id;
                    $attributes['etudiant_id'] = $student->id;
                    $attributes['created_by'] = auth()->id();
                    ESBTPLMDJuryDecision::query()->create($attributes);
                }
                $count++;
            }
            return $count;
        });
    }

    /**
     * Override jury individuel d'une decision (motif obligatoire).
     */    public function overrideDecision(
        ESBTPLMDJury $jury,
        ESBTPEtudiant $etudiant,
        string $nouvelleDecision,
        string $motif,
        ?string $voteResultat = null
    ): ESBTPLMDJuryDecision {
        abort_unless(in_array($nouvelleDecision, ESBTPLMDJuryDecision::DECISIONS, true), 422, 'Decision invalide');
        abort_if(trim($motif) === '', 422, 'Motif override obligatoire');

        return DB::transaction(function () use ($jury, $etudiant, $nouvelleDecision, $motif, $voteResultat): ESBTPLMDJuryDecision {
            $lockedJury = $this->lockMutableJury($jury->id, true);
            $decision = ESBTPLMDJuryDecision::query()
                ->where('jury_id', $lockedJury->id)
                ->where('etudiant_id', $etudiant->id)
                ->lockForUpdate()
                ->first();
            if ($decision?->locked) {
                throw new \LogicException('Une decision verrouillee ne peut pas etre modifiee.');
            }
            if (! $decision) {
                $calculation = $this->calculerDecisionAuto($etudiant, $lockedJury);
                $decision = new ESBTPLMDJuryDecision([
                    'jury_id' => $lockedJury->id,
                    'etudiant_id' => $etudiant->id,
                    'bulletin_id' => $this->requireBulletinId($calculation),
                    'decision_auto' => $calculation['decision_auto'],
                    'moyenne_generale' => $calculation['moyenne'],
                    'credits_obtenus' => $calculation['credits_obtenus'],
                    'credits_attendus' => $calculation['credits_attendus'],
                    'created_by' => auth()->id(),
                ]);
            } else {
                $calculation = $this->calculerDecisionAuto($etudiant, $lockedJury);
                $decision->bulletin_id = $this->requireBulletinId($calculation);
            }
            $decision->forceFill([
                'bulletin_id' => $decision->bulletin_id,
                'decision' => $nouvelleDecision,
                'override_par_jury' => true,
                'motif_override' => trim($motif),
                'vote_resultat' => $voteResultat,
                'updated_by' => auth()->id(),
            ])->save();
            return $decision;
        });
    }

    /**
     * Reserve un numero PV sequentiel thread-safe.
     * Format : PV-{ANNEE}-{TENANT}-{SEQ4}
     */
    public function reserverNumeroPv(int $anneeUniversitaireId): string
    {
        return $this->pvSequences->next($anneeUniversitaireId);
    }

    /**
     * Genere le PV PDF, le stocke en storage/pv/{tenant}/{annee}/{numero}.pdf,
     * persiste le path + numero + datetime + auteur.
     */
    public function genererPvDeliberation(ESBTPLMDJury $jury): string
    {
        $actor = auth()->user();
        if (! $actor instanceof User) throw new \LogicException('Un acteur authentifie est requis.');
        $document = $this->officialDocuments->issueJuryPv($jury, $actor);
        Log::info('[JuryDeliberationService] PV officiel emis', ['jury_id' => $jury->id, 'reference' => $document->reference]);
        return $document->path;
    }

    /**
     * Publie le jury : verrouille decisions + change statut + horodate.
     */
    public function publierDecisions(ESBTPLMDJury $jury): void
    {
        DB::transaction(function () use ($jury): void {
            $locked = ESBTPLMDJury::query()->lockForUpdate()->findOrFail($jury->id);
            $document = $this->officialDocuments->existingJuryPv($locked);
            if (! $document) throw new \LogicException('Le PV officiel est requis avant publication.');
            $this->integrity->assertValidAndIntact($document, true, auth()->id());
            $this->projection->publish($locked, auth()->id());
            $locked->forceFill(['status' => 'publie', 'publie_at' => now(), 'publie_par' => auth()->id(), 'updated_by' => auth()->id()])->save();
        });
        Log::info('[JuryDeliberationService] Jury publie', ['jury_id' => $jury->id]);
    }

    /**
     * Verifie le quorum selon settings tenant.
     *
     * @return array{ok:bool, present:int, min:int, has_president:bool, has_secretaire:bool, reasons:array}
     */
    public function verifierQuorum(ESBTPLMDJury $jury): array
    {
        $min = (int) SettingsHelper::get('lmd_jury_quorum_min', 2);
        $minAssesseurs = (int) SettingsHelper::get('lmd_jury_quorum_assesseurs_min', 1);
        $membres = $jury->membres()->where('present', true)->get();

        $present = $membres->count();
        $hasPresident = $membres->contains('role', 'president');
        $hasSecretaire = $membres->contains('role', 'secretaire');
        $assesseurs = $membres->where('role', 'assesseur')->count();

        $reasons = [];
        $ok = true;

        if ($present < $min) {
            $ok = false;
            $reasons[] = sprintf('%d membres presents < quorum %d', $present, $min);
        }
        if (! $hasPresident) {
            $ok = false;
            $reasons[] = 'President absent';
        }
        if (! $hasSecretaire) {
            $reasons[] = 'Secretaire absent (recommande)';
        }
        if ($assesseurs < $minAssesseurs) {
            $reasons[] = sprintf('%d assesseur(s) < min %d', $assesseurs, $minAssesseurs);
        }

        return [
            'ok' => $ok,
            'present' => $present,
            'min' => $min,
            'has_president' => $hasPresident,
            'has_secretaire' => $hasSecretaire,
            'assesseurs_count' => $assesseurs,
            'assesseurs_min' => $minAssesseurs,
            'reasons' => $reasons,
        ];
    }

    /**
     * Verifie que le jury dispose de feuilles de notes applicables a son scope.
     *
     * @return array{ok:bool, grade_sheets_count:int, reasons:array}
     */
    public function verifierReadiness(ESBTPLMDJury $jury): array
    {
        return $this->issuanceGuard->readiness($jury);
    }

    /**
     * Enregistre la signature digital d'un membre (canvas base64 ou checkbox).
     */
    public function enregistrerSignature(
        ESBTPLMDJuryMembre $membre,
        string $signatureData,
        int $userId,
        ?string $ip = null,
        ?string $userAgent = null
    ): ESBTPLMDJuryMembre {
        return DB::transaction(function () use ($membre, $signatureData, $userId, $ip, $userAgent): ESBTPLMDJuryMembre {
            $jury = $this->lockMutableJury($membre->jury_id);
            $locked = ESBTPLMDJuryMembre::query()
                ->where('jury_id', $jury->id)
                ->lockForUpdate()
                ->findOrFail($membre->id);
            if (! $locked->canBeSignedBy($userId)) {
                throw new \LogicException('Cette signature ne peut pas etre enregistree par cet utilisateur.');
            }
            if ($locked->hasSigned() || $locked->signature_data !== null) {
                throw new \LogicException('Une signature existante ne peut pas etre remplacee.');
            }
            $locked->forceFill([
                'signature_data' => $signatureData,
                'signature_at' => now(),
                'signature_ip' => $ip,
                'signature_user_agent' => $userAgent,
            ])->save();
            return $locked;
        });
    }

    public function addOrUpdateMembre(ESBTPLMDJury $jury, array $attributes): ESBTPLMDJuryMembre
    {
        return DB::transaction(function () use ($jury, $attributes): ESBTPLMDJuryMembre {
            $lockedJury = $this->lockMutableJury($jury->id);
            $member = ESBTPLMDJuryMembre::query()
                ->where('jury_id', $lockedJury->id)
                ->where('user_id', $attributes['user_id'])
                ->lockForUpdate()
                ->first();
            if (! $member) {
                return ESBTPLMDJuryMembre::query()->create([
                    'jury_id' => $lockedJury->id,
                    'user_id' => $attributes['user_id'],
                    'role' => $attributes['role'],
                    'present' => $attributes['present'] ?? true,
                ]);
            }
            if ($member->hasSigned()) {
                throw new \LogicException('Un membre ayant signe ne peut plus etre modifie.');
            }
            $member->forceFill([
                'role' => $attributes['role'],
                'present' => $attributes['present'] ?? $member->present,
            ])->save();
            return $member;
        });
    }

    public function removeMembre(ESBTPLMDJury $jury, ESBTPLMDJuryMembre $membre): void
    {
        DB::transaction(function () use ($jury, $membre): void {
            $lockedJury = $this->lockMutableJury($jury->id);
            $locked = ESBTPLMDJuryMembre::query()
                ->where('jury_id', $lockedJury->id)
                ->lockForUpdate()
                ->findOrFail($membre->id);
            if ($locked->hasSigned()) {
                throw new \LogicException('Un membre ayant signe ne peut pas etre supprime.');
            }
            $locked->delete();
        });
    }

    private function lockMutableJury(int $juryId, bool $startDeliberation = false): ESBTPLMDJury
    {
        $jury = ESBTPLMDJury::query()->lockForUpdate()->findOrFail($juryId);
        if ($jury->pv_genere_at !== null || in_array($jury->status, ['clos', 'publie', 'archive'], true)) {
            throw new \LogicException('Le PV est deja emis, le jury est verrouille.');
        }
        if ($startDeliberation && $jury->status === 'preparation') {
            $jury->forceFill(['status' => 'en_cours', 'updated_by' => auth()->id()])->save();
        }
        return $jury;
    }

    /**
     * Statistiques jury pour le PV.
     */
    public function buildStatistiques(ESBTPLMDJury $jury): array
    {
        $decisions = $jury->decisions ?? collect();

        return [
            'total' => $decisions->count(),
            'admis' => $decisions->where('decision', 'admis')->count(),
            'admission_rattrapage' => $decisions->where('decision', 'admission_rattrapage')->count(),
            'ajourne' => $decisions->where('decision', 'ajourne')->count(),
            'exclu' => $decisions->where('decision', 'exclu')->count(),
            'admis_sous_condition' => $decisions->where('decision', 'admis_sous_condition')->count(),
            'defere' => $decisions->where('decision', 'defere')->count(),
            'overrides' => $decisions->where('override_par_jury', true)->count(),
            'mentions' => [
                'excellent' => $decisions->where('mention', 'excellent')->count(),
                'tres_bien' => $decisions->where('mention', 'tres_bien')->count(),
                'bien' => $decisions->where('mention', 'bien')->count(),
                'assez_bien' => $decisions->where('mention', 'assez_bien')->count(),
                'passable' => $decisions->where('mention', 'passable')->count(),
            ],
            'moyenne_promo' => $decisions->whereNotNull('moyenne_generale')->avg('moyenne_generale'),
        ];
    }

    private function resolveBulletin(ESBTPEtudiant $etudiant, ESBTPLMDJury $jury): ?ESBTPLMDBulletin
    {
        return ESBTPLMDBulletin::query()
            ->forJury($jury)
            ->where('etudiant_id', $etudiant->id)
            ->orderByDesc('id')
            ->first();
    }

    private function requireBulletinId(array $calculation): int
    {
        $bulletinId = $calculation['bulletin_id'] ?? null;
        if ($bulletinId === null) {
            throw new \LogicException('Une decision de jury exige un bulletin du perimetre.');
        }

        return (int) $bulletinId;
    }

    /**
     * Liste les etudiants concernes par ce jury via bulletins LMD du scope.
     *
     * @return Collection<int, ESBTPEtudiant>
     */
    private function getEtudiantsForJury(ESBTPLMDJury $jury): Collection
    {
        $etudiantIds = ESBTPLMDBulletin::query()
            ->forJury($jury)
            ->pluck('etudiant_id')
            ->unique()
            ->values();
        if ($etudiantIds->isEmpty()) {
            return collect();
        }

        return ESBTPEtudiant::whereIn('id', $etudiantIds)->get();
    }
}
