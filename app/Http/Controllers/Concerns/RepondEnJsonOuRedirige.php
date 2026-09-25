<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Une meme decision servie a deux ecrans : l'ancien, qui soumet un formulaire
 * et attend une redirection, et le nouveau, qui appelle en arriere-plan et
 * attend du JSON. La decision ne change pas ; seule la reponse s'adapte.
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
}
