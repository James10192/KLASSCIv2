<?php

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\Exceptions\AcademicPilotageException;
use Illuminate\Http\UploadedFile;
use ZipArchive;

final class GradeSheetDocumentContentValidator
{
    private const PNG_SIGNATURE = "\x89PNG\x0D\x0A\x1A\x0A";

    private const JPEG_SIGNATURE = "\xFF\xD8\xFF";

    private const XLS_SIGNATURE = "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1";

    public function assertValid(UploadedFile $file): void
    {
        $path = $file->getRealPath();
        $extension = strtolower($file->getClientOriginalExtension());
        $header = file_get_contents($path, false, null, 0, 8);
        $valid = match ($extension) {
            'pdf' => str_starts_with((string) $header, '%PDF-'),
            'jpg', 'jpeg' => str_starts_with((string) $header, self::JPEG_SIGNATURE),
            'png' => $header === self::PNG_SIGNATURE,
            'xls' => $header === self::XLS_SIGNATURE,
            'xlsx' => $this->isValidXlsx($path),
            default => false,
        };

        if (! $valid) {
            throw AcademicPilotageException::invalidDocumentContent();
        }
    }

    private function isValidXlsx(string $path): bool
    {
        $archive = new ZipArchive;
        if ($archive->open($path) !== true) {
            return false;
        }

        try {
            return $archive->locateName('[Content_Types].xml') !== false
                && $archive->locateName('xl/workbook.xml') !== false;
        } finally {
            $archive->close();
        }
    }
}
