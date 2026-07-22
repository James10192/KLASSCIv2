<?php

namespace App\Domain\OfficialDocuments\Services;

use App\Domain\AcademicPilotage\Services\GradeSheetDocumentContentValidator;
use App\Domain\OfficialDocuments\Models\OfficialDocument;
use App\Models\ESBTPLMDJury;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use LogicException;

class LegacyJuryPvReconciliationService
{
    public function __construct(
        private readonly GradeSheetDocumentContentValidator $validator,
        private readonly OfficialDocumentEventRecorder $events,
        private readonly OfficialDocumentIntegrityService $integrity,
    ) {}

    public function reconcile(ESBTPLMDJury $jury, User $actor): OfficialDocument
    {
        return DB::transaction(function () use ($jury, $actor): OfficialDocument {
            $locked = ESBTPLMDJury::query()->lockForUpdate()->findOrFail($jury->id);
            $existing = $this->existingDocument($locked);
            if ($existing) return $this->integrity->assertValidAndIntact($existing, true, $actor->id);
            if (! $locked->pv_path || ! Storage::disk('local')->exists($locked->pv_path)) {
                throw new LogicException('Le fichier PV historique est introuvable.');
            }

            $this->assertRealPdf($locked->pv_path);
            $document = $this->persistLegacy($locked, $actor);
            $this->events->record($document, 'reconciled', $actor->id, ['jury_id' => $locked->id, 'snapshot_available' => false]);
            return $document;
        });
    }

    private function existingDocument(ESBTPLMDJury $jury): ?OfficialDocument
    {
        return OfficialDocument::query()->forSource(ESBTPLMDJury::class, $jury->id)
            ->where('document_type', OfficialDocument::TYPE_LMD_JURY_PV)
            ->lockForUpdate()->orderByDesc('version')->first();
    }

    private function assertRealPdf(string $path): void
    {
        $absolutePath = Storage::disk('local')->path($path);
        $file = new UploadedFile($absolutePath, basename($path), 'application/pdf', null, true);
        $this->validator->assertValid($file);
    }

    private function persistLegacy(ESBTPLMDJury $jury, User $actor): OfficialDocument
    {
        $path = $jury->pv_path;
        $seriesKey = 'lmd-jury-pv:'.$jury->id;
        return OfficialDocument::query()->create([
            'document_type' => OfficialDocument::TYPE_LMD_JURY_PV, 'source_type' => ESBTPLMDJury::class,
            'source_id' => $jury->id, 'series_key' => $seriesKey, 'version' => 1,
            'reference' => 'LEGACY-PV-'.Str::upper(Str::random(20)), 'status' => OfficialDocument::STATUS_LEGACY,
            'valid_series_key' => null, 'disk' => 'local', 'path' => $path, 'original_name' => basename($path),
            'mime_type' => 'application/pdf', 'size_bytes' => Storage::disk('local')->size($path),
            'checksum_sha256' => hash('sha256', Storage::disk('local')->get($path)),
            'snapshot' => null, 'snapshot_sha256' => null, 'rules_version' => null,
            'template_version' => null, 'renderer_version' => null, 'verification_code_digest' => null,
            'issued_by' => $jury->pv_genere_par, 'issued_at' => $jury->pv_genere_at ?? now(),
            'lifecycle_metadata' => ['provenance' => 'legacy_jury_pv_path', 'original_path' => $path, 'reconciled_at' => now()->toIso8601String(), 'reconciled_by' => $actor->id, 'historical_snapshot_unavailable' => true],
        ]);
    }
}
