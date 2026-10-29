<?php

declare(strict_types=1);

namespace App\Domain\OfficialDocuments\Services;

use App\Domain\OfficialDocuments\Models\OfficialDocument;
use App\Models\ESBTPLMDJury;

final class OfficialDocumentSnapshotIntegrity
{
    public function __construct(private readonly OfficialDocumentEventRecorder $events)
    {
    }

    public function assert(OfficialDocument $document, bool $allowLegacy, ?int $actorId): void
    {
        try {
            if ($document->status === 'legacy') {
                $this->assertLegacy($document, $allowLegacy);
                return;
            }

            $snapshot = $document->snapshot;
            if (! is_array($snapshot) || ! $document->snapshot_sha256) {
                throw new \RuntimeException('Snapshot officiel absent.');
            }

            $actual = hash('sha256', $this->canonicalJson($snapshot));
            if (! hash_equals((string) $document->snapshot_sha256, $actual)) {
                throw new \RuntimeException('Empreinte du snapshot invalide.');
            }

            $this->assertDocumentProjection($document, $snapshot);
        } catch (\Throwable $exception) {
            $this->events->record($document, 'integrity_failed', $actorId, [
                'check' => 'snapshot',
            ]);
            throw new OfficialDocumentIntegrityException($document->id, $document->reference);
        }
    }

    private function assertLegacy(OfficialDocument $document, bool $allowLegacy): void
    {
        if (! $allowLegacy) {
            throw new \RuntimeException('Un document historique doit être explicitement autorisé.');
        }
        if ($document->snapshot !== null || $document->snapshot_sha256 !== null) {
            throw new \RuntimeException('Un document historique ne peut pas avoir de faux snapshot.');
        }
    }

    private function assertDocumentProjection(OfficialDocument $document, array $snapshot): void
    {
        $projection = $snapshot['document'] ?? [];
        $matches = ($projection['reference'] ?? null) === $document->reference
            && (int) ($projection['version'] ?? 0) === (int) $document->version;

        if (! $matches) {
            throw new \RuntimeException('Projection documentaire incohérente.');
        }

        if ($document->source_type !== ESBTPLMDJury::class) {
            return;
        }

        $jury = ESBTPLMDJury::query()->find($document->source_id);
        if (! $jury || ($projection['number'] ?? null) !== $jury->pv_numero) {
            throw new \RuntimeException('Numéro de PV incohérent.');
        }
    }

    private function canonicalJson(array $value): string
    {
        $normalized = $this->normalize($value);
        return json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function normalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        if (! array_is_list($value)) {
            ksort($value, SORT_STRING);
        }
        foreach ($value as $key => $item) {
            $value[$key] = $this->normalize($item);
        }
        return $value;
    }
}
