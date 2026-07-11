<?php

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\Exceptions\AcademicPilotageException;
use App\Domain\AcademicPilotage\Models\GradeSheet;
use App\Domain\AcademicPilotage\Models\GradeSheetDocument;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

class GradeSheetDocumentService
{
    public const MAX_DOCUMENT_SIZE_KB = 10240;

    public function __construct(
        private readonly GradeSheetEventRecorder $eventRecorder,
        private readonly GradeSheetDocumentContentValidator $contentValidator,
        private readonly GradeSheetDocumentStorage $storage,
    ) {}

    public function store(
        GradeSheet $sheet,
        UploadedFile $file,
        Authenticatable $uploader,
        int $expectedLockVersion,
    ): GradeSheetDocument {
        $this->contentValidator->assertValid($file);
        $storedPath = null;

        try {
            return DB::transaction(function () use (
                $sheet,
                $file,
                $uploader,
                $expectedLockVersion,
                &$storedPath,
            ): GradeSheetDocument {
                $current = GradeSheet::query()->lockForUpdate()->findOrFail($sheet->id);
                $this->assertUploadAllowed($current, $expectedLockVersion);
                $storedPath = $this->storage->store($current, $file);

                $document = $this->persistDocument($current, $file, $uploader, $storedPath);
                $this->recordUpload($current, $document, $uploader, $expectedLockVersion);

                return $document;
            });
        } catch (Throwable $exception) {
            if (is_string($storedPath)) {
                $this->storage->delete($storedPath);
            }

            throw $exception;
        }
    }

    private function assertUploadAllowed(GradeSheet $sheet, int $expectedVersion): void
    {
        if ($sheet->lock_version !== $expectedVersion) {
            throw AcademicPilotageException::staleGradeSheet(
                $expectedVersion,
                $sheet->lock_version,
            );
        }

        if (! $sheet->status->allowsDocumentUpload()) {
            throw AcademicPilotageException::mutationNotAllowed(
                'ajouter un document',
                $sheet->status,
            );
        }
    }

    private function persistDocument(
        GradeSheet $sheet,
        UploadedFile $file,
        Authenticatable $uploader,
        string $path,
    ): GradeSheetDocument {
        $document = $sheet->documents()->make([
            'disk' => GradeSheetDocumentStorage::DISK,
            'path' => $path,
            'original_name' => basename(str_replace('\\', '/', $file->getClientOriginalName())),
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'checksum_sha256' => hash_file('sha256', $file->getRealPath()),
            'uploaded_at' => now(),
        ]);
        $document->uploaded_by = $uploader->getAuthIdentifier();
        $document->save();

        return $document;
    }

    private function recordUpload(
        GradeSheet $sheet,
        GradeSheetDocument $document,
        Authenticatable $uploader,
        int $expectedVersion,
    ): void {
        $newVersion = $expectedVersion + 1;
        $actorId = (int) $uploader->getAuthIdentifier();
        $sheet->forceFill([
            'lock_version' => $newVersion,
            'updated_by' => $actorId,
        ])->save();
        $this->eventRecorder->record(
            $sheet,
            'document_uploaded',
            $sheet->status,
            $sheet->status,
            $actorId,
            null,
            [
                'document_id' => $document->id,
                'checksum_sha256' => $document->checksum_sha256,
                'lock_version' => $newVersion,
            ],
        );
    }
}
