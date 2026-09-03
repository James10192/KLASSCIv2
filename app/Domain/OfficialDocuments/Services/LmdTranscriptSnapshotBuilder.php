<?php

namespace App\Domain\OfficialDocuments\Services;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPLMDResultatUE;
use App\Models\User;
use App\Services\LMD\LmdAcademicRuleProfile;
use App\Services\LMDBulletinService;
use Carbon\CarbonInterface;

/**
 * Instantane d'un releve de notes LMD.
 *
 * Meme contrat que JuryPvSnapshotBuilder : le tableau produit est canonicalise
 * (cles triees) puis hache, et c'est lui — pas la base — qui fait foi ensuite.
 * Le rendu PDF ne lit que cet instantane, jamais les modeles.
 */
class LmdTranscriptSnapshotBuilder
{
    /**
     * Version du jeu de regles academiques grave dans le releve.
     *
     * v1 : n'inscrit que les regles qui gouvernent reellement l'acquisition d'une
     * UE et le total de credits. Les ponderations controle continu / examen
     * terminal en sont absentes pour la meme raison que dans le PV : elles
     * n'entrent aujourd'hui dans aucun calcul.
     */
    public const RULES_VERSION = 'lmd-transcript-profile-v1';

    public function __construct(
        private readonly LmdAcademicRuleProfile $profile,
        private readonly LMDBulletinService $bulletins,
    ) {}

    /**
     * @param array{student: mixed, year: mixed, bulletins: \Illuminate\Support\Collection} $state
     */
    public function build(array $state, array $identity, User $actor, CarbonInterface $issuedAt): array
    {
        $bulletins = $state['bulletins'];
        $semesters = $bulletins->map(fn ($bulletin) => $this->semesterData($bulletin))->values()->all();

        $snapshot = [
            'schema' => 'lmd-transcript-snapshot-v1',
            'document' => [
                'reference' => $identity['reference'],
                'version' => $identity['version'],
                'number' => $identity['number'],
            ],
            'institution' => SettingsHelper::getSchoolInfo(),
            'student' => $this->studentData($state['student']),
            'scope' => $this->scopeData($state['year'], $bulletins->first()),
            'semesters' => $semesters,
            'totals' => $this->totals($semesters),
            'rules' => $this->rules(),
            'issuance' => [
                'actor_id' => $actor->id,
                'actor_name' => $actor->name,
                'issued_at' => $issuedAt->toIso8601String(),
                'template_version' => 'lmd-releve-notes-v1',
                'renderer_version' => 'dompdf-v2',
            ],
        ];

        return $this->canonicalize($snapshot);
    }

    public function canonicalJson(array $snapshot): string
    {
        return json_encode(
            $this->canonicalize($snapshot),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );
    }

    private function studentData($student): array
    {
        return [
            'id' => $student->id,
            'matricule' => $student->matricule,
            'last_name' => $student->nom,
            'first_names' => $student->prenoms,
            'birth_date' => $student->date_naissance
                ? \Carbon\Carbon::parse($student->date_naissance)->toDateString()
                : null,
            'birth_place' => $student->lieu_naissance ?: $student->ville_naissance,
        ];
    }

    private function scopeData($year, $firstBulletin): array
    {
        return [
            'year' => [
                'id' => $year->id,
                'label' => $year->display_name,
            ],
            'level' => $firstBulletin?->niveau,
            'domain' => $firstBulletin?->domaine_label,
            'mention' => $firstBulletin?->mention_label,
            'parcours' => [
                'id' => $firstBulletin?->parcours_id,
                'label' => $firstBulletin?->parcours_label ?? $firstBulletin?->parcours?->name,
                'code' => $firstBulletin?->parcours?->code,
            ],
        ];
    }

    private function semesterData($bulletin): array
    {
        $units = $bulletin->resultatsUEs
            ->map(fn ($resultat) => $this->unitData($resultat))
            ->sortBy('code')
            ->values()
            ->all();

        return [
            'semester' => (int) $bulletin->semestre,
            'class' => $bulletin->classe?->name,
            'average' => $this->decimal($bulletin->moyenne_generale),
            'mention' => $bulletin->mention_generale,
            'credits_earned' => (int) $bulletin->credits_capitalises,
            'credits_expected' => (int) $bulletin->credits_totaux,
            'rank' => $bulletin->rang !== null ? (int) $bulletin->rang : null,
            'headcount' => $bulletin->effectif !== null ? (int) $bulletin->effectif : null,
            'decision' => $bulletin->decision_deliberation,
            'units' => $units,
        ];
    }

