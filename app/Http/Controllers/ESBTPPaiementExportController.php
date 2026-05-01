<?php

namespace App\Http\Controllers;

use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPPaiement;
use App\Services\PaiementExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Lot 15 — Export détaillé des paiements (états financiers).
 *
 * Endpoints:
 *  - GET  /esbtp/paiements/export-detaille          → form
 *  - POST /esbtp/paiements/export-detaille/preview  → AJAX count + garde-fou PDF
 *  - POST /esbtp/paiements/export-detaille/generate → téléchargement (PDF/Excel)
 */
class ESBTPPaiementExportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Affiche le formulaire de filtres pour l'export.
     */
    public function index()
    {
        $filieres = ESBTPFiliere::orderBy('name')->get(['id', 'name']);
        $niveaux = ESBTPNiveauEtude::orderBy('name')->get(['id', 'name']);
        $classes = ESBTPClasse::with(['filiere:id,name', 'niveau:id,name'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'filiere_id', 'niveau_etude_id']);

        $modes = $this->availableModes();

        return view('esbtp.paiements.exports.form', compact('filieres', 'niveaux', 'classes', 'modes'));
    }

    /**
     * Preview AJAX — retourne le count + check garde-fou PDF.
     */
    public function preview(Request $request, PaiementExportService $service): JsonResponse
    {
        $filters = $this->validatedFilters($request);

        $count = $service->count($filters, $request->user());

        if ($filters['format'] === 'pdf' && $count > PaiementExportService::PDF_MAX_ROWS) {
            return response()->json([
                'success' => false,
                'count' => $count,
                'limit' => PaiementExportService::PDF_MAX_ROWS,
                'message' => "Trop de lignes pour le PDF ({$count} > "
                    . PaiementExportService::PDF_MAX_ROWS
                    . '). Affinez les filtres ou choisissez le format Excel.',
            ], 422);
        }

        if ($count > PaiementExportService::EXCEL_MAX_ROWS) {
            return response()->json([
                'success' => false,
                'count' => $count,
                'limit' => PaiementExportService::EXCEL_MAX_ROWS,
                'message' => "Volume trop important pour un export ({$count} > "
                    . PaiementExportService::EXCEL_MAX_ROWS
                    . '). Affinez les filtres pour réduire la sélection.',
            ], 422);
        }

        if ($count === 0) {
            return response()->json([
                'success' => false,
                'count' => 0,
                'message' => 'Aucun paiement ne correspond aux filtres sélectionnés.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'count' => $count,
            'message' => "{$count} ligne(s) prête(s) pour l'export.",
        ]);
    }

    /**
     * Aperçu PDF inline (nouvelle tab) — applique les filtres et retourne
     * le PDF visuel sans téléchargement. Permet au comptable de vérifier le
     * rendu avant le bouton "Télécharger".
     */
    public function previewPdf(Request $request, PaiementExportService $service)
    {
        $filters = $this->validatedFilters($request);
        $filters['format'] = 'pdf';
        $count = $service->count($filters, $request->user());

        if ($count === 0) {
            return back()->with('error', 'Aucun paiement à prévisualiser avec ces filtres.');
        }
        if ($count > PaiementExportService::PDF_MAX_ROWS) {
            return back()->with('error', "Trop de lignes pour le PDF ({$count}). Affinez les filtres.");
        }

        try {
            return $service->previewPdf($filters, $request->user());
        } catch (\Throwable $e) {
            Log::error('Erreur aperçu PDF paiements', ['message' => $e->getMessage()]);
            return back()->with('error', 'Erreur lors de l\'aperçu : ' . $e->getMessage());
        }
    }

    /**
     * Génère et télécharge le fichier (PDF ou Excel/CSV).
     */
    public function generate(Request $request, PaiementExportService $service)
    {
        $filters = $this->validatedFilters($request);

        $count = $service->count($filters, $request->user());

        // Garde-fou serveur (au cas où le client bypasse la step preview)
        if ($filters['format'] === 'pdf' && $count > PaiementExportService::PDF_MAX_ROWS) {
            Log::warning('Tentative export PDF au-delà du seuil', [
                'user_id' => $request->user()?->id,
                'count' => $count,
                'limit' => PaiementExportService::PDF_MAX_ROWS,
            ]);
            return back()->with('error', "Trop de lignes pour le PDF ({$count}). Choisissez le format Excel.");
        }

        if ($count > PaiementExportService::EXCEL_MAX_ROWS) {
            return back()->with('error', "Volume trop important pour un export ({$count}). Affinez les filtres.");
        }

        if ($count === 0) {
            return back()->with('error', 'Aucun paiement à exporter avec ces filtres.');
        }

        try {
            return $filters['format'] === 'pdf'
                ? $service->exportPdf($filters, $request->user())
                : $service->exportExcel($filters, $request->user());
        } catch (\Throwable $e) {
            Log::error('Erreur export détaillé paiements', [
                'message' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ]);
            return back()->with('error', 'Erreur lors de la génération de l\'export : ' . $e->getMessage());
        }
    }

    /**
     * Validation et normalisation des filtres.
     */
    private function validatedFilters(Request $request): array
    {
        $rules = [
            'etudiant_id' => 'nullable|integer|exists:esbtp_etudiants,id',
            'classe_ids' => 'nullable|array',
            'classe_ids.*' => 'integer|exists:esbtp_classes,id',
            'filiere_id' => 'nullable|integer|exists:esbtp_filieres,id',
            'niveau_id' => 'nullable|integer|exists:esbtp_niveau_etudes,id',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'modes' => 'nullable|array',
            'modes.*' => 'string|max:50',
            'format' => 'required|in:pdf,excel',
        ];

        $data = $request->validate($rules);

        // Normalisation : forcer arrays
        $data['classe_ids'] = $data['classe_ids'] ?? [];
        $data['modes'] = $data['modes'] ?? [];

        return $data;
    }

    /**
     * Modes de paiement disponibles dans la base
     * (lecture distinct depuis la table — fallback liste fixe).
     */
    private function availableModes(): array
    {
        $modes = ESBTPPaiement::query()
            ->select('mode_paiement')
            ->distinct()
            ->whereNotNull('mode_paiement')
            ->orderBy('mode_paiement')
            ->pluck('mode_paiement')
            ->filter()
            ->values()
            ->toArray();

        if (! empty($modes)) {
            return $modes;
        }

        return [
            'espèces',
            'chèque',
            'virement',
            'mobile money',
            'carte bancaire',
        ];
    }
}
