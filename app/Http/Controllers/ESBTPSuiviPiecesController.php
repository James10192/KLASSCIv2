<?php

namespace App\Http\Controllers;

use App\Domain\Exports\Reports\SuiviPiecesReport;
use App\Exports\SuiviPiecesExport;
use App\Helpers\SettingsHelper;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Services\Dossiers\RelancePiecesService;
use App\Services\Dossiers\SuiviPiecesService;
use App\Services\ExportRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Vue d'ensemble des pieces manquantes : « QUI doit encore fournir QUOI ».
 *
 * Le panneau d'une inscription repond pour un etudiant ; cet ecran repond pour
 * une classe, une filiere ou une promotion, et c'est lui qu'on utilise au moment
 * de constituer les dossiers pour les ministeres.
 */
class ESBTPSuiviPiecesController extends Controller
{
    /** Garde-fous de volume export, configurables par instance. */
    public const CLE_PDF_MAX = 'dossiers.exports.pdf_max_rows';
    public const DEFAUT_PDF_MAX = 1000;
    public const CLE_EXCEL_MAX = 'dossiers.exports.excel_max_rows';
    public const DEFAUT_EXCEL_MAX = 50000;

    /** Taille de page par defaut de l'ecran, configurable par instance. */
    public const CLE_PER_PAGE = 'dossiers.suivi.per_page_default';
    public const DEFAUT_PER_PAGE = 25;

    public function index(Request $request, SuiviPiecesService $service)
    {
        $filtres = $this->filtres($request);
        $resultat = $service->construireLignes($filtres);

        $perPage = $this->perPage($request);
        $page = max(1, (int) $request->input('page', 1));
        $lignesPage = $this->paginer($resultat['lignes'], $perPage, $page, $request);

        if ($request->ajax()) {
            return response()->json([
                'html' => view('esbtp.inscriptions.pieces.partials.resultats', [
                    'lignes' => $lignesPage,
                ])->render(),
                'url' => $request->fullUrl(),
                'kpis' => $resultat['kpis'],
                'par_piece' => $resultat['par_piece'],
                'tronque' => $resultat['tronque'],
            ]);
        }

        return view('esbtp.inscriptions.pieces.suivi', [
            'lignes' => $lignesPage,
            'parPiece' => $resultat['par_piece'],
            'kpis' => $resultat['kpis'],
            'tronque' => $resultat['tronque'],
            'filtres' => $filtres,
            'perPage' => $perPage,
            'annees' => ESBTPAnneeUniversitaire::orderByDesc('start_date')->get(['id', 'name', 'is_current']),
            'filieres' => ESBTPFiliere::orderBy('name')->get(['id', 'name']),
            'niveaux' => ESBTPNiveauEtude::orderBy('name')->get(['id', 'name']),
            'classes' => ESBTPClasse::orderBy('name')->get(['id', 'name']),
            'codesPieces' => $service->codesDisponibles(),
        ]);
    }

    public function previewPdf(Request $request, SuiviPiecesService $service, ExportRenderer $renderer)
    {
        [$report, $erreur] = $this->rapport($request, $service, 'pdf');

        if ($erreur !== null) {
            return $erreur;
        }

        return $renderer->pdfPreview($report);
    }

    public function exportPdf(Request $request, SuiviPiecesService $service, ExportRenderer $renderer)
    {
        [$report, $erreur] = $this->rapport($request, $service, 'pdf');

        if ($erreur !== null) {
            return $erreur;
        }

        return $renderer->pdfDownload($report);
    }

    public function exportExcel(Request $request, SuiviPiecesService $service, ExportRenderer $renderer)
    {
        [$report, $erreur] = $this->rapport($request, $service, 'excel');

        if ($erreur !== null) {
            return $erreur;
        }

        return $renderer->excelDownload($report);
    }

