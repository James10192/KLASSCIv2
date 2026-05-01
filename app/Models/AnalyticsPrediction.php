<?php

namespace App\Models;

use App\Domain\Analytics\DTOs\AnalyticsContext;
use App\Domain\Analytics\DTOs\ConfidenceInterval;
use App\Domain\Analytics\DTOs\PredictionResult;
use Illuminate\Database\Eloquent\Model;

class AnalyticsPrediction extends Model
{
    protected $table = 'analytics_predictions';

    protected $fillable = [
        'predictor',
        'context_hash',
        'context_json',
        'target_date',
        'predicted_value',
        'predicted_label',
        'confidence_lower',
        'confidence_upper',
        'confidence_label',
        'explanation_json',
        'metadata_json',
        'actual_value',
        'accuracy_score',
        'computed_at',
    ];

    protected $casts = [
        'context_json' => 'array',
        'explanation_json' => 'array',
        'metadata_json' => 'array',
        'predicted_value' => 'float',
        'confidence_lower' => 'float',
        'confidence_upper' => 'float',
        'actual_value' => 'float',
        'accuracy_score' => 'float',
        'target_date' => 'date',
        'computed_at' => 'datetime',
    ];

    public static function fromResult(PredictionResult $result, AnalyticsContext $context): self
    {
        return new self([
            'predictor' => $result->predictor,
            'context_hash' => $context->hash(),
            'context_json' => $context->toArray(),
            'target_date' => $result->targetDate,
            'predicted_value' => $result->value,
            'predicted_label' => $result->label,
            'confidence_lower' => $result->confidenceInterval?->lower,
            'confidence_upper' => $result->confidenceInterval?->upper,
            'confidence_label' => $result->confidenceLabel,
            'explanation_json' => $result->explanation,
            'metadata_json' => $result->metadata,
            'computed_at' => now(),
        ]);
    }

    public function toResult(): PredictionResult
    {
        $ci = ($this->confidence_lower !== null && $this->confidence_upper !== null)
            ? new ConfidenceInterval(
                lower: (float) $this->confidence_lower,
                upper: (float) $this->confidence_upper,
                percentile: 95,
            )
            : null;

        return new PredictionResult(
            predictor: $this->predictor,
            value: $this->predicted_value !== null ? (float) $this->predicted_value : null,
            label: $this->predicted_label,
            confidenceInterval: $ci,
            confidenceLabel: $this->confidence_label ?? 'indicatif',
            explanation: $this->explanation_json ?? [],
            targetDate: $this->target_date,
            metadata: $this->metadata_json ?? [],
        );
    }
}
