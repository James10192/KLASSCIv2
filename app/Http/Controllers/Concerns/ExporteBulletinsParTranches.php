<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ESBTPBulletin;
use App\Services\BulletinBulkPdfExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Export groupé découpé en tranches.
 *
 * Un export d'une classe entière ne tient pas dans une requête : sept bulletins
 * consomment déjà trente secondes, mesurées sur esbtp-yakro, pour une limite
 * d'exécution du même ordre. L'export d'un coup est donc plafonné à six.
 *
 * Le découpage reprend le chemin de la génération, qui traite la même classe en
 * douze tranches sans jamais approcher la limite. Trois étapes :
 *
 *   1. `ouvrir`    — fige la liste des bulletins et rend un jeton ;
 *   2. `tranche`   — rend N bulletins dans le dossier de session ;
 *   3. `assembler` — concatène le tout et sert le PDF.
 *
 * L'ordre est porté par le numéro de chaque fichier, pas par l'ordre d'arrivée
 * des tranches : une tranche rejouée ne désordonne pas le document.
 */
trait ExporteBulletinsParTranches
{
    /** Durée de vie d'une session d'export, au-delà de laquelle elle est balayée. */
    private const SESSION_TTL_MINUTES = 60;

    /**
     * POST /esbtp/bulletins/export-pdf/ouvrir
     */
    public function ouvrirExportParTranches(Request $request): JsonResponse
    {
        try {
            $contexte = $this->contexteExport($request);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $jeton = Str::lower(Str::random(24));

        session()->put($this->cleDeSession($jeton), [
            'bulletin_ids' => $contexte['bulletin_ids'],
            'ungenerated_ids' => $contexte['ungenerated_ids'],
            'echecs' => [],
            'ouvert_a' => now()->toIso8601String(),
            'filtres' => $request->only(['classe_id', 'periode', 'annee_universitaire_id']),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'jeton' => $jeton,
                'total' => count($contexte['bulletin_ids']),
                'absents' => count($contexte['ungenerated_ids']),
                'taille_tranche' => $this->tailleTrancheExport(),
            ],
        ]);
    }

    /**
     * POST /esbtp/bulletins/export-pdf/tranche
     */
    public function rendreTrancheExport(Request $request, BulletinBulkPdfExporter $exporter): JsonResponse
    {
        $valide = $request->validate([
            'jeton' => 'required|string|max:64',
            'depart' => 'required|integer|min:0',
        ]);

        $cle = $this->cleDeSession($valide['jeton']);
        $etat = session()->get($cle);

        if (! $etat) {
            return response()->json([
                'success' => false,
                'message' => "Cette session d'export a expiré. Relancez l'export.",
            ], 410);
        }

        $taille = $this->tailleTrancheExport();
        $ids = array_slice($etat['bulletin_ids'], $valide['depart'], $taille);

        if ($ids === []) {
            return response()->json([
                'success' => true,
                'data' => ['rendus' => 0, 'termine' => true],
            ]);
        }

        // whereIn ne garantit pas l'ordre : on le rétablit sur la liste figée.
        $bulletins = ESBTPBulletin::whereIn('id', $ids)->get()
            ->sortBy(fn (ESBTPBulletin $b) => array_search($b->id, $ids, true))
            ->values();

        $rendu = $exporter->rendreTranche(
            $bulletins,
            fn (ESBTPBulletin $b) => $this->buildBulletinPdf($b, false),
            $exporter->dossierDeSession($valide['jeton']),
            $valide['depart']
        );

        $etat['echecs'] = array_merge($etat['echecs'], $rendu['echecs']);
        session()->put($cle, $etat);

        $traites = $valide['depart'] + count($ids);

        return response()->json([
            'success' => true,
            'data' => [
                'rendus' => $rendu['rendus'],
                'echecs' => count($rendu['echecs']),
                'traites' => $traites,
                'total' => count($etat['bulletin_ids']),
                'termine' => $traites >= count($etat['bulletin_ids']),
            ],
        ]);
    }

