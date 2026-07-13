<?php

namespace App\Services\Scoring;

use InvalidArgumentException;

class PersonnelScoreResult
{
    public const STATE_MEASURABLE = 'measurable';

    public const STATE_NON_APPLICABLE = 'non_applicable';

    public const STATE_INSUFFICIENT_DATA = 'insufficient_data';

    public function __construct(
        public readonly string $dimension,
        public readonly string $label,
        public readonly int $score,
        public readonly int $weight,
        public readonly array $metrics = [],
        public readonly array $messages = [],
        public readonly string $state = self::STATE_MEASURABLE,
        public readonly ?int $numerator = null,
        public readonly ?int $denominator = null,
        public readonly ?float $coverage = null,
        public readonly ?float $confidence = null,
        public readonly ?string $evidenceHash = null
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        $this->require(trim($this->dimension) !== '', 'La dimension est obligatoire.');
        $this->require(trim($this->label) !== '', 'Le libellé est obligatoire.');
        $this->require($this->score >= 0 && $this->score <= 100, 'Le score doit être compris entre 0 et 100.');
        $this->require($this->weight >= 0, 'Le poids ne peut pas être négatif.');
        $this->require(in_array($this->state, self::states(), true), 'État de mesure invalide.');
        $this->validateEvidenceCounts();
        $this->validateRatios();
        $this->validateState();
        $this->require(
            $this->evidenceHash === null || preg_match('/^[a-f0-9]{64}$/', $this->evidenceHash) === 1,
            'L’empreinte de preuve doit être une valeur SHA-256 hexadécimale.'
        );
    }

    private function validateEvidenceCounts(): void
    {
        $bothNull = $this->numerator === null && $this->denominator === null;
        $bothPresent = $this->numerator !== null && $this->denominator !== null;
        $this->require($bothNull || $bothPresent, 'Le numérateur et le dénominateur doivent être fournis ensemble.');

        if (! $bothPresent) {
            return;
        }

        $this->require($this->numerator >= 0 && $this->denominator >= 0, 'Les compteurs de preuve doivent être positifs.');
        $this->require($this->numerator <= $this->denominator, 'Le numérateur ne peut pas dépasser le dénominateur.');
    }

    private function validateRatios(): void
    {
        $bothNull = $this->coverage === null && $this->confidence === null;
        $bothPresent = $this->coverage !== null && $this->confidence !== null;
        $this->require($bothNull || $bothPresent, 'La couverture et la confiance doivent être fournies ensemble.');

        if ($bothPresent) {
            $this->require($this->isRatio($this->coverage), 'La couverture doit être comprise entre 0 et 1.');
            $this->require($this->isRatio($this->confidence), 'La confiance doit être comprise entre 0 et 1.');
        }
    }

    private function validateState(): void
    {
        if ($this->state === self::STATE_MEASURABLE && $this->denominator !== null) {
            $this->require($this->denominator > 0, 'Une mesure doit avoir un dénominateur strictement positif.');
            $this->require($this->coverage !== null, 'Une mesure avec preuves doit exposer sa couverture.');
            $this->require($this->evidenceHash !== null, 'Une mesure avec preuves doit exposer leur empreinte.');
        }

        if ($this->state !== self::STATE_MEASURABLE) {
            $this->require($this->score === 0, 'Une dimension non mesurable doit avoir un score nul.');
            $this->require($this->coverage === null, 'Une dimension non mesurable ne peut pas exposer de couverture.');
        }

        if ($this->state === self::STATE_NON_APPLICABLE) {
            $this->require($this->numerator === 0 && $this->denominator === 0, 'Une dimension non applicable doit avoir des compteurs nuls.');
        }

        if ($this->state === self::STATE_INSUFFICIENT_DATA) {
            $this->require($this->numerator === null, 'Une dimension sans données ne peut pas exposer de compteurs.');
            $this->require($this->evidenceHash === null, 'Une dimension sans données ne peut pas exposer de preuve.');
        }
    }

    private function isRatio(float $value): bool
    {
        return $value >= 0.0 && $value <= 1.0;
    }

    private function require(bool $condition, string $message): void
    {
        if (! $condition) {
            throw new InvalidArgumentException($message);
        }
    }

    private static function states(): array
    {
        return [self::STATE_MEASURABLE, self::STATE_NON_APPLICABLE, self::STATE_INSUFFICIENT_DATA];
    }

    public function toArray(): array
    {
        return [
            'dimension' => $this->dimension,
            'label' => $this->label,
            'score' => $this->score,
            'weight' => $this->weight,
            'state' => $this->state,
            'numerator' => $this->numerator,
            'denominator' => $this->denominator,
            'coverage' => $this->coverage,
            'confidence' => $this->confidence,
            'evidence_hash' => $this->evidenceHash,
            'metrics' => $this->metrics,
            'messages' => $this->messages,
        ];
    }
}
