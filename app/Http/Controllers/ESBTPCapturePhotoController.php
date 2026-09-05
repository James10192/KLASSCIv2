<?php

namespace App\Http\Controllers;

use App\Models\ESBTPCapturePhoto;
use App\Models\ESBTPEtudiant;
use App\Services\Photos\CapturePhotoParTelephone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Le côté guichet de la prise de vue par téléphone.
 *
 * Ouvrir le code, savoir où on en est, accepter ou refaire. Tout est gardé par
 * `students.edit` — la même permission que le téléversement classique, parce que
 * c'est le même geste par un autre chemin.
 */
class ESBTPCapturePhotoController extends Controller
{
    public function __construct(private readonly CapturePhotoParTelephone $captures)
    {
    }

    /** Affiche un code QR pour cet étudiant. */
    public function ouvrir(Request $request, ESBTPEtudiant $etudiant): JsonResponse
    {
        if (! $this->captures->actif()) {
            return response()->json([
                'success' => false,
                'message' => "La prise de vue par téléphone est désactivée pour cet établissement.",
            ], 422);
        }

        $ouverture = $this->captures->ouvrir($etudiant, $request->user()?->id);

        return response()->json([
            'success' => true,
            'capture' => $this->serialiser($ouverture['capture']),
            'url' => $ouverture['url'],
            'qr' => $ouverture['qr'],
            'duree_minutes' => $this->captures->dureeMinutes(),
        ]);
    }

    /**
     * Où en est cette prise de vue ?
     *
     * Sondée par l'écran du guichet toutes les deux secondes. KLASSCI n'a pas de
     * canal temps réel — le diffuseur est à `null` sur toutes les instances — et
     * en ouvrir un pour cette seule fonctionnalité coûterait un service de plus à
     * exploiter sur un hébergement mutualisé.
     */
    public function etat(ESBTPCapturePhoto $capture): JsonResponse
    {
        return response()->json([
            'success' => true,
            'capture' => $this->serialiser($capture),
        ]);
    }

    /**
     * La photo reçue, tant que personne ne l'a acceptée.
     *
     * Servie par cette route et non par une adresse de fichier : elle dort sur
     * le disque privé, et une photo d'élève que nul n'a encore validée n'a rien
     * à faire sur un chemin que le serveur web sert directement.
     */
    public function apercu(ESBTPCapturePhoto $capture): StreamedResponse
    {
        abort_unless(
            $capture->etat === ESBTPCapturePhoto::RECUE && $capture->fichier_provisoire,
            404
        );

        $disque = Storage::disk(CapturePhotoParTelephone::DISQUE);

        abort_unless(is_file($disque->path($capture->fichier_provisoire)), 404);

        return $disque->response($capture->fichier_provisoire, null, [
            // Jamais de cache : la meme adresse sert une photo differente des
            // qu'on en reprend une, et un navigateur qui garde la premiere ferait
            // valider une image que le guichet ne regarde plus.
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /** Le guichet accepte : la photo devient celle de l'étudiant. */
    public function accepter(Request $request, ESBTPCapturePhoto $capture): JsonResponse
    {
        try {
            $this->captures->accepter($capture, $request->user()?->id);
        } catch (Throwable $e) {
            Log::error('Capture photo : validation refusee', [
                'capture_id' => $capture->id,
                'message' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $capture->refresh()->loadMissing('etudiant');

        return response()->json([
            'success' => true,
            'message' => 'Photo enregistrée.',
            'photo_url' => $capture->etudiant->photo_url,
        ]);
    }

    /** Le guichet refuse, ou ferme la fenêtre : on refait. */
    public function refuser(Request $request, ESBTPCapturePhoto $capture): JsonResponse
    {
        $etat = $request->boolean('abandon')
            ? ESBTPCapturePhoto::ABANDONNEE
            : ESBTPCapturePhoto::REFUSEE;

        $this->captures->ecarter($capture, $request->user()?->id, $etat);

        return response()->json([
            'success' => true,
            'message' => $etat === ESBTPCapturePhoto::REFUSEE
                ? 'Photo écartée. Affichez un nouveau code pour recommencer.'
                : 'Prise de vue fermée.',
        ]);
    }

    /** @return array<string, mixed> */
    private function serialiser(ESBTPCapturePhoto $capture): array
    {
        return [
            'id' => $capture->id,
            'etat' => $capture->etat,
            'expire_at' => $capture->expire_at?->toIso8601String(),
            'expiree' => $capture->estExpiree(),
            'attend_decision' => $capture->attendUneDecision(),
            'apercu' => $capture->attendUneDecision()
                ? route('esbtp.captures-photo.apercu', $capture)
                : null,
        ];
    }
}
