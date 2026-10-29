<?php

namespace App\Http\Controllers;

use App\Models\ESBTPAnnonce;
use App\Services\Annonces\CourrielAnnonce;
use App\Services\Annonces\RelancerCourrielsAnnonce;
use App\Support\ListeInfinie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

final class ESBTPAnnonceCourrielController extends Controller
{
    public const STATUTS = [
        'pending' => 'En attente', 'processing' => 'En cours',
        'retrying' => 'Nouvelle tentative', 'awaiting_dispatch' => 'À confirmer',
        'submitted' => 'Soumis', 'delayed' => 'Retardé',
        'delivered' => 'Délivré', 'failed' => 'Échec',
    ];

    public function liste(Request $request, ESBTPAnnonce $annonce): JsonResponse
    {
        abort_unless($annonce->email_notification_enabled, 404);
        $filtre = $request->query('courriels_statut');
        $filtre = is_string($filtre) && isset(self::STATUTS[$filtre]) ? $filtre : null;
        $destinataires = DB::table('esbtp_annonce_email_deliveries')
            ->where('annonce_id', $annonce->id)
            ->when($filtre, fn ($q) => $q->where('status', $filtre))
            ->select(['id', 'email', 'status', 'attempts', 'updated_at', 'error_code'])
            ->orderByDesc('updated_at')->orderByDesc('id')
            ->paginate(25);
        return ListeInfinie::reponse(
            $destinataires,
            fn ($ligne) => view('esbtp.annonces._courriel-ligne', compact('ligne'))->render(),
            ['resume' => $this->resumeStatuts($annonce)]
        );
    }

    public function apercu(ESBTPAnnonce $annonce, CourrielAnnonce $courriel): Response
    {
        abort_unless($annonce->email_notification_enabled, 404);

        return response($courriel->html($annonce), 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Cache-Control' => 'private, no-store',
            'Content-Security-Policy' => "sandbox; default-src 'none'; img-src https: data:; style-src 'unsafe-inline'; font-src https: data:; base-uri 'none'; form-action 'none'",
            'Referrer-Policy' => 'no-referrer',
        ]);
    }

    public function reprendre(ESBTPAnnonce $annonce, RelancerCourrielsAnnonce $service): JsonResponse
    {
        $resultat = $service->reprendre($annonce);

        return response()->json(['success' => true] + $resultat + [
            'resume' => $this->resumeStatuts($annonce),
        ]);
    }

    private function resumeStatuts(ESBTPAnnonce $annonce): array
    {
        $compteurs = DB::table('esbtp_annonce_email_deliveries')
            ->where('annonce_id', $annonce->id)
            ->select('status', DB::raw('COUNT(*) AS total'))
            ->groupBy('status')->pluck('total', 'status');
        return [
            'total' => (int) $compteurs->sum(),
            'pending' => (int) ($compteurs['pending'] ?? 0)
                + (int) ($compteurs['processing'] ?? 0) + (int) ($compteurs['retrying'] ?? 0),
            'replayable' => (int) ($compteurs['pending'] ?? 0) + (int) ($compteurs['retrying'] ?? 0),
            'can_resume' => $annonce->isActive() && $annonce->email_notification_enabled
                && ! \Illuminate\Support\Facades\Cache::has(\App\Jobs\Annonces\RelancerCampagneCourriels::cle($annonce->id)),
            'submitted' => (int) ($compteurs['submitted'] ?? 0),
            'delayed' => (int) ($compteurs['delayed'] ?? 0),
            'delivered' => (int) ($compteurs['delivered'] ?? 0),
            'failed' => (int) ($compteurs['failed'] ?? 0),
            'unconfirmed' => (int) ($compteurs['awaiting_dispatch'] ?? 0),
        ];
    }
}
