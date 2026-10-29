<?php

namespace App\Http\Controllers\Concerns;

use App\Domain\BtsTroncCommun\BtsClassCohortCounter;
use App\Domain\Bulletins\FiltresBulletins;
use App\Models\ESBTPBulletin;
use App\Domain\Bulletins\Taches\LancementTachesBulletins;
use App\Domain\Bulletins\Taches\SuiviTachesBulletins;
use App\Services\DocumentPrintGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Export groupé : figer la liste, puis laisser le travail se faire en tranches.
 *
 * Une classe entière ne tient pas dans une requête : sept bulletins consomment
 * déjà trente secondes, mesurées sur esbtp-yakro, pour une limite d'exécution
 * du même ordre. Jusqu'ici, c'était l'onglet qui enchaînait les tranches, et le
 * quitter arrêtait l'export. Ce trait ne fait plus que figer la liste des
 * bulletins et créer la tâche ({@see \App\Domain\Bulletins\Taches\BulletinTache}) :
 * l'onglet la fait avancer tant qu'il reste ouvert, la planification la finit
 * sinon, et le demandeur est prévenu à la fin.
 *
 * L'ordre est porté par le numéro de chaque fichier rendu, pas par l'ordre
 * d'arrivée des tranches : une tranche rejouée ne désordonne pas le document.
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

    /**
     * POST /esbtp/bulletins/export-pdf/lancer
     *
     * Fige la liste des bulletins et crée la tâche. Quand des élèves n'ont pas
     * de bulletin, on le dit d'abord : la personne confirme (`confirme=1`)
     * avant de lancer cinq minutes de travail.
     */
    public function lancerExportEnArrierePlan(Request $request, LancementTachesBulletins $lancement): JsonResponse
    {
        $valide = $request->validate([
            'mode' => 'required|in:apercu,telechargement',
            'confirme' => 'sometimes|boolean',
        ]);

        try {
            $contexte = $this->contexteExport($request);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        if ($contexte['ungenerated_ids'] !== [] && ! $request->boolean('confirme')) {
            return response()->json([
                'success' => true,
                'confirmation' => [
                    'total' => count($contexte['bulletin_ids']),
                    'absents' => count($contexte['ungenerated_ids']),
                    'absents_noms' => $contexte['ungenerated_noms'] ?? [],
                ],
            ]);
        }

        $tache = $lancement->export($request->user(), $valide['mode'], $contexte['bulletin_ids'], $contexte);

        return response()->json([
            'success' => true,
            'bloques_solde' => $contexte['bloques_solde'] ?? 0,
            'bloques_approbation' => $contexte['bloques_approbation'] ?? 0,
            'tache' => SuiviTachesBulletins::etat($tache),
        ], 201);
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
        $filtreExport = $ids === []
            ? ['allowed_ids' => [], 'bloques_solde' => 0, 'bloques_approbation' => 0]
            : app(DocumentPrintGuard::class)->filtrerExport(
                $request->user(),
                'bulletin',
                ESBTPBulletin::query()->whereIn('id', $ids)->get(['id', 'etudiant_id'])
            );
        $ids = $filtreExport['allowed_ids'];

        if ($ids === [] && ($filtreExport['bloques_solde'] + $filtreExport['bloques_approbation']) > 0) {
            throw new \RuntimeException(sprintf(
                'Export bloqué : %d bulletin(s) avec solde impayé, %d sans accord de la responsable.',
                $filtreExport['bloques_solde'],
                $filtreExport['bloques_approbation']
            ));
        }

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
            // Pour ramener la notification sur la même liste filtrée.
            'filtres' => array_filter([
                'classe_id' => $filtres->classeId,
                'annee_universitaire_id' => $filtres->anneeId,
                'periode_id' => $filtres->periode,
            ]),
            'bulletin_ids' => $ids,
            'bloques_solde' => $filtreExport['bloques_solde'],
            'bloques_approbation' => $filtreExport['bloques_approbation'],
            'ungenerated_ids' => $brouillons->pluck('id')->map(fn ($id) => (int) $id)->all(),
            'ungenerated_noms' => $brouillons->map(function (ESBTPBulletin $b) {
                $e = $b->etudiant;

                return trim(($e->nom ?? '').' '.($e->prenoms ?? '')).($e?->matricule ? ' · '.$e->matricule : '');
            })->filter()->values()->all(),
        ];
    }
}
