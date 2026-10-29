<?php

namespace App\Domain\OfficialDocuments\Services;

use App\Domain\OfficialDocuments\Models\OfficialDocument;
use App\Domain\OfficialDocuments\Models\OfficialDocumentEvent;

class OfficialDocumentEventRecorder
{
    public function record(
        ?OfficialDocument $document,
        string $event,
        ?int $actorId = null,
        array $metadata = [],
        ?string $reason = null,
        ?string $fromStatus = null,
        ?string $toStatus = null,
        ?string $fingerprint = null,
    ): void {
        OfficialDocumentEvent::query()->create([
            'official_document_id' => $document?->id,
            'reference' => $document?->reference,
            'event_type' => $event,
            'from_status' => $fromStatus,
            'to_status' => $toStatus ?? $document?->status,
            'actor_id' => $actorId,
            'request_fingerprint' => $fingerprint,
            'reason' => $reason,
            'metadata' => $metadata ?: null,
            'occurred_at' => now(),
        ]);
    }
}
