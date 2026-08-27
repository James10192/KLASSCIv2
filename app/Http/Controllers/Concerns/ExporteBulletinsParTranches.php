<?php

namespace App\Http\Controllers\Concerns;

use App\Domain\BtsTroncCommun\BtsClassCohortCounter;
use App\Domain\Bulletins\FiltresBulletins;
use App\Models\ESBTPBulletin;
use App\Services\BulletinBulkPdfExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Export groupé découpé en tranches.
 *
 * Une classe entière ne tient pas dans une requête : sept bulletins consomment
 * déjà trente secondes, mesurées sur esbtp-yakro, pour une limite d'exécution
 * du même ordre. Le découpage reprend le chemin de la génération, qui traite la
 * même classe en douze tranches sans jamais approcher la limite.
 *
 *   1. ouvrir      — fige la liste des bulletins et rend un jeton ;
 *   2. tranche     — rend N bulletins dans le dossier de session ;
 *   3. assembler   — concatène le tout et rend l'adresse du document ;
 *   4. telecharger — sert le document, autant de fois qu'on le demande.
 *
 * L'ordre est porté par le numéro de chaque fichier, pas par l'ordre d'arrivée
 * des tranches : une tranche rejouée ne désordonne pas le document.
 *
 * L'état vit dans le cache, pas dans la session : le tableau des identifiants
 * n'a rien à faire dans une session relue à chaque requête, et le verrou du
 * pilote « file » sérialiserait tout le trafic de l'utilisateur pendant les
 * vingt-cinq secondes de chaque tranche. Le cache apporte en prime l'expiration
 * qui manquait aux exports abandonnés.
 */
trait ExporteBulletinsParTranches
{
    /**
     * Nombre maximum de bulletins dans un seul document.
     *
     * Le découpage protège l'exécution d'une requête, rien d'autre. Sans borne,
     * un export lancé sans choisir de classe part sur tous les bulletins de
     * l'année — plusieurs milliers sur les grosses écoles : autant de requêtes,
     * autant de fichiers sur le disque, et un assemblage qui n'a plus de sens.
     * Une classe entière tient très largement dessous.
     */
    private const PLAFOND_EXPORT = 400;

    /** POST /esbtp/bulletins/export-pdf/ouvrir */
    public function ouvrirExportParTranches(Request $request): JsonResponse
    {
        try {
            $contexte = $this->contexteExport($request);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $jeton = Str::lower(Str::random(24));

        $this->rangerEtatExport($jeton, [
            'user_id' => (int) $request->user()->id,
            'entete' => $contexte['entete'],
            'bulletin_ids' => $contexte['bulletin_ids'],
            'ungenerated_ids' => $contexte['ungenerated_ids'],
            'echecs' => [],
            'rendus' => 0,
            'fichier' => null,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'jeton' => $jeton,
                'total' => count($contexte['bulletin_ids']),
                'absents' => count($contexte['ungenerated_ids']),
                'absents_noms' => $contexte['ungenerated_noms'] ?? [],
                'taille_tranche' => $this->tailleTrancheExport(),
            ],
        ]);
    }

