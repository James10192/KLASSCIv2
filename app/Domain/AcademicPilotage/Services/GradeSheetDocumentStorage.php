<?php

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\Models\GradeSheet;
use App\Domain\AcademicPilotage\Models\GradeSheetDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class GradeSheetDocumentStorage
{
    public const DISK = 'local';

    private const BASE_PATH = 'academic-pilotage/grade-sheets';

    public function store(GradeSheet $sheet, UploadedFile $file): string
    {
        $path = Storage::disk(self::DISK)->putFile($this->directoryFor($sheet->id), $file);

        if ($path === false) {
            throw new \RuntimeException('Le document n\'a pas pu être stocké.');
        }

        return $path;
    }

    public function delete(string $path): void
    {
        if (! Storage::disk(self::DISK)->delete($path)) {
            Log::warning('Grade sheet orphan file cleanup failed.', ['path' => $path]);
        }
    }

    public function stream(GradeSheetDocument $document): StreamedResponse
    {
        $path = (string) $document->path;
        $expectedPrefix = $this->directoryFor($document->grade_sheet_id).'/';

        if (! str_starts_with($path, $expectedPrefix)
            || ! Storage::disk(self::DISK)->exists($path)) {
            Log::warning('Grade sheet document missing from local storage.', [
                'grade_sheet_document_id' => $document->getKey(),
                'grade_sheet_id' => $document->grade_sheet_id,
                'path' => $path,
            ]);

            abort(404, 'Document introuvable sur le serveur.');
        }

        return Storage::disk(self::DISK)->download(
            $path,
            $document->original_name,
            ['Content-Type' => $document->mime_type],
        );
    }

    private function directoryFor(int|string|null $sheetId): string
    {
        return self::BASE_PATH.'/'.(string) $sheetId;
    }
}