    /**
     * GET /esbtp/bulletins/export-pdf/assembler
     */
    public function assemblerExportParTranches(Request $request, BulletinBulkPdfExporter $exporter)
    {
        $valide = $request->validate([
            'jeton' => 'required|string|max:64',
            'mode' => 'nullable|in:apercu,telechargement',
        ]);

        $cle = $this->cleDeSession($valide['jeton']);
        $etat = session()->get($cle);

        if (! $etat) {
            return response("Cette session d'export a expiré. Relancez l'export.", 410)
                ->header('Content-Type', 'text/plain; charset=UTF-8');
        }

        $dossier = $exporter->dossierDeSession($valide['jeton']);

        try {
            $absents = ESBTPBulletin::whereIn('id', $etat['ungenerated_ids'])
                ->with(['etudiant:id,matricule,nom,prenoms', 'classe:id,name'])
                ->get();

            $garde = fn (array $echecs): ?\Barryvdh\DomPDF\PDF => $this->buildExportCoverPdf(
                $absents,
                $echecs,
                $request,
                count($etat['bulletin_ids'])
            );

            $chemin = $exporter->assembler($dossier, $garde, $etat['echecs']);
        } catch (\RuntimeException $e) {
            $exporter->oublierLaSession($dossier);
            session()->forget($cle);

            return response($e->getMessage(), 422)->header('Content-Type', 'text/plain; charset=UTF-8');
        } catch (\Throwable $e) {
            Log::error("assemblerExportParTranches: échec de l'assemblage — ".$e->getMessage());
            $exporter->oublierLaSession($dossier);
            session()->forget($cle);

            return response("Échec de l'assemblage du PDF groupé.", 500)
                ->header('Content-Type', 'text/plain; charset=UTF-8');
        }

        if (! empty($etat['echecs'])) {
            Log::warning('assemblerExportParTranches: '.count($etat['echecs']).' bulletin(s) non rendus', $etat['echecs']);
        }

        $exporter->oublierLaSession($dossier);
        session()->forget($cle);

        $nom = $this->bulkExportFilename();

        return ($valide['mode'] ?? 'telechargement') === 'apercu'
            ? response()->file($chemin, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$nom.'"',
            ])->deleteFileAfterSend(true)
            : response()->download($chemin, $nom)->deleteFileAfterSend(true);
    }

    /**
     * Bulletins à imprimer et bulletins absents, sans plafond : c'est le
     * découpage qui protège l'exécution, plus la taille du lot.
     *
     * @return array{bulletin_ids: array<int, int>, ungenerated_ids: array<int, int>}
     */
    private function contexteExport(Request $request): array
    {
        $anneeId = $this->resolveAnneeId($request);

        $filtre = ESBTPBulletin::query();
        $this->applyBulletinFilters($filtre, $request, $anneeId);

        $generes = (clone $filtre)->whereNotNull('esbtp_bulletins.moyenne_generale');
        $this->applyBulletinExportOrder($generes, $request);
        $ids = $generes->pluck('esbtp_bulletins.id')->map(fn ($id) => (int) $id)->all();

        if ($ids === []) {
            $total = (clone $filtre)->count();

            throw new \RuntimeException("Aucun bulletin généré parmi les $total filtrés. Générez d'abord les bulletins, puis réessayez.");
        }

        return [
            'bulletin_ids' => $ids,
            'ungenerated_ids' => (clone $filtre)
                ->whereNull('esbtp_bulletins.moyenne_generale')
                ->pluck('esbtp_bulletins.id')->map(fn ($id) => (int) $id)->all(),
        ];
    }

    /**
     * Même taille que la génération : les deux répondent à la même limite
     * d'exécution, et six bulletins tiennent largement dessous.
     */
    private function tailleTrancheExport(): int
    {
        return max(1, (int) \App\Helpers\SettingsHelper::get('bulletins_bulk_export_cap', 6));
    }

    private function cleDeSession(string $jeton): string
    {
        return 'export_bulletins.'.preg_replace('/[^a-z0-9]/i', '', $jeton);
    }
}
