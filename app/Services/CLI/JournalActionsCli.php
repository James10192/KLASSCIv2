<?php

namespace App\Services\CLI;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OwenIt\Auditing\Models\Audit;

/**
 * Trace une action d'ecriture lancee a distance par le CLI : dans le journal
 * d'audit (qui, quand, depuis ou, quoi) et dans le journal applicatif. Pas de
 * donnee personnelle : des compteurs et des chemins.
 */
class JournalActionsCli
{
    /** @param  array<string, mixed>  $details */
    public function consigner(Request $request, string $evenement, array $details): void
    {
        $user = $request->user();

        try {
            Audit::query()->create([
                'user_type' => $user ? $user->getMorphClass() : null,
                'user_id' => $user?->getKey(),
                'event' => $evenement,
                'auditable_type' => $user ? $user->getMorphClass() : 'cli',
                'auditable_id' => $user?->getKey() ?? 0,
                'old_values' => [],
                'new_values' => $details,
                'url' => $request->fullUrl(),
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 1023),
                'tags' => 'cli',
            ]);
        } catch (\Throwable $e) {
            Log::error('Journal CLI : audit non ecrit', ['evenement' => $evenement, 'erreur' => $e->getMessage()]);
        }

        Log::warning('[cli] '.$evenement, $details + ['par_utilisateur' => $user?->getKey()]);
    }
}
