<?php

namespace App\Domain\OfficialDocuments\Services;

use App\Domain\OfficialDocuments\Models\OfficialDocument;
use App\Models\ESBTPLMDJury;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use Throwable;

class OfficialDocumentService
{
    public function __construct(
        private readonly OfficialDocumentStorage $storage,
        private readonly OfficialDocumentEventRecorder $events,
        private readonly OfficialDocumentIntegrityService $integrity,
        private readonly JuryPvIssuanceGuard $guard,
        private readonly JuryPvSnapshotBuilder $snapshots,
        private readonly JuryPvRenderer $renderer,
        private readonly PvNumberSequenceService $sequences,
    ) {}

    public function existingJuryPv(ESBTPLMDJury $jury): ?OfficialDocument
    {
        return $this->jurySeries($jury)->whereIn('status', [
            OfficialDocument::STATUS_VALID,
            OfficialDocument::STATUS_LEGACY,
        ])->orderByDesc('version')->first();
    }

    public function issueJuryPv(
        ESBTPLMDJury $jury,
        User $actor,
        ?string $supersessionReason = null,
    ): OfficialDocument {
        $storedPath = null;
        try {
            return DB::transaction(function () use ($jury, $actor, $supersessionReason, &$storedPath): OfficialDocument {
                $lockedJury = ESBTPLMDJury::query()->lockForUpdate()->findOrFail($jury->id);
                $series = $this->lockedSeries($lockedJury);
                $current = $series->firstWhere('status', OfficialDocument::STATUS_VALID);
                if ($current && $supersessionReason === null) {
                    return $this->integrity->assertValidAndIntact($current, false, $actor->id);
                }
                $legacy = $series->firstWhere('status', OfficialDocument::STATUS_LEGACY);
                if (! $current && $legacy && $supersessionReason === null) {
                    return $this->integrity->assertValidAndIntact($legacy, true, $actor->id);
                }

                $this->assertReplacementReason($current, $supersessionReason);
                $state = $this->guard->assertIssuable($jury->id, $supersessionReason !== null);
                $lockedJury = $state['jury'];
                $number = $this->ensurePvNumber($lockedJury);
                $identity = $this->newIdentity($number, $this->nextVersion($series));
                $snapshot = $this->snapshots->build($state, $identity, $actor, now());
                $contents = $this->renderer->render($snapshot, $identity['verification_code']);
                $this->storeAndVerify('lmd-jury-pv:'.$lockedJury->id, $identity, $contents, $storedPath);
                $previous = $series->sortByDesc('version')->first();
                if ($current) $this->markSuperseded($current, $supersessionReason, $actor);
                return $this->persistIssuedPv($lockedJury, $previous, $identity, $snapshot, $contents, $storedPath, $actor);
            });
        } catch (OfficialDocumentIntegrityException $exception) {
            if ($storedPath) $this->storage->delete($storedPath);
            $this->events->record(new OfficialDocument(['reference' => $exception->reference]), 'integrity_failed', $actor->id);
            throw $exception;
        } catch (Throwable $exception) {
            if ($storedPath) $this->storage->delete($storedPath);
            throw $exception;
        }
    }

    public function verify(string $reference, string $code, string $fingerprint): ?OfficialDocument
    {
        $document = OfficialDocument::query()->where('reference', trim($reference))->first();
        $storedDigest = $document?->verification_code_digest;
        $comparisonDigest = is_string($storedDigest) && strlen($storedDigest) === 64
            ? $storedDigest
            : hash('sha256', 'official-document-verification-dummy');
        $codeMatches = hash_equals($comparisonDigest, hash('sha256', $code));

        if (! $codeMatches || ! $document) return null;
        try {
            $this->integrity->assertValidAndIntact($document);
        } catch (OfficialDocumentIntegrityException) {
            return null;
        }
        $this->events->record($document, 'verified', null, [], null, null, null, $fingerprint);
        return $document;
    }

    public function revoke(OfficialDocument $document, string $reason, ?User $actor): OfficialDocument
    {
        if (trim($reason) === '') throw new \InvalidArgumentException('Le motif de révocation est obligatoire.');
        return DB::transaction(function () use ($document, $reason, $actor): OfficialDocument {
            $locked = OfficialDocument::query()->lockForUpdate()->findOrFail($document->id);
            if ($locked->status !== OfficialDocument::STATUS_VALID) throw new LogicException('Seul un document valide peut être révoqué.');
            $locked->forceFill(['status' => OfficialDocument::STATUS_REVOKED, 'valid_series_key' => null, 'revoked_at' => now(), 'revoked_by' => $actor?->id, 'revocation_reason' => $reason])->save();
            $this->events->record($locked, 'revoked', $actor?->id, [], $reason, OfficialDocument::STATUS_VALID, OfficialDocument::STATUS_REVOKED);
            return $locked;
        });
    }

