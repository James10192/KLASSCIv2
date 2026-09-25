<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Personnel\ActiviteDuPersonnel;
use App\Services\Personnel\FenetreDActivite;
use App\Services\Personnel\PreuvesDActivite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * L'activité du personnel : des faits prévus / réalisés, jamais une note.
 *
 * `performance.view_all` ouvre la liste de tout le personnel ; `performance.view`
 * ouvre sa propre activité (« Mon activité »), que personne ne pouvait voir
 * jusqu'ici alors que la permission existait.
 */
class ESBTPPersonnelPerformanceController extends Controller
{
    public function __construct(
        private readonly ActiviteDuPersonnel $activite,
        private readonly PreuvesDActivite $preuves,
    ) {}

    public function index(Request $request): View
    {
        return view('esbtp.personnel.performance.index', [
            'periodes' => FenetreDActivite::PERIODES,
            'periode' => FenetreDActivite::pour($request->get('periode'))->periode,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $fenetre = FenetreDActivite::pour($request->get('periode'));
        $lignes = $this->activite->lignes($fenetre);

        return response()->json([
            'periode' => $fenetre->periode,
            'html' => view('esbtp.personnel.performance.partials._liste', [
                'fenetre' => $fenetre,
                'lignes' => $lignes,
                'synthese' => $this->activite->synthese($lignes),
                'tauxPrecedent' => $this->activite->tauxPrecedent($fenetre),
                'seuilRelance' => app(\App\Domain\AcademicPilotage\Services\SeuilsDePilotage::class)->relanceApresJours(),
            ])->render(),
        ]);
    }

    public function show(Request $request, User $user): View
    {
        $estSoi = (int) $user->id === (int) $request->user()->id;
        abort_unless($request->user()->can('performance.view_all') || ($estSoi && $request->user()->can('performance.view')), 403);

        $fenetre = FenetreDActivite::pour($request->get('periode'));

        return view('esbtp.personnel.performance.show', [
            'personne' => $user,
            'estSoi' => $estSoi,
            'fenetre' => $fenetre,
            'periodes' => FenetreDActivite::PERIODES,
            'ligne' => $this->activite->lignes($fenetre, (int) $user->id)->first(),
            'preuves' => $this->preuves->pour((int) $user->id, $fenetre),
            'attenteJours' => $this->activite->attenteJours(),
        ]);
    }

    public function moi(Request $request): RedirectResponse
    {
        return redirect()->route('esbtp.personnel.performance.show', array_filter([
            'user' => $request->user()->id,
            'periode' => $request->get('periode'),
        ]));
    }
}
