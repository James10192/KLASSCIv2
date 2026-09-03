<?php

namespace App\Domain\OfficialDocuments\Services;

use App\Domain\OfficialDocuments\Models\OfficialDocument;
use Illuminate\Support\Facades\URL;

class OfficialDocumentDownloadService
{
    public function __construct(private readonly OfficialDocumentIntegrityService $integrity) {}

    /**
     * Lien signe de telechargement.
     *
     * La route de diffusion est un parametre : chaque type de document a la sienne,
     * avec les permissions qui lui correspondent. Un releve de notes ne doit pas
     * transiter par la route des PV de jury, dont l'acces exige `lmd.jury.view`.
     * Le defaut reste la route des PV pour ne rien changer aux appels existants.
     */
    public function signedUrl(
        OfficialDocument $document,
        bool $inline = false,
        string $routeName = 'esbtp.lmd.jurys.official-documents.stream',
    ): string {
        $this->integrity->assertValidAndIntact($document, true, auth()->id());
        $expires = now()->addMinutes(5);
        return URL::temporarySignedRoute($routeName, $expires, [
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
