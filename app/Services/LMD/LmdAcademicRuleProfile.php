<?php

declare(strict_types=1);

namespace App\Services\LMD;

use App\Helpers\SettingsHelper;
use App\Services\AppreciationScaleService;
use Closure;

final class LmdAcademicRuleProfile
{
    public const RATTRAPAGE_SCOPE_ECUE = 'ecue';

    public const RATTRAPAGE_SCOPE_UE = 'ue';

    private Closure $resolver;

    /** @param null|Closure(string, mixed): mixed $resolver */
    public function __construct(?Closure $resolver = null)
    {
        $this->resolver = $resolver ?? static fn (string $key, mixed $default = null): mixed => SettingsHelper::get($key, $default);
    }

    public function validationThreshold(): float
    {
        return (float) $this->first(['lmd_validation_threshold', 'lmd_seuil_validation_ecue'], 10);
    }

    public function eliminatoryGrade(): float
    {
        return (float) $this->first(['lmd_note_eliminatoire'], 0);
    }

    public function interUeCompensationEnabled(): bool
    {
        return $this->toBool($this->first(['lmd_compensation_inter_ue', 'lmd_compensation_enabled'], true));
    }

    /**
     * Compensation entre ECUE d'une meme UE.
     *
     * Lecture en cascade : la cle canonique est `lmd_compensation_intra_ue` (celle que
     * l'ecran de reglages ecrit). `lmd_intra_ue_compensation` est l'ancienne cle, encore
     * lue pour les etablissements qui l'ont deja renseignee en base.
     */
    public function intraUeCompensationEnabled(): bool
    {
        return $this->toBool($this->first(['lmd_compensation_intra_ue', 'lmd_intra_ue_compensation'], true));
    }

    /**
     * Ponderation du controle continu, en pourcentage.
     *
     * ATTENTION : ce reglage n'entre encore dans aucun calcul de moyenne. Il est expose
     * ici pour un branchement futur, et volontairement absent du proces-verbal de jury
     * tant qu'il ne pilote rien (un document legal ne doit pas affirmer une regle inappliquee).
     */
    public function continuousAssessmentWeight(): float
    {
        return (float) $this->first(['lmd_cc_weight'], 40);
    }

    /**
     * Ponderation de l'examen terminal, en pourcentage. Meme reserve que
     * continuousAssessmentWeight() : expose, pas encore applique au calcul des notes.
     */
    public function finalExamWeight(): float
    {
        return (float) $this->first(['lmd_exam_weight'], 60);
    }

    /**
     * Portee du rattrapage : `ecue` (seuls les ECUE rates) ou `ue` (toute UE non acquise).
     */
    public function rattrapageScope(): string
    {
        $scope = mb_strtolower(trim((string) $this->first(['lmd_rattrapage_scope'], self::RATTRAPAGE_SCOPE_ECUE)));

        return in_array($scope, [self::RATTRAPAGE_SCOPE_ECUE, self::RATTRAPAGE_SCOPE_UE], true)
            ? $scope
            : self::RATTRAPAGE_SCOPE_ECUE;
    }

    /** @return array{passable: float, assez_bien: float, bien: float, tres_bien: float, excellent: float} */
    public function mentionThresholds(): array
    {
        return [
            'passable' => (float) $this->first(['lmd_mention_p_threshold'], 10),
            'assez_bien' => (float) $this->first(['lmd_mention_ab_threshold'], 12),
            'bien' => (float) $this->first(['lmd_mention_b_threshold'], 14),
            'tres_bien' => (float) $this->first(['lmd_mention_tb_threshold'], 16),
            'excellent' => (float) $this->first(['lmd_mention_excellent_threshold'], 18),
        ];
    }

    public function mentionFor(?float $average): ?string
    {
        if ($average === null) {
            return null;
        }

        $classification = (new AppreciationScaleService($this->resolver))->classificationFor($average, 'lmd', '');

        return match ($classification['slug']) {
            'excellent' => 'excellent',
            'tres-bien' => 'tres_bien',
            'bien' => 'bien',
            'assez-bien' => 'assez_bien',
            'passable' => 'passable',
            default => null,
        };
    }

    /**
     * Credits attendus par semestre.
     *
     * Le reglage d'instance prime : chaque etablissement fixe son propre volume.
     * Le fichier de configuration versionne ne sert que de dernier recours.
     */
    public function expectedCreditsPerSemester(): int
    {
        $configured = $this->first(['lmd_credits_per_semester'], null);

        if (is_numeric($configured) && (int) $configured > 0) {
            return (int) $configured;
        }

        return (int) config('academic_pilotage.lmd.expected_credits_per_semester', 30);
    }

    /**
     * Total de credits attendu pour un cycle diplomant (norme UEMOA : 180 / 120).
     */
    public function diplomaCreditTotal(string $cycle): int
    {
        return match (mb_strtolower(trim($cycle))) {
            'licence' => (int) $this->first(['lmd_credits_licence_total'], 180),
            'master' => (int) $this->first(['lmd_credits_master_total'], 120),
            default => 0,
        };
    }

    private function first(array $keys, mixed $default): mixed
    {
        foreach ($keys as $key) {
            $value = ($this->resolver)($key, null);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return $default;
    }

    private function toBool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false;
    }
}