    /**
     * Relance les etudiants selectionnes. On recalcule le manquant cote serveur
     * plutot que de faire confiance au navigateur : entre l'affichage et le clic,
     * le secretariat a pu enregistrer une piece.
     */
    public function relancer(
        Request $request,
        SuiviPiecesService $service,
        RelancePiecesService $relanceService
    ): JsonResponse {
        $valide = $request->validate([
            'inscription_ids' => ['required', 'array', 'min:1'],
            'inscription_ids.*' => ['integer'],
        ]);

        $cibles = array_map('intval', $valide['inscription_ids']);
        $resultat = $service->construireLignes($this->filtres($request));

        $lignes = array_values(array_filter(
            $resultat['lignes'],
            fn (array $ligne) => in_array($ligne['inscription_id'], $cibles, true)
        ));

        if ($lignes === []) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun dossier incomplet parmi les inscriptions selectionnees.',
            ], 422);
        }

        $bilan = $relanceService->relancer($lignes, optional($request->user())->id);

        return response()->json([
            'success' => true,
            'message' => $this->messageRelance($bilan, count($lignes)),
            'bilan' => $bilan,
        ]);
    }

    /**
     * @return array{0: ?SuiviPiecesReport, 1: mixed}
     */
    private function rapport(Request $request, SuiviPiecesService $service, string $format): array
    {
        $filtres = $this->filtres($request);
        $resultat = $service->construireLignes($filtres);

        $nbLignes = $format === 'excel'
            ? count(SuiviPiecesExport::aplatir($resultat['lignes']))
            : count($resultat['lignes']);

        $plafond = $format === 'excel'
            ? (int) SettingsHelper::get(self::CLE_EXCEL_MAX, self::DEFAUT_EXCEL_MAX)
            : (int) SettingsHelper::get(self::CLE_PDF_MAX, self::DEFAUT_PDF_MAX);

        if ($nbLignes > $plafond) {
            return [null, response()->json([
                'success' => false,
                'message' => "Export trop volumineux ({$nbLignes} lignes, maximum {$plafond}). Affinez les filtres.",
            ], 422)];
        }

        return [new SuiviPiecesReport(
            $resultat['lignes'],
            $resultat['par_piece'],
            $resultat['kpis'],
            $this->recapFiltres($filtres)
        ), null];
    }

    /**
     * @return array<string, mixed>
     */
    private function filtres(Request $request): array
    {
        return [
            'annee_id' => $request->input('annee_id'),
            'filiere_id' => $request->input('filiere_id'),
            'niveau_id' => $request->input('niveau_id'),
            'classe_id' => $request->input('classe_id'),
            'piece_code' => (string) $request->input('piece_code', ''),
            'search' => (string) $request->input('search', ''),
            'obligatoires_seulement' => $request->boolean('obligatoires_seulement'),
            'statut_inscription' => (string) $request->input('statut_inscription', ''),
        ];
    }

    /**
     * Recap lisible pour le bandeau du PDF : on affiche les libelles, pas les ids.
     *
     * @return array<string, string>
     */
    private function recapFiltres(array $filtres): array
    {
        $recap = [];

        if (! empty($filtres['annee_id'])) {
            $recap['Année'] = (string) optional(ESBTPAnneeUniversitaire::find($filtres['annee_id']))->name;
        }

        if (! empty($filtres['filiere_id'])) {
            $recap['Filière'] = (string) optional(ESBTPFiliere::find($filtres['filiere_id']))->name;
        }

        if (! empty($filtres['niveau_id'])) {
            $recap['Niveau'] = (string) optional(ESBTPNiveauEtude::find($filtres['niveau_id']))->name;
        }

        if (! empty($filtres['classe_id'])) {
            $recap['Classe'] = (string) optional(ESBTPClasse::find($filtres['classe_id']))->name;
        }

        if (! empty($filtres['piece_code'])) {
            $recap['Pièce'] = (string) $filtres['piece_code'];
        }

        if (! empty($filtres['search'])) {
            $recap['Recherche'] = (string) $filtres['search'];
        }

        if (! empty($filtres['obligatoires_seulement'])) {
            $recap['Portée'] = 'Pièces obligatoires uniquement';
        }

        return array_filter($recap, fn ($valeur) => $valeur !== '');
    }

    private function perPage(Request $request): int
    {
        $defaut = (int) SettingsHelper::get(self::CLE_PER_PAGE, self::DEFAUT_PER_PAGE);
        $perPage = (int) $request->input('per_page', $defaut);

        // Whitelist : une taille de page libre laisse un visiteur demander 100000 lignes.
        return in_array($perPage, [15, 25, 50, 100], true) ? $perPage : 25;
    }

    private function paginer(array $lignes, int $perPage, int $page, Request $request): LengthAwarePaginator
    {
        $total = count($lignes);
        $tranche = array_slice($lignes, ($page - 1) * $perPage, $perPage);

        return new LengthAwarePaginator(
            new Collection($tranche),
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    private function messageRelance(array $bilan, int $demandees): string
    {
        $parties = [$bilan['envoyees'] . ' relance(s) envoyée(s)'];

        if ($bilan['sans_email'] > 0) {
            $parties[] = $bilan['sans_email'] . ' sans adresse email';
        }

        if ($bilan['echecs'] > 0) {
            $parties[] = $bilan['echecs'] . ' en échec';
        }

        if ($demandees > $bilan['plafond']) {
            $parties[] = 'plafond de ' . $bilan['plafond'] . ' destinataires atteint';
        }

        return implode(' — ', $parties) . '.';
    }
}
