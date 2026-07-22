<?php

namespace App\Domain\OfficialDocuments\Services;

use App\Domain\OfficialDocuments\Models\OfficialDocument;
use Illuminate\Support\Facades\URL;

class OfficialDocumentDownloadService
{
    public function __construct(private readonly OfficialDocumentIntegrityService $integrity) {}

    public function signedUrl(OfficialDocument $document, bool $inline = false): string
    {
        $this->integrity->assertValidAndIntact($document, true, auth()->id());
        $expires = now()->addMinutes(5);
        return URL::temporarySignedRoute('esbtp.lmd.jurys.official-documents.stream', $expires, [
            'document' => $document->id,
            'inline' => $inline ? 1 : 0,
            'document_expires' => $expires->getTimestamp(),
            'document_token' => $this->token($document, $expires->getTimestamp()),
        ]);
    }

    public function assertToken(OfficialDocument $document, int $expires, string $token): bool
    {
        return $expires >= now()->getTimestamp()
            && hash_equals($this->token($document, $expires), $token);
    }

    private function token(OfficialDocument $document, int $expires): string
    {
        return hash_hmac('sha256', implode('|', [$document->id, $document->checksum_sha256, $expires]), (string) config('app.key'));
    }
}