    private function jurySeries(ESBTPLMDJury $jury)
    {
        return OfficialDocument::query()->forSource(ESBTPLMDJury::class, $jury->id)
            ->where('document_type', OfficialDocument::TYPE_LMD_JURY_PV);
    }

    private function lockedSeries(ESBTPLMDJury $jury)
    {
        return $this->jurySeries($jury)->orderByDesc('version')->lockForUpdate()->get();
    }

    private function ensurePvNumber(ESBTPLMDJury $jury): string
    {
        if ($jury->pv_numero) return $jury->pv_numero;
        $number = $this->sequences->next($jury->annee_universitaire_id);
        $jury->forceFill(['pv_numero' => $number])->save();
        return $number;
    }

    private function newIdentity(string $number, int $version): array
    {
        return ['number' => $number, 'version' => $version, 'reference' => sprintf('DOC-%s-V%d-%s', $number, $version, Str::upper(Str::random(10))), 'verification_code' => Str::upper(Str::random(48))];
    }

    private function nextVersion($series): int
    {
        return max(1, ((int) $series->max('version')) + 1);
    }

    private function assertReplacementReason(?OfficialDocument $current, ?string $reason): void
    {
        if ($current && trim((string) $reason) === '') throw new \InvalidArgumentException('Le motif de remplacement est obligatoire.');
    }

    private function storeAndVerify(string $seriesKey, array $identity, string $contents, ?string &$storedPath): void
    {
        $storedPath = $this->storage->storePdf($seriesKey, $identity['version'], $identity['reference'], $contents);
        if (! hash_equals(hash('sha256', $contents), $this->storage->checksum($storedPath))) {
            throw new OfficialDocumentIntegrityException(0, $identity['reference']);
        }
    }

    private function markSuperseded(OfficialDocument $current, ?string $reason, User $actor): void
    {
        $current->forceFill(['status' => OfficialDocument::STATUS_SUPERSEDED, 'valid_series_key' => null, 'lifecycle_metadata' => ['supersession_reason' => $reason]])->save();
        $this->events->record($current, 'superseded', $actor->id, [], $reason, OfficialDocument::STATUS_VALID, OfficialDocument::STATUS_SUPERSEDED);
    }

    private function persistIssuedPv(ESBTPLMDJury $jury, ?OfficialDocument $previous, array $identity, array $snapshot, string $contents, string $path, User $actor): OfficialDocument
    {
        $seriesKey = 'lmd-jury-pv:'.$jury->id;
        $document = OfficialDocument::query()->create(['document_type' => OfficialDocument::TYPE_LMD_JURY_PV, 'source_type' => ESBTPLMDJury::class, 'source_id' => $jury->id, 'series_key' => $seriesKey, 'version' => $identity['version'], 'reference' => $identity['reference'], 'status' => OfficialDocument::STATUS_VALID, 'valid_series_key' => $seriesKey, 'disk' => OfficialDocumentStorage::DISK, 'path' => $path, 'original_name' => $identity['number'].'-v'.$identity['version'].'.pdf', 'mime_type' => 'application/pdf', 'size_bytes' => strlen($contents), 'checksum_sha256' => hash('sha256', $contents), 'snapshot' => $snapshot, 'snapshot_sha256' => hash('sha256', $this->snapshots->canonicalJson($snapshot)), 'rules_version' => JuryPvSnapshotBuilder::RULES_VERSION, 'template_version' => 'lmd-jury-pv-v3', 'renderer_version' => 'dompdf-v2', 'verification_code_digest' => hash('sha256', $identity['verification_code']), 'issued_by' => $actor->id, 'issued_at' => $snapshot['issuance']['issued_at'], 'supersedes_document_id' => $previous?->id]);
        $jury->forceFill(['pv_path' => $path, 'pv_genere_at' => now(), 'pv_genere_par' => $actor->id, 'status' => 'clos', 'clos_at' => now(), 'updated_by' => $actor->id])->save();
        $jury->decisions()->update(['locked' => true, 'locked_at' => now()]);
        $this->events->record($document, 'issued', $actor->id, ['jury_id' => $jury->id, 'version' => $document->version]);
        return $document;
    }
}
