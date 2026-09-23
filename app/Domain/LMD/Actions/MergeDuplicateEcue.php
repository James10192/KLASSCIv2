<?php

namespace App\Domain\LMD\Actions;

use App\Models\ESBTPMatiere;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Fusionne N ECUE (matières) doublons vers une ECUE canonique.
 *
 * Why: après import de maquettes multi-parcours, le même ECUE existe en plusieurs
 * exemplaires distincts. Cette action repointe tous les liens (pivot
 * esbtp_ue_matiere, FK unite_enseignement_id, pivot esbtp_matiere_filiere,
 * planifications esbtp_planifications_academiques) des ECUE absorbés vers la
 * canonique, lie la canonique à TOUTES les UEs concernées, puis soft-delete les
 * absorbés.
 *
 * SÉCURITÉ : refuse de fusionner un ECUE qui porte des évaluations
 * (esbtp_evaluations) ou des notes (esbtp_notes) sans flag `force`. Tout en
 * transaction, idempotent, avec log d'audit. `dry_run` (défaut true) retourne un
 * aperçu d'impact SANS commit.
 *
 * SOUS `force`, LES NOTES CHANGENT DE MATIÈRE PAR UN `update()` DE QUERY
 * BUILDER, qui ne réveille aucun observateur, et `esbtp_resultats` n'est PAS
 * recalculé ici. C'est délibéré, et mesuré : aucun écran LMD ne lit ces lignes
 * (les métriques LMD viennent des bulletins LMD, et tous les appelants de
 * `BtsCurrentResultSnapshotService` écartent les classes LMD). Leur seul
 * lecteur non filtré est le repli du certificat de scolarité
 * (`ESBTPEtudiantController::attachMoyenneCalculee()`), qui moyenne TOUTES les
 * lignes de l'année quand l'inscription n'a pas de bulletin BTS. Un recalcul y
 * créerait la ligne de la canonique à côté de celle de l'absorbée, laissée
 * faute de note : l'ECUE compterait deux fois. Voir
 * `App\Domain\Notes\RecalculApresDeplacement`, qui recense ce déplaceur.
 *
 * L'AGRÉGAT LMD NE SE RECALCULE PAS, IL SE REPORTE. `esbtp_lmd_resultats_ecues`
 * est une ligne de bulletin LMD généré : sa moyenne se reconstruit à la
 * régénération du bulletin. Mais elle porte aussi `note_rattrapage`, qu'aucune
 * note ne permet de reconstruire, et la régénération cherche la ligne par
 * (bulletin, matière) : laissée sur l'ECUE absorbée, cette note de seconde
 * session ne serait plus jamais retrouvée. La fusion la reporte donc sur la
 * canonique, et nomme les bulletins à régénérer.
 */
class MergeDuplicateEcue
{
    /**
     * @param  int    $canonicalId  ECUE qui survit
     * @param  int[]  $absorbedIds  ECUE absorbés (soft-deleted)
     * @param  array{dry_run?:bool, force?:bool}  $options
     * @return array<string, mixed>  aperçu / rapport d'impact
     */
    public function execute(int $canonicalId, array $absorbedIds, array $options = []): array
    {
        $dryRun = (bool) ($options['dry_run'] ?? true);
        $force = (bool) ($options['force'] ?? false);

        $absorbedIds = array_values(array_unique(array_filter(
            array_map('intval', $absorbedIds),
            fn ($id) => $id !== 0 && $id !== $canonicalId
        )));

        if (empty($absorbedIds)) {
            return $this->emptyReport($canonicalId, 'Aucune ECUE à absorber (idempotent).');
        }

        $canonical = ESBTPMatiere::findOrFail($canonicalId);
        $absorbed = ESBTPMatiere::whereIn('id', $absorbedIds)->get();

        if ($absorbed->count() !== count($absorbedIds)) {
            throw new RuntimeException('Une ou plusieurs ECUE à absorber sont introuvables.');
        }

        // ── Garde-fou : évaluations / notes ──
        $blocking = $this->blockingDependencies($absorbedIds);
        if (($blocking['evaluations'] > 0 || $blocking['notes'] > 0) && !$force) {
            return [
                'success' => false,
                'dry_run' => $dryRun,
                'blocked' => true,
                'reason' => 'ECUE absorbées portant des évaluations ou des notes — fusion bloquée sans confirmation explicite (force).',
                'blocking' => $blocking,
                'canonical_id' => $canonicalId,
                'absorbed_ids' => $absorbedIds,
            ];
        }

        $impact = $this->computeImpact($canonicalId, $absorbedIds);
        $impact['blocking'] = $blocking;

        if ($dryRun) {
            return array_merge(['success' => true, 'dry_run' => true, 'committed' => false], $impact);
        }

        $lmd = DB::transaction(fn () => $this->appliquerLaFusion($canonicalId, $absorbed, $absorbedIds, $force));

        return array_merge(['success' => true, 'dry_run' => false, 'committed' => true], $impact, [
            'lmd_resultats_ecues' => $lmd,
        ]);
    }