    private function unitData(ESBTPLMDResultatUE $resultat): array
    {
        // La note portee par l'element constitutif doit etre celle que l'unite a
        // agregee : la note effective, c'est-a-dire le resultat de seconde session
        // quand il y en a eu un. Lire `moyenne` ici afficherait un element a 07,00
        // dans une unite a 11,50 declaree acquise — un document officiel fige a
        // l'emission, conserve, et incoherent avec lui-meme pour toujours.
        $elements = $resultat->resultatsECUEs
            ->map(fn ($ecue) => [
                'code' => $ecue->matiere?->code,
                'name' => $ecue->matiere?->name,
                'credits' => (int) $ecue->credit,
                'average' => $this->decimal($this->bulletins->noteEffectiveECUE($ecue)),
                // Dire laquelle des deux notes le releve imprime.
                'session' => $ecue->note_finale !== null ? 'rattrapage' : 'normale',
            ])
            ->sortBy('code')
            ->values()
            ->all();

        return [
            'code' => $resultat->uniteEnseignement?->code,
            'name' => $resultat->uniteEnseignement?->name,
            'credits' => (int) $resultat->credit,
            'average' => $this->decimal($resultat->moyenne),
            'status' => $resultat->statut,
            'status_label' => $this->statusLabel($resultat->statut),
            'acquired' => $resultat->isValidee(),
            'elements' => $elements,
        ];
    }

    /**
     * Libelle en clair des trois statuts UEMOA.
     *
     * Le code brut (AQ / APC / NAQ) reste la donnee ; le libelle n'est la que
     * pour que le releve soit lisible par un etablissement qui ne connait pas
     * nos abreviations.
     */
    private function statusLabel(?string $status): ?string
    {
        return match ($status) {
            ESBTPLMDResultatUE::STATUT_AQ => 'Acquise',
            ESBTPLMDResultatUE::STATUT_APC => 'Acquise par compensation',
            ESBTPLMDResultatUE::STATUT_NAQ => 'Non acquise',
            default => $status,
        };
    }

    /**
     * Cumul annuel.
     *
     * La moyenne annuelle est ponderee par les credits attendus de chaque
     * semestre. Un semestre a 30 credits ne peut pas peser autant qu'un semestre
     * a 12. Si aucun semestre n'annonce de credits attendus, on retombe sur la
     * moyenne arithmetique — et le document dit laquelle des deux il montre,
     * pour qu'aucun lecteur n'ait a le deviner.
     */
    private function totals(array $semesters): array
    {
        $earned = 0;
        $expected = 0;
        $weighted = 0.0;
        $weights = 0;
        $plainSum = 0.0;
        $plainCount = 0;

        foreach ($semesters as $semester) {
            $earned += (int) $semester['credits_earned'];
            $expected += (int) $semester['credits_expected'];

            if ($semester['average'] === null) {
                continue;
            }

            $plainSum += (float) $semester['average'];
            $plainCount++;

            $weight = (int) $semester['credits_expected'];
            if ($weight > 0) {
                $weighted += ((float) $semester['average']) * $weight;
                $weights += $weight;
            }
        }

        if ($weights > 0) {
            $average = round($weighted / $weights, 2);
            $method = 'weighted_by_expected_credits';
        } elseif ($plainCount > 0) {
            $average = round($plainSum / $plainCount, 2);
            $method = 'arithmetic_mean';
        } else {
            $average = null;
            $method = 'unavailable';
        }

        return [
            'credits_earned' => $earned,
            'credits_expected' => $expected,
            'average' => $average,
            'average_method' => $method,
            'mention' => $average !== null
                ? app(\App\Services\AppreciationScaleService::class)->labelFor($average, 'lmd', '')
                : null,
        ];
    }

    private function rules(): array
    {
        return [
            'profile_version' => self::RULES_VERSION,
            'validation_threshold' => $this->profile->validationThreshold(),
            'eliminatory_grade' => $this->profile->eliminatoryGrade(),
            'inter_ue_compensation' => $this->profile->interUeCompensationEnabled(),
            'intra_ue_compensation' => $this->profile->intraUeCompensationEnabled(),
            'mention_thresholds' => $this->profile->mentionThresholds(),
            'expected_credits_per_semester' => $this->profile->expectedCreditsPerSemester(),
        ];
    }

    private function decimal($value): ?float
    {
        return $value === null ? null : round((float) $value, 2);
    }

    private function canonicalize(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->canonicalize($item);
            }
        }

        if (! array_is_list($value)) {
            ksort($value, SORT_STRING);
        }

        return $value;
    }
}
