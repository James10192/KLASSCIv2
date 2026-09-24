<?php

namespace App\Http\Controllers\API\CLI;

use App\Helpers\EntityLabelHelper;
use App\Http\Controllers\API\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\Models\Audit;

/**
 * Où travaille-t-on réellement dans l'application ?
 *
 * Lu depuis le journal d'audit : chaque création, modification ou suppression
 * y porte l'URL d'où l'action est partie. On n'y voit donc pas les simples
 * consultations, mais les pages où l'on AGIT — celles qu'il faut soigner en
 * premier sur téléphone. Les identifiants sont remplacés par {id} pour que
 * « /etudiants/12/edit » et « /etudiants/98/edit » comptent pour une page.
 *
 * Lecture seule. Voir docs/api/CLI_USAGE.md.
 */
class CLIUsageController extends BaseApiController
{
    private const LIGNES_MAX = 40;

    public function pages(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $jours = max(1, min(365, (int) $request->input('jours', 30)));
        $depuis = Carbon::now()->subDays($jours);

        $parUrl = Audit::query()
            ->where('created_at', '>=', $depuis)
            ->whereNotNull('url')
            ->groupBy('url')
            ->select('url', DB::raw('COUNT(*) as n'), DB::raw('COUNT(DISTINCT user_id) as u'))
            ->get();

        $pages = [];
        foreach ($parUrl as $ligne) {
            $chemin = self::normaliser((string) $ligne->url);
            if ($chemin === null) {
                continue;
            }
            $pages[$chemin]['actions'] = ($pages[$chemin]['actions'] ?? 0) + (int) $ligne->n;
            $pages[$chemin]['utilisateurs_max'] = max($pages[$chemin]['utilisateurs_max'] ?? 0, (int) $ligne->u);
        }
        uasort($pages, fn ($a, $b) => $b['actions'] <=> $a['actions']);

        $entites = Audit::query()
            ->where('created_at', '>=', $depuis)
            ->groupBy('auditable_type', 'event')
            ->select('auditable_type', 'event', DB::raw('COUNT(*) as n'))
            ->orderByDesc('n')
            ->limit(self::LIGNES_MAX)
            ->get()
            ->map(fn ($l) => [
                'entite' => EntityLabelHelper::for((string) $l->auditable_type),
                'evenement' => $l->event,
                'actions' => (int) $l->n,
            ]);

        return $this->successResponse([
            'periode_jours' => $jours,
            'pages' => collect($pages)->take(self::LIGNES_MAX)->map(fn ($v, $k) => ['page' => $k] + $v)->values(),
            'entites' => $entites,
        ], 'Pages où l’on agit le plus');
    }

    /** Chemin sans domaine ni paramètres, identifiants remplacés par {id}. */
    public static function normaliser(string $url): ?string
    {
        $chemin = parse_url($url, PHP_URL_PATH);
        if (! is_string($chemin) || ! str_starts_with($chemin, '/')) {
            return null;
        }
        if (str_starts_with($chemin, '/api/') || str_starts_with($chemin, '/livewire')) {
            return null;
        }

        return preg_replace('#/(\d+|[0-9a-f]{8}-[0-9a-f-]{27,})(?=/|$)#i', '/{id}', rtrim($chemin, '/')) ?: '/';
    }
}