    /**
     * Le corps de la fusion, exécuté dans la transaction d'`execute()` : tous
     * les repointages, puis la mise de côté des absorbées.
     *
     * @param  Collection<int, ESBTPMatiere>  $absorbed
     * @return array{repointes:int, conflits:list<array<string,mixed>>, bulletins_a_regenerer:list<int>}
     */
    private function appliquerLaFusion(int $canonicalId, $absorbed, array $absorbedIds, bool $force): array
    {
        // 1. Pivot esbtp_ue_matiere : repointer matiere_id absorbé → canonique
        //    (en évitant les collisions sur unique(ue_id, matiere_id)).
        $this->repointUeMatierePivot($canonicalId, $absorbedIds);

        // 2. Planifications académiques : matiere_id absorbé → canonique
        DB::table('esbtp_planifications_academiques')
            ->whereIn('matiere_id', $absorbedIds)
            ->update(['matiere_id' => $canonicalId, 'updated_at' => now()]);

        // 3. Évaluations / Notes (seulement si force) : matiere_id → canonique
        if ($force) {
            DB::table('esbtp_evaluations')
                ->whereIn('matiere_id', $absorbedIds)
                ->update(['matiere_id' => $canonicalId, 'updated_at' => now()]);
            DB::table('esbtp_notes')
                ->whereIn('matiere_id', $absorbedIds)
                ->update(['matiere_id' => $canonicalId, 'updated_at' => now()]);
        }

        // 3 bis. Lignes de bulletin LMD : reportées, jamais recalculées ici.
        $lmd = $this->repointLmdResultatsEcues($canonicalId, $absorbedIds);

        // 4. Pivot esbtp_matiere_filiere : repointer en évitant doublons.
        $this->repointMatiereFilierePivot($canonicalId, $absorbedIds);

        // 5. Soft-delete des absorbés.
        foreach ($absorbed as $ecue) {
            $ecue->delete();
        }

        Log::info('[LMD reconciliation] ECUE merge', [
            'canonical_id' => $canonicalId,
            'absorbed_ids' => $absorbedIds,
            'forced' => $force,
            'lmd_bulletins_a_regenerer' => $lmd['bulletins_a_regenerer'],
            'by' => optional(auth()->user())->id,
        ]);

        return $lmd;
    }

