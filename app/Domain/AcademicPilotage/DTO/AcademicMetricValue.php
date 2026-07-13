<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\DTO;

use InvalidArgumentException;

final readonly class AcademicMetricValue
{
    public function __construct(
        public string $key,
        public ?float $value,
        public int $confidencePct,
        public array $evidence = [],
        public ?int $coveragePct = null,
        public ?float $numerator = null,
        public ?float $denominator = null,
        public bool $applicable = true,
        public array $reasons = [],
    ) {
        $this->validateIdentityAndScore();
        $this->validateCoverage();
        $this->validateRatio();
        $this->validateReasons();
        AcademicMetricEvidenceSchema::validate($key, $evidence);
    }

    public function isAvailable(): bool
    {
        return $this->applicable && $this->value !== null;
    }

    public function effectiveCoveragePct(): int
    {
        return $this->coveragePct ?? ($this->isAvailable() ? 100 : 0);
    }

    public static function unavailable(
        string $key,
        string $reason,
        array $evidence = [],
        bool $applicable = true,
    ): self {
        return new self(
            key: $key,
            value: null,
            confidencePct: 0,
            evidence: $evidence,
            coveragePct: 0,
            applicable: $applicable,
            reasons: [$reason],
        );
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'value' => $this->value,
            'available' => $this->isAvailable(),
            'applicable' => $this->applicable,
            'numerator' => $this->numerator,
            'denominator' => $this->denominator,
            'coverage_pct' => $this->effectiveCoveragePct(),
            'confidence_pct' => $this->confidencePct,
            'evidence' => AcademicMetricEvidenceSchema::redact($this->key, $this->evidence),
            'reasons' => $this->reasons,
        ];
    }

    private function validateIdentityAndScore(): void
    {
        if (! preg_match('/^[a-z][a-z0-9_]*$/', $this->key)) {
            throw new InvalidArgumentException('La clé de métrique doit être en snake_case ASCII.');
        }

        if ($this->value !== null && (! is_finite($this->value) || $this->value < 0 || $this->value > 100)) {
            throw new InvalidArgumentException('La valeur de métrique doit être comprise entre 0 et 100.');
        }

        if ($this->confidencePct < 0 || $this->confidencePct > 100) {
            throw new InvalidArgumentException('La confiance doit être comprise entre 0 et 100.');
        }

        if ($this->value === null && $this->confidencePct !== 0) {
            throw new InvalidArgumentException('Une métrique indisponible doit avoir une confiance nulle.');
        }
    }

    private function validateCoverage(): void
    {
        if ($this->coveragePct !== null && ($this->coveragePct < 0 || $this->coveragePct > 100)) {
            throw new InvalidArgumentException('La couverture doit être comprise entre 0 et 100.');
        }

        if ($this->value === null && $this->coveragePct !== null && $this->coveragePct !== 0) {
            throw new InvalidArgumentException('Une métrique indisponible doit avoir une couverture nulle.');
        }
    }

    private function validateRatio(): void
    {
        foreach (['numérateur' => $this->numerator, 'dénominateur' => $this->denominator] as $label => $number) {
            if ($number !== null && (! is_finite($number) || $number < 0)) {
                throw new InvalidArgumentException("Le {$label} doit être positif.");
            }
        }

        if ($this->numerator !== null && $this->denominator !== null && $this->numerator > $this->denominator) {
            throw new InvalidArgumentException('Le numérateur ne peut pas dépasser le dénominateur.');
        }
    }

    private function validateReasons(): void
    {
        if ($this->reasons !== [] && (! array_is_list($this->reasons) || array_filter(
            $this->reasons,
            static fn ($reason): bool => is_string($reason) && trim($reason) !== '',
        ) !== $this->reasons)) {
            throw new InvalidArgumentException('Les raisons doivent être une liste de textes non vides.');
        }
    }
}
