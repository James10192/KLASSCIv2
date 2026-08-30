<?php

namespace App\Http\Controllers;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPLMDJuryDecision;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPLMDResultatUE;
use Illuminate\Http\Request;

class ESBTPLMDAjournesController extends Controller
{
    public function index(Request $request)
    {
        $annees = ESBTPAnneeUniversitaire::query()->orderByDesc('id')->get();
        $anneeId = (int) $request->input('annee_universitaire_id');
        $annee = $annees->firstWhere('id', $anneeId) ?? ESBTPAnneeUniversitaire::anneeCourante() ?? $annees->first();

        $classeId = (int) $request->input('classe_id');
        $parcoursId = (int) $request->input('parcours_id');
        $semestre = (int) $request->input('semestre');
        $q = trim((string) $request->input('q'));

        $decisions = ESBTPLMDJuryDecision::query()
            ->with(['jury.classe', 'jury.parcours', 'etudiant'])
            ->where('decision', 'ajourne')
            ->when($annee, fn ($qry) => $qry->whereHas('jury', fn ($j) => $j->where('annee_universitaire_id', $annee->id)))
            ->when($classeId, fn ($qry) => $qry->whereHas('jury', fn ($j) => $j->where('classe_id', $classeId)))
            ->when($parcoursId, fn ($qry) => $qry->whereHas('jury', fn ($j) => $j->where('parcours_id', $parcoursId)))
            ->when($semestre, fn ($qry) => $qry->whereHas('jury', fn ($j) => $j->where('semestre', $semestre)))
            ->when($q !== '', function ($qry) use ($q) {
                $qry->whereHas('etudiant', function ($e) use ($q) {
                    $e->where('nom', 'like', '%'.$q.'%')
                        ->orWhere('prenoms', 'like', '%'.$q.'%')
                        ->orWhere('matricule', 'like', '%'.$q.'%');
                });
            })
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

        $classes = ESBTPClasse::query()->orderBy('name')->get(['id', 'name']);
        $parcours = ESBTPLMDParcours::query()->orderBy('name')->get(['id', 'name']);

        return view('esbtp.lmd.ajournes.index', compact('lignes', 'annee', 'annees', 'kpis', 'classes', 'parcours'));
    }
}