    /**
     * Repointe le pivot esbtp_ue_matiere des ECUE absorbés vers la canonique,
     * sans créer de doublon sur la contrainte unique.
     *
     * Cette contrainte porte sur le TRIPLET (unité, matière, parcours) depuis que
     * la composition d'une unité peut varier d'une maquette à l'autre. Le test
     * d'existence doit donc porter sur le même triplet : n'y mettre que le couple
     * revenait à considérer comme un doublon deux lignes qui n'en sont pas.
     *
     * Concrètement, avec la canonique liée à l'unité en commun (parcours 0) et la
     * ligne absorbée réservée au parcours 3, l'ancien test répondait « déjà liée »
     * et supprimait la réservation. Le parcours 3 perdait son élément, le compte
     * rendu de la fusion n'en disait rien, et aucune erreur n'était levée.
     */
    private function repointUeMatierePivot(int $canonicalId, array $absorbedIds): void
    {
        $rows = DB::table('esbtp_ue_matiere')
            ->whereIn('matiere_id', $absorbedIds)
            ->get();

        foreach ($rows as $row) {
            $portee = (int) ($row->parcours_id ?? 0);

            $existsForCanonical = DB::table('esbtp_ue_matiere')
                ->where('unite_enseignement_id', $row->unite_enseignement_id)
                ->where('matiere_id', $canonicalId)
                ->where('parcours_id', $portee)
                ->exists();

            if ($existsForCanonical) {
                // La canonique occupe déjà cette place, pour CETTE maquette :
                // la ligne absorbée fait bien doublon.
                DB::table('esbtp_ue_matiere')->where('id', $row->id)->delete();
            } else {
                DB::table('esbtp_ue_matiere')->where('id', $row->id)->update([
                    'matiere_id' => $canonicalId,
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reporte les lignes de bulletin LMD de l'ECUE absorbée sur la canonique.
     *
     * Même geste que pour les pivots, sauf en cas de collision : une seconde
     * ligne pour la même matière sur le même bulletin n'est PAS un doublon
     * qu'on peut effacer. Elle porte sa propre moyenne, et peut-être sa propre
     * note de rattrapage ; laquelle garder est une décision d'établissement.
     * Elle reste donc en place, et elle est nommée.
     *
     * `DB::table` et non le modèle : la contrainte unique (bulletin, matière)
     * compte aussi les lignes effacées en douceur, le test d'existence doit
     * donc les voir.
     *
     * @return array{repointes:int, conflits:list<array{id:int, bulletin_id:int, etudiant_id:int, matiere_id:int, moyenne:?float}>, bulletins_a_regenerer:list<int>}
     */
    private function repointLmdResultatsEcues(int $canonicalId, array $absorbedIds): array
    {
        $rows = DB::table('esbtp_lmd_resultats_ecues')
            ->whereIn('matiere_id', $absorbedIds)
            ->orderBy('id')
            ->get(['id', 'bulletin_id', 'etudiant_id', 'matiere_id', 'moyenne']);

        $repointes = 0;
        $conflits = [];

        foreach ($rows as $row) {
            $occupe = DB::table('esbtp_lmd_resultats_ecues')
                ->where('bulletin_id', $row->bulletin_id)
                ->where('matiere_id', $canonicalId)
                ->exists();

            if ($occupe) {
                $conflits[] = [
                    'id' => (int) $row->id,
                    'bulletin_id' => (int) $row->bulletin_id,
                    'etudiant_id' => (int) $row->etudiant_id,
                    'matiere_id' => (int) $row->matiere_id,
                    'moyenne' => $row->moyenne !== null ? (float) $row->moyenne : null,
                ];

                continue;
            }

            DB::table('esbtp_lmd_resultats_ecues')->where('id', $row->id)->update([
                'matiere_id' => $canonicalId,
                'updated_at' => now(),
            ]);
            $repointes++;
        }

        if ($conflits !== []) {
            Log::warning('[LMD reconciliation] Lignes de bulletin LMD laissees sur l ECUE absorbee (collision)', [
                'canonical_id' => $canonicalId,
                'conflits' => $conflits,
            ]);
        }

        return [
            'repointes' => $repointes,
            'conflits' => $conflits,
            'bulletins_a_regenerer' => $rows->pluck('bulletin_id')->map(fn ($id) => (int) $id)->unique()->values()->all(),
        ];
    }

    private function repointMatiereFilierePivot(int $canonicalId, array $absorbedIds): void
    {
        $rows = DB::table('esbtp_matiere_filiere')
            ->whereIn('matiere_id', $absorbedIds)
            ->get();

        foreach ($rows as $row) {
            $exists = DB::table('esbtp_matiere_filiere')
                ->where('filiere_id', $row->filiere_id)
                ->where('matiere_id', $canonicalId)
                ->exists();

            if ($exists) {
                DB::table('esbtp_matiere_filiere')->where('id', $row->id)->delete();
            } else {
                DB::table('esbtp_matiere_filiere')->where('id', $row->id)->update([
                    'matiere_id' => $canonicalId,
                ]);
            }
        }
    }

    /**
     * @return array{evaluations:int, notes:int}
     */
    private function blockingDependencies(array $absorbedIds): array
    {
        return [
            'evaluations' => DB::table('esbtp_evaluations')->whereIn('matiere_id', $absorbedIds)->count(),
            'notes' => DB::table('esbtp_notes')->whereIn('matiere_id', $absorbedIds)->count(),
        ];
    }

    private function computeImpact(int $canonicalId, array $absorbedIds): array
    {
        return [
            'canonical_id' => $canonicalId,
            'absorbed_ids' => $absorbedIds,
            'repointed' => [
                'ue_matiere_links' => DB::table('esbtp_ue_matiere')->whereIn('matiere_id', $absorbedIds)->count(),
                'planifications' => DB::table('esbtp_planifications_academiques')->whereIn('matiere_id', $absorbedIds)->count(),
                'matiere_filiere_links' => DB::table('esbtp_matiere_filiere')->whereIn('matiere_id', $absorbedIds)->count(),
                // Une ligne au plus par bulletin, et aucune là où la canonique
                // figure déjà : les autres entrent en collision et restent en
                // place, nommées. Deux ECUE absorbées sur un même bulletin
                // n'en font donc qu'UNE de reportée.
                'lmd_resultats_ecues' => DB::table('esbtp_lmd_resultats_ecues')
                    ->whereIn('matiere_id', $absorbedIds)
                    ->whereNotIn('bulletin_id', DB::table('esbtp_lmd_resultats_ecues')
                        ->where('matiere_id', $canonicalId)
                        ->select('bulletin_id'))
                    ->distinct()
                    ->count('bulletin_id'),
            ],
            'soft_deleted_count' => count($absorbedIds),
        ];
    }

    private function emptyReport(int $canonicalId, string $message): array
    {
        return [
            'success' => true,
            'committed' => false,
            'dry_run' => true,
            'message' => $message,
            'canonical_id' => $canonicalId,
            'absorbed_ids' => [],
            'repointed' => ['ue_matiere_links' => 0, 'planifications' => 0, 'matiere_filiere_links' => 0, 'lmd_resultats_ecues' => 0],
            'soft_deleted_count' => 0,
            'blocking' => ['evaluations' => 0, 'notes' => 0],
        ];
    }
}
