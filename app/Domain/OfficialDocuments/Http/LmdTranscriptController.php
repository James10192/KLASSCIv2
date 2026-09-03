<?php

namespace App\Domain\OfficialDocuments\Http;

use App\Domain\OfficialDocuments\Exceptions\LmdTranscriptNotIssuableException;
use App\Domain\OfficialDocuments\Services\LmdTranscriptIssuanceGuard;
use App\Domain\OfficialDocuments\Services\LmdTranscriptRenderer;
use App\Domain\OfficialDocuments\Services\LmdTranscriptService;
use App\Domain\OfficialDocuments\Services\OfficialDocumentDownloadService;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPEtudiant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Releve de notes LMD : emission, apercu, telechargement.
 *
 * Les noms de methodes evitent `validate`, `failed` et `authorize`, reserves par
 * Laravel (cf. rule controller-naming).
 */
class LmdTranscriptController
{
    /** Nom de la route de diffusion propre aux releves. */
    public const STREAM_ROUTE = 'esbtp.lmd.releves.official-documents.stream';

    /**
     * Emet le releve, ou rend celui deja emis si rien n'est a rectifier.
     */
    public function issue(
        Request $request,
        ESBTPEtudiant $etudiant,
        ESBTPAnneeUniversitaire $annee,
        LmdTranscriptService $transcripts,
    ): JsonResponse|RedirectResponse {
        abort_unless($request->user()?->can('lmd.releve.issue'), 403);

        $validated = $request->validate([
            'motif' => ['nullable', 'string', 'min:10', 'max:1000'],
        ]);

        try {
            $document = $transcripts->issue(
                $etudiant,
                $annee,
                $request->user(),
                $validated['motif'] ?? null,
            );
        } catch (LmdTranscriptNotIssuableException $exception) {
            return $this->failure($request, implode(' ', $exception->reasons), 422, [
                'reasons' => $exception->reasons,
            ]);
        } catch (\InvalidArgumentException $exception) {
            return $this->failure($request, $exception->getMessage(), 422);
        } catch (\Throwable $exception) {
            Log::error('Echec de l emission du releve de notes LMD.', [
                'etudiant_id' => $etudiant->id,
                'annee_universitaire_id' => $annee->id,
                'exception' => $exception,
            ]);

            return $this->failure($request, 'Le releve de notes n a pas pu etre emis.', 422);
        }

        if (! $request->expectsJson()) {
            return back()->with('success', 'Le releve de notes est emis sous la reference '.$document->reference.'.');
        }

        return response()->json([
            'success' => true,
            'releve' => [
                'reference' => $document->reference,
                'version' => $document->version,
                'status' => $document->status,
                'issued_at' => $document->issued_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Apercu en ligne : le PDF est reconstruit depuis l'instantane et rendu
     * `inline`, sans telechargement. Le fichier fige, lui, reste celui de
     * `download` — c'est lui qui fait foi.
     */
    public function preview(
        Request $request,
        ESBTPEtudiant $etudiant,
        ESBTPAnneeUniversitaire $annee,
        LmdTranscriptService $transcripts,
        LmdTranscriptRenderer $renderer,
    ): Response {
        abort_unless($request->user()?->can('lmd.releve.view'), 403);

        $document = $transcripts->existing($etudiant, $annee);
        abort_unless($document && is_array($document->snapshot), 404, 'Aucun releve de notes disponible.');

        $pdf = $renderer->render($document->snapshot, (string) $document->reference);

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.($document->original_name ?? 'releve.pdf').'"',
        ]);
    }

    /**
     * Telechargement du fichier fige, via une URL signee a duree courte.
     */
    public function download(
        Request $request,
        ESBTPEtudiant $etudiant,
        ESBTPAnneeUniversitaire $annee,
        LmdTranscriptService $transcripts,
        OfficialDocumentDownloadService $downloads,
    ): RedirectResponse {
        abort_unless($request->user()?->can('lmd.releve.view'), 403);

        $document = $transcripts->existing($etudiant, $annee);
        abort_unless($document, 404, 'Aucun releve de notes disponible.');

        return redirect()->away($downloads->signedUrl($document, false, self::STREAM_ROUTE));
    }

    /**
     * Etat de preparation : permet a l'interface de dire pourquoi le releve
     * n'est pas encore emettable, au lieu de laisser l'utilisateur decouvrir un
     * refus apres avoir clique.
     */
    public function status(
        Request $request,
        ESBTPEtudiant $etudiant,
        ESBTPAnneeUniversitaire $annee,
        LmdTranscriptService $transcripts,
        LmdTranscriptIssuanceGuard $guard,
    ): JsonResponse {
        abort_unless($request->user()?->can('lmd.releve.view'), 403);

        $document = $transcripts->existing($etudiant, $annee);
        $readiness = $guard->readiness($etudiant->id, $annee->id);

        return response()->json([
            'readiness' => $readiness,
            'releve' => $document ? [
                'reference' => $document->reference,
                'version' => $document->version,
                'status' => $document->status,
                'issued_at' => $document->issued_at?->toIso8601String(),
            ] : null,
        ]);
    }

    private function failure(Request $request, string $message, int $code, array $extra = []): JsonResponse|RedirectResponse
    {
        if (! $request->expectsJson()) {
            return back()->with('error', $message);
        }

        return response()->json(array_merge(['success' => false, 'message' => $message], $extra), $code);
    }
}
