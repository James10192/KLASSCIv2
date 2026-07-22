<?php

namespace App\Domain\OfficialDocuments\Services;

use App\Domain\OfficialDocuments\Models\OfficialDocument;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OfficialDocumentStorage
{
    public const DISK = 'local';

    public function storePdf(string $seriesKey, int $version, string $reference, string $contents): string
    {
        $path = sprintf('official-documents/%s/v%d/%s.pdf', hash('sha256', $seriesKey), $version, $reference);
        Storage::disk(self::DISK)->put($path, $contents);

        return $path;
    }

    public function contents(string $path): string
    {
        if (! Storage::disk(self::DISK)->exists($path)) {
            throw new NotFoundHttpException('Document officiel introuvable.');
        }

        return Storage::disk(self::DISK)->get($path);
    }

    public function checksum(string $path): string
    {
        return hash('sha256', $this->contents($path));
    }

    public function delete(string $path): void
    {
        Storage::disk(self::DISK)->delete($path);
    }

    public function size(string $path): int
    {
        return Storage::disk(self::DISK)->size($path);
    }

    public function stream(OfficialDocument $document, bool $inline)
    {
        return Storage::disk(self::DISK)->response(
            $document->path,
            $document->original_name,
            ['Content-Type' => $document->mime_type],
            $inline ? 'inline' : 'attachment',
        );
    }
}
