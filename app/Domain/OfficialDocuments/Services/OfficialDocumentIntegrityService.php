<?php

namespace App\Domain\OfficialDocuments\Services;

use App\Domain\OfficialDocuments\Models\OfficialDocument;
use Throwable;

class OfficialDocumentIntegrityService
{
    public function __construct(
        private readonly OfficialDocumentStorage $storage,
        private readonly OfficialDocumentEventRecorder $events,
    ) {}

    public function assertValidAndIntact(
        OfficialDocument $document,
        bool $allowLegacy = false,
        ?int $actorId = null,
    ): OfficialDocument {
        app(OfficialDocumentSnapshotIntegrity::class)->assert($document, $allowLegacy, $actorId);
        if (! $document->isAccessible($allowLegacy)) {
            throw new OfficialDocumentIntegrityException($document->id, $document->reference);
        }

        try {
            $actual = $this->storage->checksum($document->path);
        } catch (Throwable) {
            $this->recordFailure($document, $actorId);
            throw new OfficialDocumentIntegrityException($document->id, $document->reference);
        }

        if (! hash_equals($document->checksum_sha256, $actual)) {
            $this->recordFailure($document, $actorId);
            throw new OfficialDocumentIntegrityException($document->id, $document->reference);
        }

        return $document;
    }

    private function recordFailure(OfficialDocument $document, ?int $actorId): void
    {
        $this->events->record($document, 'integrity_failed', $actorId);
    }
}
