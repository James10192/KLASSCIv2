<?php

namespace App\Http\Controllers;

use App\Services\Caisse\CashSessionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ESBTPCashSessionController extends Controller
{
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
        ]);
    }

    public function close(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'counted_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $session = $this->sessions->close(
            $request->user(),
            (float) $validated['counted_amount'],
            $validated['notes'] ?? null
        );

        $ecart = (float) $session->variance;
        $flash = abs($ecart) < 0.01
            ? 'success'
            : 'warning';
        $msg = abs($ecart) < 0.01
            ? 'Journée clôturée. Caisse juste.'
            : sprintf(
                'Journée clôturée. Écart : %s %s F.',
                $ecart < 0 ? 'manquant' : 'excédent',
                number_format(abs($ecart), 0, ',', ' ')
            );

        return redirect()->route('esbtp.caisse.ma-caisse')->with($flash, $msg);
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
}