    /** POST /esbtp/bulletins/export-pdf/tranche */
    public function rendreTrancheExport(Request $request, BulletinBulkPdfExporter $exporter): JsonResponse
    {
        $valide = $request->validate([
            'jeton' => 'required|string|max:64',
            'depart' => 'required|integer|min:0',
        ]);

        $etat = $this->etatExport($request, $valide['jeton']);
        if ($etat === null) {
            return $this->exportExpire();
        }

        $total = count($etat['bulletin_ids']);
        $ids = array_slice($etat['bulletin_ids'], $valide['depart'], $this->tailleTrancheExport());

        if ($ids === []) {
            return $this->trancheRendue(0, 0, $total, $total);
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
        $etat['rendus'] += $rendu['rendus'];
        $this->rangerEtatExport($valide['jeton'], $etat);

        return $this->trancheRendue(
            $rendu['rendus'],
            count($rendu['echecs']),
            $valide['depart'] + count($ids),
            $total
        );
    }

    /**
     * POST /esbtp/bulletins/export-pdf/assembler
     *
     * Rend l'adresse du document plutôt que le document : les erreurs de
     * l'assemblage — l'étape la plus coûteuse — reviennent ainsi dans la page
     * au lieu de s'échouer dans un onglet que personne ne regarde.
     */
    public function assemblerExportParTranches(Request $request, BulletinBulkPdfExporter $exporter): JsonResponse
    {
        $valide = $request->validate([
            'jeton' => 'required|string|max:64',
            'mode' => 'nullable|in:apercu,telechargement',
        ]);

        $etat = $this->etatExport($request, $valide['jeton']);
        if ($etat === null) {
            return $this->exportExpire();
        }

        // Déjà assemblé : on ne refait pas cinq minutes de travail.
        if (! is_string($etat['fichier'] ?? null) || ! is_file($etat['fichier'])) {
            $dossier = null;

            try {
                $dossier = $exporter->dossierDeSession($valide['jeton']);

                $absents = ESBTPBulletin::whereIn('id', $etat['ungenerated_ids'])
                    ->with(['etudiant:id,matricule,nom,prenoms', 'classe:id,name'])
                    ->get();

                $garde = fn (array $echecs): ?\Barryvdh\DomPDF\PDF => $this->buildExportCoverPdf(
                    $absents,
                    $echecs,
                    $etat['entete'] ?? [],
                    $etat['rendus'] ?? count($etat['bulletin_ids'])
                );

                $etat['fichier'] = $exporter->assembler($dossier, $garde, $etat['echecs']);
            } catch (\RuntimeException $e) {
                $dossier === null ?: $exporter->oublierLaSession($dossier);
                $this->oublierEtatExport($valide['jeton']);

                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            } catch (\Throwable $e) {
                Log::error("assemblerExportParTranches: échec de l'assemblage — ".$e->getMessage());
                $dossier === null ?: $exporter->oublierLaSession($dossier);
                $this->oublierEtatExport($valide['jeton']);

                return response()->json([
                    'success' => false,
                    'message' => "Échec de l'assemblage du PDF groupé. Réessayez, et prévenez le support si cela persiste.",
                ], 500);
            }

            $dossier === null ?: $exporter->oublierLaSession($dossier);
            $this->rangerEtatExport($valide['jeton'], $etat);

            if ($etat['echecs'] !== []) {
                Log::warning('assemblerExportParTranches: '.count($etat['echecs']).' bulletin(s) non rendus', $etat['echecs']);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'url' => route('esbtp.bulletins.export-pdf.telecharger', [
                    'jeton' => $valide['jeton'],
                    'mode' => $valide['mode'] ?? 'telechargement',
                ]),
                'echecs' => count($etat['echecs']),
            ],
        ]);
    }

