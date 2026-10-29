<?php

namespace App\Domain\Analytics\DTOs;

/**
 * Alerte d'anomalie détectée par AnomalyDetector. Les seuils de severity
 * sont configurables via Settings (rule feedback_always_configurable_settings).
 */
final class AnomalyAlert
{
    public const SEVERITY_INFO = 'info';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_CRITICAL = 'critical';

    public function __construct(
        public readonly string $type,
        public readonly string $severity,
        public readonly string $entityType,
        public readonly int $entityId,
        public readonly float $score,
        public readonly string $message,
        public readonly array $context = [],
    ) {}

    public function isCritical(): bool
    {
        return $this->severity === self::SEVERITY_CRITICAL;
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'severity' => $this->severity,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'score' => $this->score,
            'message' => $this->message,
            'context' => $this->context,
        ];
    }
}
