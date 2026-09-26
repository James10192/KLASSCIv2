<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Une meme decision servie a deux ecrans : l'ancien, qui soumet un formulaire
 * et attend une redirection, et le nouveau, qui appelle en arriere-plan et
 * attend du JSON. La decision ne change pas ; seule la reponse s'adapte.
 *
 * Deux formes : repondre(), pour une action qui sait deja ce qu'elle dit, et
 * traduireRedirection(), pour une action ecrite en redirections (le long flux
 * d'inscription) qu'on ne reecrit pas pour autant.
 */
trait RepondEnJsonOuRedirige
{
    /** @param  array<string, mixed>  $extra */
    protected function repondre(Request $request, bool $ok, string $message, array $extra = []): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['ok' => $ok, 'message' => $message] + $extra, $ok ? 200 : 422);
        }

        return back()->with($ok ? 'success' : 'error', $message);
    }

    /**
     * Une redirection deja construite, dite en JSON quand l'ecran l'attend.
     *
     * Un refus a pose son message en session pour la page suivante : il est lu
     * puis retire, sinon il s'afficherait sur le prochain ecran ouvert. Un
     * succes garde ses messages : la page d'arrivee, ouverte juste apres, les
     * montre (identifiants du compte, demande de photo).
     */
    protected function traduireRedirection(Request $request, mixed $reponse): mixed
    {
        if (! $request->expectsJson() || ! $reponse instanceof RedirectResponse) {
            return $reponse;
        }

        $session = session();
        $erreurs = $session->get('errors')?->getBag('default')->toArray() ?? [];

        if ($session->has('error') || $erreurs !== []) {
            $corps = [
                'ok' => false,
                'message' => $session->get('error') ?? collect($erreurs)->flatten()->first(),
                'errors' => $erreurs,
                'doublons' => $session->get('duplicate_suggestions', []),
            ];
            $session->forget(['error', 'errors', 'duplicate_suggestions', 'paywall_contact', '_old_input']);

            return response()->json($corps, 422);
        }

        return response()->json([
            'ok' => true,
            'message' => $session->get('success'),
            'warning' => $session->get('warning'),
            'redirect' => $reponse->getTargetUrl(),
        ]);
    }
}