    /**
     * GET /esbtp/bulletins/export-pdf/telecharger
     *
     * Idempotent : recharger l'onglet resert le même document au lieu de
     * réclamer cinq minutes de travail refait. Le ménage revient à la purge.
     */
    public function telechargerExportParTranches(Request $request)
    {
        $valide = $request->validate([
            'jeton' => 'required|string|max:64',
            'mode' => 'nullable|in:apercu,telechargement',
        ]);

        $etat = $this->etatExport($request, $valide['jeton']);
        $chemin = $etat['fichier'] ?? null;

        if (! is_string($chemin) || ! is_file($chemin)) {
            return response("Ce document n'est plus disponible. Relancez l'export.", 410)
                ->header('Content-Type', 'text/plain; charset=UTF-8');
        }

        $nom = $this->bulkExportFilename();

        return ($valide['mode'] ?? 'telechargement') === 'apercu'
            ? response()->file($chemin, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$nom.'"',
            ])
            : response()->download($chemin, $nom);
    }

    /**
     * Bulletins à imprimer et bulletins absents, sans plafond : c'est le
     * découpage qui protège l'exécution, plus la taille du lot.
     *
     * @return array{bulletin_ids: array<int, int>, ungenerated_ids: array<int, int>, ungenerated_noms: array<int, string>}
     */
    private function contexteExport(Request $request): array
    {
        // Le même objet que la liste : ce qu'on exporte est ce qu'on voit.
        $filtres = FiltresBulletins::depuis($request);

        $filtre = ESBTPBulletin::query();
        $filtres->appliquerA($filtre);

        $generes = (clone $filtre)->whereNotNull('esbtp_bulletins.moyenne_generale');
        $this->applyBulletinExportOrder($generes, $request);
        $ids = $generes->pluck('esbtp_bulletins.id')->map(fn ($id) => (int) $id)->all();

        if (count($ids) > self::PLAFOND_EXPORT) {
            throw new \RuntimeException(sprintf(
                "%d bulletins correspondent au filtre : c'est trop pour un seul document. Choisissez une classe, ou un semestre.",
                count($ids)
            ));
        }

        if ($ids === []) {
            $total = (clone $filtre)->count();

            throw new \RuntimeException("Aucun bulletin généré parmi les $total filtrés. Générez d'abord les bulletins, puis réessayez.");
        }

        $brouillons = (clone $filtre)
            ->whereNull('esbtp_bulletins.moyenne_generale')
            ->with('etudiant:id,nom,prenoms,matricule')
            ->get(['esbtp_bulletins.id', 'esbtp_bulletins.etudiant_id']);

        // Un brouillon d'étudiant plus dans la classe n'est pas un oubli de
        // génération : la masse ne le verra jamais. Le compter comme « non
        // généré » alarme pour rien (cas DOUKOURE / 1BTS GBAT G).
        if ($filtres->classeId && $filtres->anneeId && $filtres->periode
            && \Illuminate\Support\Facades\Schema::hasTable('esbtp_inscriptions')) {
            $cohorte = array_flip(app(BtsClassCohortCounter::class)->etudiantIdsPourPeriode(
                $filtres->classeId,
                $filtres->anneeId,
                $filtres->periode
            ));
            $brouillons = $brouillons->filter(
                fn (ESBTPBulletin $b) => isset($cohorte[(int) $b->etudiant_id])
            );
        }

        return [
            'entete' => [
                'annee' => optional($filtres->annees->firstWhere('id', $filtres->anneeId))->name,
                'classe' => optional($filtres->classes->firstWhere('id', $filtres->classeId))->name,
                'periode' => $filtres->periode === null ? null : (FiltresBulletins::PERIODES[$filtres->periode] ?? null),
            ],
            'bulletin_ids' => $ids,
            'ungenerated_ids' => $brouillons->pluck('id')->map(fn ($id) => (int) $id)->all(),
            'ungenerated_noms' => $brouillons->map(function (ESBTPBulletin $b) {
                $e = $b->etudiant;

                return trim(($e->nom ?? '').' '.($e->prenoms ?? '')).($e?->matricule ? ' · '.$e->matricule : '');
            })->filter()->values()->all(),
        ];
    }

    /**
     * Nombre de bulletins par tranche.
     *
     * Mesuré sur esbtp-yakro : sept bulletins consomment déjà les trente
     * secondes de la limite d'exécution, six laisse la marge. C'est une
     * constante et non un réglage : aucun écran ne l'expose, et un réglage
     * que personne ne peut changer coûte une lecture par requête pour rien.
     */
    private function tailleTrancheExport(): int
    {
        return 6;
    }

    private function trancheRendue(int $rendus, int $echecs, int $traites, int $total): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'rendus' => $rendus,
                'echecs' => $echecs,
                'traites' => $traites,
                'total' => $total,
                'termine' => $traites >= $total,
            ],
        ]);
    }

    private function exportExpire(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => "Cette session d'export a expiré. Relancez l'export.",
        ], 410);
    }

    /** @return array<string, mixed>|null */
    private function etatExport(Request $request, string $jeton): ?array
    {
        $etat = Cache::get($this->cleExport($jeton));

        // Le jeton est un secret, mais on vérifie quand même le porteur : un
        // export appartient à celui qui l'a ouvert.
        if (! is_array($etat) || ($etat['user_id'] ?? null) !== (int) $request->user()->id) {
            return null;
        }

        return $etat;
    }

    /** @param array<string, mixed> $etat */
    private function rangerEtatExport(string $jeton, array $etat): void
    {
        Cache::put($this->cleExport($jeton), $etat, now()->addMinutes(BulletinBulkPdfExporter::DUREE_VIE_MINUTES));
    }

    private function oublierEtatExport(string $jeton): void
    {
        Cache::forget($this->cleExport($jeton));
    }

    private function cleExport(string $jeton): string
    {
        return 'export_bulletins.'.preg_replace('/[^a-z0-9]/i', '', $jeton);
    }
}
