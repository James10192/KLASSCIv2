<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\Contracts\BulletinPreparationService;
use App\Domain\AcademicPilotage\DTO\BulletinPreparationResult;
use App\Domain\AcademicPilotage\DTO\StudentMetricContext;
use App\Domain\AcademicPilotage\Models\GradeSheet;
use App\Helpers\SettingsHelper;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;

/**
 * Ce qu'il faut pour qu'un bulletin LMD soit généré.
 *
 * Deux questions, qu'on ne confond plus :
 *  1. L'étudiant a-t-il des notes ce semestre ? Sans aucune, il n'y a rien à
 *     calculer : c'est toujours bloquant.
 *  2. Les fiches de notes du Pilotage académique sont-elles validées ? C'est un
 *     circuit de contrôle que toutes les écoles ne pratiquent pas. Mesuré à
 *     esbtp-abidjan (novembre 2026) : 1 722 évaluations sans fiche, 13 fiches,
 *     aucune validée — exiger ce circuit refusait la classe entière alors que
 *     les notes étaient saisies. L'école choisit donc : bloquant si elle exige
 *     le circuit, simple avertissement sinon (défaut).
 *
 * La question 2 a trois causes distinctes, et trois gestes différents : on les
 * nomme séparément pour que l'écran puisse dire QUOI faire.
 */
final class LmdBulletinPreparationService implements BulletinPreparationService
{
    /** Réglage d'école : les fiches doivent-elles être validées avant le bulletin ? */
    public const REGLAGE_EXIGER_FICHES = 'lmd_bulletins_exiger_fiches_validees';

    public const CODE_SANS_NOTE = 'no_grade_data';

    public const CODE_FICHES_ABSENTES = 'grade_sheets_missing';

    public const CODE_FICHES_NON_VALIDEES = 'grade_sheets_not_validated';

    public const CODE_NOTES_MANQUANTES = 'missing_grade_entries';

    public function __construct(
        private readonly StudentAcademicHealthService $health,
        private readonly AcademicPeriodNormalizer $periods,
    ) {}

    public static function exigeFichesValidees(): bool
    {
        return (string) SettingsHelper::get(self::REGLAGE_EXIGER_FICHES, '0') === '1';
    }

    public function prepare(
        int $studentId,
        int $classId,
        int $academicYearId,
        string $period,
    ): BulletinPreparationResult {
        $result = $this->health->evaluate(new StudentMetricContext(
            studentId: $studentId,
            classId: $classId,
            academicYearId: $academicYearId,
            academicSystem: 'LMD',
            period: $period,
        ));
        $performance = $result->metrics->get('academic_performance');
        $completion = $result->metrics->get('assessment_completion');
        $exige = self::exigeFichesValidees();
        $severiteFiches = $exige ? 'blocking' : 'warning';

        // Les fiches de CETTE classe et de CE semestre, dans le Pilotage.
        $fiches = route('esbtp.pilotage-academique.index', [
            'class_id' => $classId,
            'system' => 'LMD',
            'period' => $period,
            'year_id' => $academicYearId,
        ]).'#sheets';

        $blocking = [];
        $warnings = [];

        $notes = $this->nombreDeNotes($studentId, $classId, $academicYearId, $period);
        if ($notes === 0) {
            $blocking[] = $this->issue(
                self::CODE_SANS_NOTE,
                'Aucune note n’est encore saisie pour cet étudiant ce semestre.',
                'blocking',
            );
        }

        $issueFiches = null;
        if (! $completion?->isAvailable()) {
            $issueFiches = $this->fichesExistent($classId, $academicYearId, $period)
                ? $this->issue(
                    self::CODE_FICHES_NON_VALIDEES,
                    'Les fiches de notes du semestre existent, mais aucune n’est encore validée.',
                    $severiteFiches,
                    $fiches,
                    'Ouvrir les fiches à valider',
                )
                : $this->issue(
                    self::CODE_FICHES_ABSENTES,
                    'Les notes sont saisies, mais les fiches de notes du semestre n’ont pas encore été préparées.',
                    $severiteFiches,
                    $fiches,
                    'Ouvrir le Pilotage académique',
                );
        } elseif (($completion->denominator ?? 0) > ($completion->numerator ?? 0)) {
            $issueFiches = $this->issue(
                self::CODE_NOTES_MANQUANTES,
                ((int) $completion->numerator).' note(s) validée(s) sur '.((int) $completion->denominator).' attendue(s).',
                $severiteFiches,
                $fiches,
                'Ouvrir les fiches de cette classe',
            );
        }

        if ($issueFiches !== null) {
            $exige ? $blocking[] = $issueFiches : $warnings[] = $issueFiches;
        }

        return new BulletinPreparationResult(
            system: 'LMD',
            studentId: $studentId,
            classId: $classId,
            academicYearId: $academicYearId,
            period: $period,
            ready: $blocking === [],
            coveragePct: $result->coveragePct,
            blockingIssues: $blocking,
            warnings: $warnings,
            evidence: [
                'health_level' => $result->level,
                'health_score' => $result->score,
                'coverage_pct' => $result->coveragePct,
                'confidence_pct' => $result->confidencePct,
                'performance_is_post_generation' => true,
                'performance' => $performance?->toArray(),
                'completion' => $completion?->toArray(),
                'notes_count' => $notes,
                'requires_validated_sheets' => $exige,
            ],
        );
    }

    /**
     * Les notes que le calcul LMD lira réellement : évaluations terminées de
     * la classe, de l'année et du semestre (mêmes critères que
     * LMDBulletinService::prechargerNotesEtEnseignants()).
     */
    private function nombreDeNotes(int $studentId, int $classId, int $academicYearId, string $period): int
    {
        $variants = $this->periods->databaseVariants($period);
        $numero = $this->periods->semesterNumber($period);
        if ($numero !== null) {
            $variants[] = 'semestre '.$numero;
        }

        return ESBTPNote::query()
            ->where('etudiant_id', $studentId)
            ->where('classe_id', $classId)
            ->whereHas('evaluation', fn ($q) => $q
                ->whereIn('periode', $variants)
                ->where('annee_universitaire_id', $academicYearId)
                ->where('status', ESBTPEvaluation::STATUS_COMPLETED))
            ->count();
    }

    private function fichesExistent(int $classId, int $academicYearId, string $period): bool
    {
        return GradeSheet::query()
            ->where('classe_id', $classId)
            ->where('annee_universitaire_id', $academicYearId)
            ->where('academic_system', 'LMD')
            ->whereIn('semester', $this->periods->databaseVariants($period))
            ->where('source', 'evaluation')
            ->exists();
    }

    /** @return array{code: string, message: string, severity: string, action_url?: string, action_label?: string} */
    private function issue(
        string $code,
        string $message,
        string $severity,
        ?string $actionUrl = null,
        ?string $actionLabel = null,
    ): array {
        $issue = compact('code', 'message', 'severity');
        if ($actionUrl !== null) {
            $issue['action_url'] = $actionUrl;
            $issue['action_label'] = $actionLabel ?? 'Ouvrir les fiches de cette classe';
        }

        return $issue;
    }
}
