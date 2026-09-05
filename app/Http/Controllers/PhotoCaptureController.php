<?php

namespace App\Http\Controllers;

use App\Models\ESBTPCapturePhoto;
use App\Services\Photos\CapturePhotoParTelephone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * La page qu'ouvre le téléphone après avoir scanné le code QR.
 *
 * PUBLIQUE, sans compte : celui qui photographie est souvent l'étudiant
 * lui-même, et il n'aura jamais de compte au moment où on le prend en photo. Le
 * jeton de l'adresse est la seule clé, et il est fait pour ne presque rien
 * valoir — une page, un envoi, quelques minutes, un nom.
 *
 * Ce contrôleur ne dit JAMAIS pourquoi un lien ne marche pas — expiré, déjà
 * utilisé, inconnu. La même page pour les trois : distinguer « ce jeton n'existe
 * pas » de « ce jeton a expiré » offrirait à qui essaie des jetons au hasard le
 * moyen de savoir lesquels ont existé.
 */
class PhotoCaptureController extends Controller
{
    public function __construct(private readonly CapturePhotoParTelephone $captures)
    {
    }

    public function montrer(string $jeton)
    {
        $capture = $this->capture($jeton);

        if ($capture === null) {
            return response()->view('photo-capture.expiree', [], 410);
        }

        $etudiant = $capture->etudiant;

        return view('photo-capture.prendre', [
            'jeton' => $jeton,
            // Le prenom et le nom, rien d'autre : de quoi savoir qui l'on
            // photographie. Ni matricule, ni classe, ni dossier.
            'nom' => trim(($etudiant->prenoms ?? '').' '.($etudiant->nom ?? '')),
            'expireDans' => max(0, now()->diffInSeconds($capture->expire_at, false)),
        ]);
    }

    public function envoyer(Request $request, string $jeton): JsonResponse
    {
        $capture = $this->capture($jeton);

        if ($capture === null) {
            return response()->json([
                'success' => false,
                'message' => 'Ce lien n’est plus valable. Demandez-en un nouveau au guichet.',
            ], 410);
        }

        $request->validate([
            // 12 Mo : une photo de telephone recent depasse largement les 5 Mo
            // que tolerait l'ancien televersement, et rien ne justifie de faire
            // recommencer quelqu'un qui a bien cadre.
            'photo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:12288'],
        ], [
            'photo.required' => 'Aucune photo reçue.',
            'photo.image' => 'Le fichier envoyé n’est pas une image.',
            'photo.max' => 'La photo est trop lourde. Réessayez avec une définition plus basse.',
        ]);

        try {
            $this->captures->recevoir($capture, $request->file('photo'), $request->ip());
        } catch (Throwable $e) {
            Log::error('Capture photo : envoi refuse', [
                'capture_id' => $capture->id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => "La photo n'a pas pu être envoyée. Réessayez.",
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Photo envoyée. Le guichet la regarde.',
        ]);
    }

    /**
     * La capture derrière ce jeton, ou null si elle ne s'ouvre plus.
     *
     * `estOuverte()` couvre les trois cas d'un seul test : expirée, déjà
     * utilisée, abandonnée au profit d'un code plus récent.
     */
    private function capture(string $jeton): ?ESBTPCapturePhoto
    {
        $capture = ESBTPCapturePhoto::with('etudiant:id,nom,prenoms')
            ->where('jeton', $jeton)
            ->first();

        return $capture && $capture->estOuverte() ? $capture : null;
    }
}
