<?php

namespace App\Http\Controllers;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPPaiement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * S1.3 — Journal de caisse OHADA.
 *
 * Vue chronologique des encaissements (livre des recettes) conforme aux exigences
 * comptables OHADA. Affiche : date paiement, ref, étudiant, catégorie, mode,
 * montant, encaissé par, validé par, statut.
 *
 * Filtres : période, filière, classe, mode de paiement, statut.
 * Export PDF format officiel via x-pdf-document.
 *
 * Permission : `comptabilite.journal.view` (default comptable + superAdmin).
 */
class ESBTPJournalCaisseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:comptabilite.access');
        $this->middleware('permission:comptabilite.journal.view');
    }

    public function index(Request $request)
    {
        [$dateDebut, $dateFin] = $this->resolvePeriod($request);

        $filters = [
            'date_debut' => $dateDebut->format('Y-m-d'),
            'date_fin' => $dateFin->format('Y-m-d'),
            'filiere_id' => $request->input('filiere_id'),
            'classe_id' => $request->input('classe_id'),
            'mode_paiement' => $request->input('mode_paiement'),
            'statut' => $request->input('statut', 'validé'),
        ];

        $paiements = $this->buildQuery($filters)->orderBy('date_paiement')->orderBy('id')->paginate(50)->withQueryString();

        $totals = $this->buildTotals($filters);

        $modes = ['Espèces', 'Chèque', 'Virement', 'Mobile Money', 'Carte bancaire'];
        $filieres = ESBTPFiliere::orderBy('name')->get(['id', 'name']);
        $classes = ESBTPClasse::orderBy('name')->get(['id', 'name', 'filiere_id']);
        $anneeActive = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        return view('esbtp.comptabilite.journal-caisse.index', compact(
            'paiements', 'totals', 'filters', 'modes', 'filieres', 'classes', 'anneeActive', 'dateDebut', 'dateFin'
        ));
    }

    /**
     * Export PDF du journal — format officiel OHADA via x-pdf-document.
     */
    public function exportPdf(Request $request)
    {
        [$dateDebut, $dateFin] = $this->resolvePeriod($request);

        $filters = [
            'date_debut' => $dateDebut->format('Y-m-d'),
            'date_fin' => $dateFin->format('Y-m-d'),
            'filiere_id' => $request->input('filiere_id'),
            'classe_id' => $request->input('classe_id'),
            'mode_paiement' => $request->input('mode_paiement'),
            'statut' => $request->input('statut', 'validé'),
        ];

        // Garde-fou volume (rule exports-pdf-excel.md)
        $count = $this->buildQuery($filters)->count();
        if ($count > 1000) {
            return back()->with('error', sprintf(
                'Trop de lignes (%d) pour l\'export PDF (limite 1000). Affinez la période ou les filtres.',
                $count,
            ));
        }

        $paiements = $this->buildQuery($filters)->orderBy('date_paiement')->orderBy('id')->get();
        $totals = $this->buildTotals($filters);

        $appliedFilters = $this->humanFilters($filters);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('esbtp.comptabilite.journal-caisse.pdf', [
            'paiements' => $paiements,
            'totals' => $totals,
            'filters' => $filters,
            'appliedFilters' => $appliedFilters,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
        ])->setPaper('a4', 'landscape');

        $filename = sprintf(
            'journal-caisse_%s_au_%s.pdf',
            $dateDebut->format('Y-m-d'),
            $dateFin->format('Y-m-d'),
        );

        return $pdf->download($filename);
    }

    /**
     * Aperçu PDF inline (rule exports-pdf-excel.md — toujours offrir preview).
     */
    public function exportPdfPreview(Request $request)
    {
        [$dateDebut, $dateFin] = $this->resolvePeriod($request);

        $filters = [
            'date_debut' => $dateDebut->format('Y-m-d'),
            'date_fin' => $dateFin->format('Y-m-d'),
            'filiere_id' => $request->input('filiere_id'),
            'classe_id' => $request->input('classe_id'),
            'mode_paiement' => $request->input('mode_paiement'),
            'statut' => $request->input('statut', 'validé'),
        ];

        $paiements = $this->buildQuery($filters)->orderBy('date_paiement')->orderBy('id')->limit(1000)->get();
        $totals = $this->buildTotals($filters);
        $appliedFilters = $this->humanFilters($filters);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('esbtp.comptabilite.journal-caisse.pdf', [
            'paiements' => $paiements,
            'totals' => $totals,
            'filters' => $filters,
            'appliedFilters' => $appliedFilters,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
        ])->setPaper('a4', 'landscape');

        $filename = sprintf('apercu-journal-caisse_%s.pdf', now()->format('Y-m-d_His'));

        return new Response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    private function resolvePeriod(Request $request): array
    {
        $dateDebut = $request->filled('date_debut')
            ? Carbon::parse($request->input('date_debut'))->startOfDay()
            : Carbon::now()->startOfMonth();

        $dateFin = $request->filled('date_fin')
            ? Carbon::parse($request->input('date_fin'))->endOfDay()
            : Carbon::now()->endOfDay();

        // Sécurité : si date_debut > date_fin, swap
        if ($dateDebut->gt($dateFin)) {
            [$dateDebut, $dateFin] = [$dateFin->copy()->startOfDay(), $dateDebut->copy()->endOfDay()];
        }

        return [$dateDebut, $dateFin];
    }

    private function buildQuery(array $filters)
    {
        $query = ESBTPPaiement::query()
            ->with([
                'inscription.etudiant:id,nom,prenoms,matricule',
                'inscription.classe:id,name,filiere_id',
                'fraisCategory:id,name',
                'createdBy:id,name',
                'validatedBy:id,name',
            ])
            ->whereNull('deleted_at')
            ->whereDate('date_paiement', '>=', $filters['date_debut'])
            ->whereDate('date_paiement', '<=', $filters['date_fin']);

        if (!empty($filters['statut'])) {
            $query->where('status', $filters['statut']);
        }

        if (!empty($filters['mode_paiement'])) {
            $query->where('mode_paiement', $filters['mode_paiement']);
        }

        if (!empty($filters['classe_id'])) {
            $query->whereHas('inscription', fn ($q) => $q->where('classe_id', $filters['classe_id']));
        } elseif (!empty($filters['filiere_id'])) {
            $query->whereHas('inscription.classe', fn ($q) => $q->where('filiere_id', $filters['filiere_id']));
        }

        return $query;
    }

    private function buildTotals(array $filters): array
    {
        $base = $this->buildQuery($filters);

        $statsByMode = (clone $base)
            ->selectRaw('mode_paiement, COUNT(*) as nb, COALESCE(SUM(montant), 0) as total')
            ->groupBy('mode_paiement')
            ->get()
            ->keyBy('mode_paiement');

        return [
            'count' => (clone $base)->count(),
            'total' => (float) (clone $base)->sum('montant'),
            'by_mode' => $statsByMode->map(fn ($row) => [
                'count' => (int) $row->nb,
                'total' => (float) $row->total,
            ])->all(),
        ];
    }

    private function humanFilters(array $filters): array
    {
        $human = [
            'Période' => sprintf(
                'du %s au %s',
                Carbon::parse($filters['date_debut'])->format('d/m/Y'),
                Carbon::parse($filters['date_fin'])->format('d/m/Y'),
            ),
            'Statut' => ucfirst($filters['statut'] ?? 'validé'),
        ];

        if (!empty($filters['mode_paiement'])) {
            $human['Mode de paiement'] = $filters['mode_paiement'];
        }
        if (!empty($filters['filiere_id'])) {
            $human['Filière'] = optional(ESBTPFiliere::find($filters['filiere_id']))->name ?? '#' . $filters['filiere_id'];
        }
        if (!empty($filters['classe_id'])) {
            $human['Classe'] = optional(ESBTPClasse::find($filters['classe_id']))->name ?? '#' . $filters['classe_id'];
        }

        return $human;
    }
}
