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
use App\Services\LMD\AgregatDeLaPeriode;
use App\Services\LMD\LmdAcademicRuleProfile;
use App\Services\LMD\LmdDecisionProjectionService;
use App\Services\LMDBulletinService;
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
    private readonly LmdAcademicRuleProfile $rules;

    public function __construct(
        private readonly OfficialDocumentService $officialDocuments,
        private readonly OfficialDocumentIntegrityService $integrity,
        private readonly JuryPvIssuanceGuard $issuanceGuard,
        private readonly PvNumberSequenceService $pvSequences,
        private readonly LmdDecisionProjectionService $projection,
        ?LmdAcademicRuleProfile $rules = null,
        ?LMDBulletinService $bulletins = null,
    ) {
        $this->rules = $rules ?? new LmdAcademicRuleProfile();
        $this->bulletins = $bulletins;
    }

    /** @var LMDBulletinService|null resolu au premier besoin */
    private ?LMDBulletinService $bulletins = null;

    private function bulletins(): LMDBulletinService
    {
        return $this->bulletins ??= app(LMDBulletinService::class);
    }

    /**
     * Calcule la decision automatique pour un etudiant donnee.
     *
     * @return array{decision_auto:string, mention:?string, moyenne:?float, credits_obtenus:int, credits_attendus:int, raisons:array}
     */
    public function calculerDecisionAuto(ESBTPEtudiant $etudiant, ESBTPLMDJury $jury): array
    {
        // Un jury peut être semestriel OU annuel : `esbtp_lmd_jurys.semestre` est
        // nullable, et `scopeForJury` ne filtre par semestre que s'il est
        // renseigné. Sur un jury annuel, plusieurs bulletins remontent donc — et
        // ce code n'en retenait qu'un, le dernier créé. La moyenne, les crédits
        // et la mention gravés au procès-verbal venaient d'un semestre sur deux,
        // sans qu'aucune erreur ne soit levée, sur un document ensuite scellé,
        // empreinté et conservé cinq ans.
        $bulletins = $this->resolveBulletins($etudiant, $jury);

        // Le bulletin de référence — le dernier semestre de la période — porte
        // l'identifiant gravé sur la décision. Les NOMBRES, eux, agrègent toute
        // la période délibérée.
        $bulletin = $bulletins->last();
        // Passer par le profil : il lit la cle de l'ecran de reglages
        // (`lmd_validation_threshold`) puis retombe sur l'ancienne (`lmd_seuil_validation_ecue`).
        $seuilValidation = $this->rules->validationThreshold();
        $noteEliminatoire = $this->rules->eliminatoryGrade();

        // L'arithmétique de la période vit dans `AgregatDeLaPeriode`, parce que
        // le garde d'émission du PV doit trouver EXACTEMENT le même résultat :
        // deux formules qui divergent d'un millième refuseraient des
        // procès-verbaux justes.
        $moyenne = AgregatDeLaPeriode::moyenne($bulletins);
        $creditsDisponibles = AgregatDeLaPeriode::creditsDisponibles($bulletins);
        $creditsObtenus = $creditsDisponibles ? AgregatDeLaPeriode::creditsObtenus($bulletins) : null;
        $creditsAttendus = $creditsDisponibles ? AgregatDeLaPeriode::creditsAttendus($bulletins) : null;

        $raisons = [];
        $decision = 'ajourne';

        // Dire la période délibérée : sur un jury annuel, la moyenne n'est pas
        // celle d'un bulletin mais l'agrégat pondéré par les crédits de chaque
        // semestre. Le jury doit pouvoir le lire, pas le supposer.
        if ($bulletins->count() > 1) {
            $raisons[] = sprintf(
                'Periode annuelle : %d bulletins agreges (semestres %s), moyenne ponderee par les credits',
                $bulletins->count(),
                $bulletins->pluck('semestre')->filter()->implode(' et ')
            );
        }

        // ECUE eliminatoires — sur TOUTE la periode. Une note eliminatoire au
        // premier semestre ne disparait pas parce que le jury est annuel.
        $hasEliminatoire = false;
        if ($bulletins->isNotEmpty() && $noteEliminatoire > 0) {
            $resultats = ESBTPLMDResultatECUE::whereIn('bulletin_id', $bulletins->pluck('id'))->get();
            foreach ($resultats as $r) {
                // La note retenue, pas celle de premiere session : un etudiant
                // passe de 7 a 14 en seconde session verrait sinon sa moyenne
                // generale monter tout en restant marque eliminatoire, et la
                // branche eliminatoire, evaluee AVANT le test de la moyenne, le
                // laisserait ajourne. Le rattrapage n'aurait servi a rien.
                $note = $this->bulletins()->noteEffectiveECUE($r);
                if ($note !== null && $note < $noteEliminatoire) {
                    $hasEliminatoire = true;
                    $raisons[] = sprintf('ECUE %d note %s < eliminatoire %s', $r->matiere_id, $note, $noteEliminatoire);
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
            $classification = app(AppreciationScaleService::class)->classificationFor($moyenne, 'lmd', '');
            $mention = $this->canonicalMentionFromSlug($classification['slug']);
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

    private function canonicalMentionFromSlug(string $slug): ?string
    {
        return match (true) {
            $slug === 'excellent' => 'excellent',
            $slug === 'tres-bien' => 'tres_bien',
            $slug === 'bien' => 'bien',
            $slug === 'assez-bien' => 'assez_bien',
            $slug === 'passable' => 'passable',
            default => null,
        };
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
                $attributes = $this->attributsDeDecision($calculation);
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
     * Les attributs d'une décision issue du calcul automatique.
     *
     * Partagés entre la délibération initiale et la réouverture pour
     * rectification : deux copies divergeraient, et l'une des deux graverait un
     * jeu de colonnes incomplet sur un document officiel.
     */
    private function attributsDeDecision(array $calculation): array
    {
        return [
            'bulletin_id' => $this->requireBulletinId($calculation),
            'decision_auto' => $calculation['decision_auto'],
            'decision' => $calculation['decision_auto'],
            'mention' => $calculation['mention'],
            'moyenne_generale' => $calculation['moyenne'],
            'credits_obtenus' => $calculation['credits_obtenus'],
            'credits_attendus' => $calculation['credits_attendus'],
            // La motivation, désormais conservée. Elle était rédigée à chaque
            // branche du calcul puis perdue au retour : le jury lisait une
            // décision sans jamais lire ce qui l'avait produite.
            'raisons' => $calculation['raisons'] ?? [],
            'override_par_jury' => false,
            'updated_by' => auth()->id(),
        ];
    }

    /**
     * Rouvre la délibération d'un jury dont le PV est déjà émis, pour qu'une
     * rectification porte sur des chiffres à jour.
     *
     * Le verrou posé sur les décisions à l'émission du PV n'était levé nulle
     * part : `grep "'locked' => false"` ne rendait aucun résultat. Une
     * réclamation aboutie — note corrigée, bulletin recalculé — laissait donc
     * la décision figée sur l'ancienne valeur, et le PV rectificatif
     * re-certifiait ce que le relevé réémis contredisait.
     *
     * Ce que cette méthode NE fait pas : toucher aux décisions que le jury a
     * reprises à son compte. Un `override_par_jury` est une décision humaine,
     * motivée et signée ; la recalculer l'effacerait. Le jury reste souverain,
     * et c'est le calcul automatique — lui seul — qui se remet à jour.
     *
     * @return int le nombre de décisions recalculées
     */
    public function rouvrirLaDeliberation(ESBTPLMDJury $jury, string $motif): int
    {
        if (trim($motif) === '') {
            throw new \InvalidArgumentException('Le motif de réouverture est obligatoire.');
        }

        return DB::transaction(function () use ($jury, $motif): int {
            $lockedJury = ESBTPLMDJury::query()->lockForUpdate()->findOrFail($jury->id);

            $decisions = ESBTPLMDJuryDecision::query()
                ->where('jury_id', $lockedJury->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('etudiant_id');

            // Le déverrouillage proprement dit. Sans lui, le recalcul plus bas
            // lèverait « Une decision verrouillee ne peut pas etre modifiee ».
            ESBTPLMDJuryDecision::query()
                ->where('jury_id', $lockedJury->id)
                ->update(['locked' => false, 'locked_at' => null, 'updated_by' => auth()->id()]);

            $recalculees = 0;
            $preservees = 0;
            foreach ($this->getEtudiantsForJury($lockedJury)->sortBy('id') as $student) {
                $decision = $decisions->get($student->id);
                if ($decision === null) {
                    // La cohorte incomplète est déjà dite par le garde d'émission ;
                    // en créer une ici masquerait le trou.
                    continue;
                }
                if ($decision->override_par_jury) {
                    $preservees++;
                    continue;
                }

                $calculation = $this->calculerDecisionAuto($student, $lockedJury);
                $decision->forceFill($this->attributsDeDecision($calculation))->save();
                $recalculees++;
            }

            // En `warning` et non `info` : rouvrir une délibération scellée est
            // un acte rare, et la production filtre `info`. Le jour où l'on
            // cherche pourquoi une décision a changé après le PV, cette ligne
            // est la seule trace hors journal d'audit.
            Log::warning('Deliberation rouverte pour rectification du PV.', [
                'jury_id' => $lockedJury->id,
                'motif' => $motif,
                'decisions_recalculees' => $recalculees,
                'decisions_preservees_override' => $preservees,
                'par' => auth()->id(),
            ]);

            return $recalculees;
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

    /**
     * Les bulletins de la période délibérée, un par semestre, du plus ancien au
     * plus récent.
     *
     * Un jury semestriel en rend un — le comportement d'avant, à l'identique.
     * Un jury annuel en rend autant que la période compte de semestres.
     *
     * La déduplication par semestre n'est pas décorative : si un bulletin a été
     * régénéré, deux lignes portent le même semestre, et les sommer compterait
     * ses crédits deux fois. On garde le dernier écrit, ce que faisait déjà
     * l'ancien `orderByDesc('id')->first()` pour le cas à un seul bulletin.
     *
     * @return Collection<int, ESBTPLMDBulletin>
     */
    private function resolveBulletins(ESBTPEtudiant $etudiant, ESBTPLMDJury $jury): Collection
    {
        $bulletins = ESBTPLMDBulletin::query()
            ->forJury($jury)
            ->where('etudiant_id', $etudiant->id)
            ->orderBy('id')
            ->get();

        return collect(AgregatDeLaPeriode::parSemestre($bulletins));
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
