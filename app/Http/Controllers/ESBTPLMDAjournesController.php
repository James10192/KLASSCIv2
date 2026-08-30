<?php

namespace App\Http\Controllers;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPLMDJuryDecision;
use App\Models\ESBTPLMDResultatUE;
use Illuminate\Http\Request;

class ESBTPLMDAjournesController extends Controller
{
    public function index(Request $request)
    {
        $annees = ESBTPAnneeUniversitaire::query()->orderByDesc('id')->get();
        $anneeId = (int) $request->input('annee_universitaire_id');
        $annee = $annees->firstWhere('id', $anneeId) ?? ESBTPAnneeUniversitaire::anneeCourante() ?? $annees->first();

        $decisions = ESBTPLMDJuryDecision::query()
            ->with(['jury.classe', 'jury.parcours', 'etudiant'])
            ->where('decision', 'ajourne')
            ->when($annee, fn ($q) => $q->whereHas('jury', fn ($j) => $j->where('annee_universitaire_id', $annee->id)))
            ->orderByDesc('id')
            ->get();

        $etudiantIds = $decisions->pluck('etudiant_id')->filter()->all();
        $naq = $etudiantIds === []
            ? collect()
            : ESBTPLMDResultatUE::query()
                ->with(['uniteEnseignement', 'resultatsECUEs.matiere'])
                ->whereIn('etudiant_id', $etudiantIds)
                ->whereNotIn('statut', [ESBTPLMDResultatUE::STATUT_AQ, ESBTPLMDResultatUE::STATUT_APC])
                ->when($annee, fn ($q) => $q->whereHas('bulletin', fn ($b) => $b->where('annee_universitaire_id', $annee->id)))
                ->get()
                ->groupBy('etudiant_id');

        $lignes = $decisions->map(function (ESBTPLMDJuryDecision $d) use ($naq) {
            $ues = $naq->get($d->etudiant_id, collect())->map(fn (ESBTPLMDResultatUE $ue) => [
                'ue' => trim(($ue->uniteEnseignement?->code ?? '').' — '.($ue->uniteEnseignement?->name ?? '')),
                'statut' => $ue->statut,
                'moyenne' => $ue->moyenne,
                'ie' => $ue->resultatsECUEs
                    ->filter(fn ($e) => (float) $e->moyenne < 10)
                    ->map(fn ($e) => $e->matiere?->code ?? $e->matiere?->name)
                    ->filter()
                    ->values()
                    ->all(),
            ]);

            return [
                'etudiant' => $d->etudiant,
                'jury' => $d->jury,
                'decision' => $d->decision,
                'ues_non_validees' => $ues->values()->all(),
            ];
        });

        $kpis = [
            'total' => $lignes->count(),
            'ues' => $lignes->sum(fn ($l) => count($l['ues_non_validees'])),
            'ie' => $lignes->sum(fn ($l) => collect($l['ues_non_validees'])->sum(fn ($u) => count($u['ie']))),
            'parcours' => $lignes->map(fn ($l) => $l['jury']?->parcours_id)->filter()->unique()->count(),
        ];

        return view('esbtp.lmd.ajournes.index', compact('lignes', 'annee', 'annees', 'kpis'));
    }
}
