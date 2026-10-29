<?php

namespace App\Http\Controllers;

use App\Exceptions\CaisseCloturee;
use App\Helpers\SettingsHelper;
use App\Models\ESBTPCashSession;
use App\Services\Caisse\CashSessionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ESBTPCashSessionController extends Controller
{
    /**
     * Coupures en circulation dans l'UEMOA (BCEAO), en FCFA, de la plus grande
     * a la plus petite. Ce n'est pas une realite d'ecole mais une realite de
     * monnaie : deux etablissements ne peuvent pas vouloir des coupures
     * differentes, d'ou une constante et non un reglage. Le reglage optionnel
     * `caisse.coupures` (valeurs separees par des virgules) permet malgre tout
     * a une ecole de retirer les pieces qu'elle ne manipule jamais au guichet.
     *
     * Billets : 10 000, 5 000, 2 000, 1 000, 500. Pieces : 500, 250, 200, 100,
     * 50, 25, 10, 5 (la piece de 500 se compte sur la meme ligne que le billet).
     */
    public const COUPURES_FCFA = [10000, 5000, 2000, 1000, 500, 250, 200, 100, 50, 25, 10, 5];

    public function __construct(private readonly CashSessionService $sessions)
    {
        $this->middleware('auth');
        $this->middleware('permission:cash_session.manage|module.caisse.access');
    }

    public function show(Request $request): View
    {
        $snap = $this->sessions->snapshot($request->user());

        return view('esbtp.caisse.ma-caisse', [
            'session' => $snap['session'],
            'aggregat' => $snap['aggregat'],
            'paiements' => $snap['paiements'],
            'coupures' => $this->coupures(),
        ]);
    }

    public function close(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'counted_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $session = $this->sessions->close(
                $request->user(),
                (float) $validated['counted_amount'],
                $validated['notes'] ?? null
            );
        } catch (CaisseCloturee $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 409);
            }

            return redirect()->route('esbtp.caisse.ma-caisse')->with('error', $e->getMessage());
        }

        $ecart = (float) $session->variance;
        $juste = abs($ecart) < 0.01;
        $msg = $juste
            ? 'Journée clôturée. Caisse juste.'
            : sprintf(
                'Journée clôturée. Écart : %s %s F.',
                $ecart < 0 ? 'manquant' : 'excédent',
                number_format(abs($ecart), 0, ',', ' ')
            );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'session' => $this->resumeSession($session),
                'bordereau_url' => route('esbtp.caisse.bordereau', ['preview' => 1]),
            ]);
        }

        return redirect()->route('esbtp.caisse.ma-caisse')->with($juste ? 'success' : 'warning', $msg);
    }

    public function bordereau(Request $request)
    {
        $snap = $this->sessions->snapshot($request->user());
        $pdf = Pdf::loadView('esbtp.caisse.bordereau-pdf', [
            'session' => $snap['session'],
            'aggregat' => $snap['aggregat'],
            'paiements' => $snap['paiements'],
            'caissier' => $request->user(),
        ])->setPaper('a4', 'portrait');

        $nom = 'bordereau-'.$snap['session']->business_date->format('Y-m-d').'.pdf';

        return $request->boolean('preview')
            ? $pdf->stream($nom)
            : $pdf->download($nom);
    }

    /**
     * @return array<int, int>
     */
    private function coupures(): array
    {
        $reglage = SettingsHelper::get('caisse.coupures');
        $valeurs = is_string($reglage) && trim($reglage) !== ''
            ? array_map('intval', explode(',', $reglage))
            : self::COUPURES_FCFA;
        $valeurs = array_values(array_unique(array_filter($valeurs, fn (int $v) => $v > 0)));
        rsort($valeurs);

        return $valeurs !== [] ? $valeurs : self::COUPURES_FCFA;
    }

    /**
     * @return array<string, mixed>
     */
    private function resumeSession(ESBTPCashSession $session): array
    {
        return [
            'status' => $session->status->value,
            'status_label' => $session->status->label(),
            'counted_amount' => (float) $session->counted_amount,
            'expected_amount' => (float) $session->expected_amount,
            'variance' => (float) $session->variance,
            'closed_at' => $session->closed_at?->format('H:i'),
            'notes' => $session->notes,
        ];
    }
}
