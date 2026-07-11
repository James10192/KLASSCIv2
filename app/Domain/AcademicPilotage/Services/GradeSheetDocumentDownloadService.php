<?php

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\Models\GradeSheetDocument;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class GradeSheetDocumentDownloadService
{
    private const SIGNED_URL_TTL_MINUTES = 5;

    public function __construct(
        private readonly GradeSheetDocumentStorage $storage,
    ) {}

    public function signedUrl(
        GradeSheetDocument $document,
        int $minutes = self::SIGNED_URL_TTL_MINUTES,
    ): string {
        $expiration = now()->addMinutes($minutes);
        $downloadExpires = $expiration->getTimestamp();

        return URL::temporarySignedRoute(
            'esbtp.academic-sheets.documents.download',
            $expiration,
            [
                'document' => $document->getRouteKey(),
                'download_expires' => $downloadExpires,
                'document_token' => $this->token($document, $downloadExpires),
            ],
        );
    }

    public function download(
        GradeSheetDocument $document,
        int $expires,
        ?string $token,
    ): StreamedResponse {
        $expected = $this->token($document, $expires);

        if ($expires < now()->getTimestamp()
            || ! is_string($token)
            || ! hash_equals($expected, $token)) {
            abort(403, 'Lien de téléchargement invalide ou expiré.');
        }

        return $this->storage->stream($document);
    }

    private function token(GradeSheetDocument $document, int $expires): string
    {
        $payload = implode('|', [
            $document->getKey(),
            $document->checksum_sha256,
            $expires,
        ]);

        return hash_hmac('sha256', $payload, (string) config('app.key'));
    }
}
