<?php

namespace App\Domain\OfficialDocuments\Http;

use App\Domain\OfficialDocuments\Models\OfficialDocument;
use App\Domain\OfficialDocuments\Services\OfficialDocumentDownloadService;
use App\Domain\OfficialDocuments\Services\OfficialDocumentEventRecorder;
use App\Domain\OfficialDocuments\Services\OfficialDocumentIntegrityException;
use App\Domain\OfficialDocuments\Services\OfficialDocumentIntegrityService;
use App\Domain\OfficialDocuments\Services\OfficialDocumentService;
use App\Domain\OfficialDocuments\Services\OfficialDocumentStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class OfficialDocumentController
{
    public function stream(Request $request, OfficialDocument $document, OfficialDocumentDownloadService $downloads, OfficialDocumentStorage $storage, OfficialDocumentEventRecorder $events, OfficialDocumentIntegrityService $integrity): Response
    {
        abort_unless($request->hasValidSignature() && $downloads->assertToken($document, (int) $request->query('document_expires'), (string) $request->query('document_token')), 403);
        try {
            $integrity->assertValidAndIntact($document, true, auth()->id());
            $response = $storage->stream($document, $request->boolean('inline'));
            $events->record($document, 'downloaded', auth()->id(), ['inline' => $request->boolean('inline')]);
            return $response;
        } catch (OfficialDocumentIntegrityException $exception) {
            abort(409, 'Le document officiel n est pas disponible.');
        }
    }

    public function verify(Request $request, OfficialDocumentService $documents): JsonResponse
    {
        $validated = $request->validate(['reference' => ['required', 'string', 'max:96'], 'code' => ['required', 'string', 'min:32', 'max:128']]);
        $fingerprint = hash('sha256', implode('|', [$request->ip(), $validated['reference']]));
        $ipKey = 'official-document-verify-ip:'.$request->ip();
        $referenceKey = 'official-document-verify-reference:'.hash('sha256', mb_strtoupper(trim($validated['reference'])));
        abort_if(RateLimiter::tooManyAttempts($ipKey, 12) || RateLimiter::tooManyAttempts($referenceKey, 6), 429);
        RateLimiter::hit($ipKey, 60);
        RateLimiter::hit($referenceKey, 60);

        $document = $documents->verify($validated['reference'], $validated['code'], $fingerprint);
        if (! $document) {
            return response()->json(['valid' => false]);
        }

        return response()->json(['valid' => true, 'reference' => $document->reference, 'document_type' => $document->document_type, 'issued_at' => $document->issued_at?->toIso8601String()]);
    }
}
