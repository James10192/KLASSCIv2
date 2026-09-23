<?php

namespace App\Domain\LMD\Actions;

use App\Models\ESBTPEvaluation;
use App\Models\ESBTPMatiere;
use Illuminate\Database\Eloquent\Collection;
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
 * CE QUE LA FUSION NE RECALCULE PAS, ET POURQUOI. Sous `force`, les notes
 * changent de matière par un `update()` qui ne réveille aucun observateur :
 * `esbtp_resultats` n'est donc pas recalculée. C'est voulu. La moyenne d'une
 * ECUE se relit sur les notes (`LMDBulletinService`, par
 * `esbtp_notes.matiere_id`, que la fusion déplace), et aucun écran LMD ne lit
 * `esbtp_resultats`. Le seul lecteur trouvé est le repli sans bulletin de
 * `ESBTPEtudiantController::attachMoyenneCalculee()`, qui additionne toutes les
 * lignes cohérentes d'un élève — et une ECUE l'est dans sa classe LMD, même
 * absorbée (la matière est relue `withTrashed()`).
 *
 * LES MOYENNES ENREGISTRÉES SE REPORTENT, SANS RECALCUL. Laissées sur
 * l'absorbée, elles ne compteraient chaque note qu'une fois… jusqu'au premier
 * enregistrement d'une note sur la canonique : l'observateur y recalcule alors
 * la ligne depuis TOUTES les notes, absorbées comprises, et le certificat les
 * verrait deux fois. Reportées sur la canonique, il n'y a plus qu'une ligne par
 * (élève, classe, année, période), que le prochain recalcul réécrit en entier.
 * La valeur reportée n'est pas recalculée ici : elle reste celle d'avant, jusqu'à
 * ce recalcul. Si la canonique a déjà sa propre ligne sur la même coordonnée,
 * celle de l'absorbée reste en place et est nommée : fusionner deux moyennes,
 * peut-être saisies à la main, n'est pas une décision de code. Tant qu'elle
 * n'est pas tranchée, le certificat compte les deux ; l'écran de fusion offre
 * de la trancher sur place ({@see RetirerMoyennesEnCollision}).
 *
 * L'AGRÉGAT LMD NE SE RECALCULE PAS, IL SE REPORTE. `esbtp_lmd_resultats_ecues`
 * est une ligne de bulletin LMD généré : sa moyenne se reconstruit à la
 * régénération du bulletin. Mais elle porte aussi `note_rattrapage`, qu'aucune
 * note ne permet de reconstruire, et la régénération cherche la ligne par
 * (bulletin, matière) : laissée sur l'ECUE absorbée, cette note de seconde
 * session ne serait plus jamais retrouvée. La fusion la reporte donc sur la
 * canonique, et décrit les bulletins à régénérer — l'écran de réconciliation
 * les affiche avec un lien vers chacun.
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

        $impact = $this->computeImpact($canonicalId, $absorbedIds);
        $impact['blocking'] = $this->blockingDependencies($absorbedIds);

        // ── Garde-fou : évaluations / notes ──
        // L'impact accompagne le refus : c'est sur lui que se décide « Forcer ».
        if (($impact['blocking']['evaluations'] > 0 || $impact['blocking']['notes'] > 0) && ! $force) {
            return array_merge($impact, [
                'success' => false,
                'dry_run' => $dryRun,
                'blocked' => true,
                'reason' => 'ECUE absorbées portant des évaluations ou des notes — fusion bloquée sans confirmation explicite (force).',
            ]);
        }

        if ($dryRun) {
            return array_merge(['success' => true, 'dry_run' => true, 'committed' => false], $impact);
        }

        [$lmd, $moyennes] = DB::transaction(fn () => $this->appliquer($canonicalId, $absorbed, $absorbedIds, $force));

        return array_merge(['success' => true, 'dry_run' => false, 'committed' => true], $impact, [
            'lmd_resultats_ecues' => $lmd,
            'moyennes_enregistrees' => $moyennes,
        ]);
    }

    /**
     * Les écritures de la fusion, dans l'ordre, sous la transaction d'execute().
     *
     * @return array{0: array{repointes:int, conflits:list<array>, bulletins_a_regenerer:list<array>}, 1: array{repointees:int, conflits:list<array>}}
     */
    private function appliquer(int $canonicalId, Collection $absorbed, array $absorbedIds, bool $force): array
    {
        // 1. Pivot esbtp_ue_matiere : repointer matiere_id absorbé → canonique
        //    (en évitant les collisions sur unique(ue_id, matiere_id)).
        $this->repointUeMatierePivot($canonicalId, $absorbedIds);

        // 2. Planifications académiques : matiere_id absorbé → canonique
        DB::table('esbtp_planifications_academiques')
            ->whereIn('matiere_id', $absorbedIds)
            ->update(['matiere_id' => $canonicalId, 'updated_at' => now()]);

        // 3. Évaluations / Notes / moyennes enregistrées (seulement si force) :
        //    matiere_id → canonique. Pas de recalcul d'esbtp_resultats : voir l'en-tête.
        $moyennes = ['repointees' => 0, 'conflits' => []];
        if ($force) {
            DB::table('esbtp_evaluations')
                ->whereIn('matiere_id', $absorbedIds)
                ->update(['matiere_id' => $canonicalId, 'updated_at' => now()]);
            DB::table('esbtp_notes')
                ->whereIn('matiere_id', $absorbedIds)
                ->update(['matiere_id' => $canonicalId, 'updated_at' => now()]);
            $moyennes = $this->repointResultats($canonicalId, $absorbedIds);
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
            'by' => optional(auth()->user())->id,
        ]);

        return [$lmd, $moyennes];
    }

    /**
     * Reporte les moyennes enregistrées (`esbtp_resultats`) de l'absorbée sur la
     * canonique — voir l'en-tête pour le pourquoi.
     *
     * Seules les lignes vivantes : la clé unique porte aussi `deleted_at`, et une
     * ligne effacée en douceur ou archivée n'est lue par personne. Une
     * coordonnée où la canonique a déjà sa ligne vivante est une collision,
     * nommée et laissée ; {@see RetirerMoyennesEnCollision} la règle.
     *
     * `DB::table` et non le modèle : le garde de cohérence de `ESBTPResultat`
     * n'a rien à dire ici (une ECUE remplace une ECUE, dans la même classe), et
     * l'audit ligne à ligne n'apporterait rien que le journal ne dise déjà.
     *
     * @return array{repointees:int, conflits:list<array{id:int, etudiant_id:int, classe_id:int, annee_universitaire_id:int, periode:string, moyenne:?float}>}
     */
    private function repointResultats(int $canonicalId, array $absorbedIds): array
    {
        $rows = DB::table('esbtp_resultats')
            ->whereIn('matiere_id', $absorbedIds)
            ->whereNull('deleted_at')
            ->whereNull('archived_at')
            ->orderBy('id')
            ->get(['id', 'etudiant_id', 'classe_id', 'annee_universitaire_id', 'periode', 'moyenne']);

        $repointees = 0;
        $conflits = [];

        foreach ($rows as $row) {
            $occupe = self::ligneDeLaCanonique($canonicalId, $row)->exists();

            if ($occupe) {
                $conflits[] = [
                    'id' => (int) $row->id,
                    'etudiant_id' => (int) $row->etudiant_id,
                    'classe_id' => (int) $row->classe_id,
                    'annee_universitaire_id' => (int) $row->annee_universitaire_id,
                    'periode' => (string) $row->periode,
                    'moyenne' => $row->moyenne !== null ? (float) $row->moyenne : null,
                ];

                continue;
            }

            DB::table('esbtp_resultats')->where('id', $row->id)->update([
                'matiere_id' => $canonicalId,
                'updated_at' => now(),
            ]);
            $repointees++;
        }

        if ($conflits !== []) {
            Log::warning('[LMD reconciliation] Moyennes enregistrees en collision apres fusion d ECUE', [
                'canonical_id' => $canonicalId,
                'conflits' => $conflits,
            ]);
        }

        return ['repointees' => $repointees, 'conflits' => $conflits];
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
     * La ligne vivante de la canonique sur la coordonnée d'une ligne donnée.
     *
     * La période s'écrit de deux façons en base (« 1 » ou « semestre1 ») : une
     * égalité stricte laisserait passer une collision réelle, et deux lignes
     * vivantes pour la même coordonnée seraient lues toutes les deux.
     */
    public static function ligneDeLaCanonique(int $canonicalId, object $row): \Illuminate\Database\Query\Builder
    {
        return DB::table('esbtp_resultats')
            ->where('etudiant_id', $row->etudiant_id)
            ->where('classe_id', $row->classe_id)
            ->where('annee_universitaire_id', $row->annee_universitaire_id)
            ->whereIn('periode', ESBTPEvaluation::aliasDePeriode((string) $row->periode))
            ->where('matiere_id', $canonicalId)
            ->whereNull('deleted_at')
            ->whereNull('archived_at');
    }

    /**
     * La ligne de bulletin LMD de la canonique sur le bulletin d'une ligne donnée.
     */
    private static function ligneLmdDeLaCanonique(int $canonicalId, object $row): \Illuminate\Database\Query\Builder
    {
        return DB::table('esbtp_lmd_resultats_ecues')
            ->where('bulletin_id', $row->bulletin_id)
            ->where('matiere_id', $canonicalId);
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
     * @return array{repointes:int, conflits:list<array{id:int, bulletin_id:int, etudiant_id:int, matiere_id:int, moyenne:?float}>, bulletins_a_regenerer:list<array<string, mixed>>}
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
            $occupe = self::ligneLmdDeLaCanonique($canonicalId, $row)->exists();

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

        $bulletins = $this->decrireBulletins($rows->pluck('bulletin_id')->unique()->all());

        // `warning` et non `info` : un journal de production peut filtrer
        // `info`, et ces bulletins affichent une moyenne périmée tant qu'on ne
        // les régénère pas.
        if ($bulletins !== []) {
            Log::warning('[LMD reconciliation] Bulletins LMD a regenerer apres fusion d ECUE', [
                'canonical_id' => $canonicalId,
                'bulletins' => array_column($bulletins, 'id'),
                'conflits' => $conflits,
            ]);
        }

        return ['repointes' => $repointes, 'conflits' => $conflits, 'bulletins_a_regenerer' => $bulletins];
    }

    /**
     * De quoi retrouver chaque bulletin sur l'écran des bulletins LMD, qui se
     * filtre par classe, année, semestre et matricule.
     *
     * @return list<array{id:int, etudiant:string, matricule:?string, classe:?string, classe_id:int, annee_universitaire_id:int, semestre:int}>
     */
    private function decrireBulletins(array $bulletinIds): array
    {
        if ($bulletinIds === []) {
            return [];
        }

        return DB::table('esbtp_lmd_bulletins as b')
            ->leftJoin('esbtp_etudiants as e', 'e.id', '=', 'b.etudiant_id')
            ->leftJoin('esbtp_classes as c', 'c.id', '=', 'b.classe_id')
            ->whereIn('b.id', $bulletinIds)
            ->whereNull('b.deleted_at')
            ->orderBy('c.name')->orderBy('e.nom')
            ->get(['b.id', 'b.classe_id', 'b.annee_universitaire_id', 'b.semestre', 'e.nom', 'e.prenoms', 'e.matricule', 'c.name as classe'])
            ->map(fn ($b) => [
                'id' => (int) $b->id,
                'etudiant' => trim(($b->nom ?? '').' '.($b->prenoms ?? '')),
                'matricule' => $b->matricule,
                'classe' => $b->classe,
                'classe_id' => (int) $b->classe_id,
                'annee_universitaire_id' => (int) $b->annee_universitaire_id,
                'semestre' => (int) $b->semestre,
            ])
            ->all();
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
                'lmd_resultats_ecues' => $this->compterLesReports(
                    DB::table('esbtp_lmd_resultats_ecues')->whereIn('matiere_id', $absorbedIds)->orderBy('id')->get(['bulletin_id']),
                    fn ($row) => (string) $row->bulletin_id,
                    fn ($row) => self::ligneLmdDeLaCanonique($canonicalId, $row)->exists(),
                ),
                // Reportées seulement avec `force`, comme les notes.
                'moyennes_enregistrees' => $this->compterLesReports(
                    DB::table('esbtp_resultats')->whereIn('matiere_id', $absorbedIds)->whereNull('deleted_at')->whereNull('archived_at')
                        ->orderBy('id')->get(['etudiant_id', 'classe_id', 'annee_universitaire_id', 'periode']),
                    fn ($row) => implode('|', [$row->etudiant_id, $row->classe_id, $row->annee_universitaire_id, ESBTPEvaluation::aliasDePeriode((string) $row->periode)[0]]),
                    fn ($row) => self::ligneDeLaCanonique($canonicalId, $row)->exists(),
                ),
            ],
            'soft_deleted_count' => count($absorbedIds),
        ];
    }

    /**
     * Ce que la fusion reportera vraiment, et rien de plus : « Forcer » se
     * coche sur ce chiffre.
     *
     * Une ligne n'est pas reportée quand la canonique a déjà la sienne à cette
     * coordonnée (le critère même de la fusion), ni quand une autre absorbée
     * vient d'y être reportée avant elle — la fusion la trouve alors occupée.
     *
     * @param  iterable<object>  $rows  lignes des absorbées, dans l'ordre où la fusion les traite
     */
    private function compterLesReports(iterable $rows, callable $coordonnee, callable $occupeeParLaCanonique): int
    {
        $prises = [];

        foreach ($rows as $row) {
            $cle = $coordonnee($row);

            if (! isset($prises[$cle]) && ! $occupeeParLaCanonique($row)) {
                $prises[$cle] = true;
            }
        }

        return count($prises);
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
            'repointed' => ['ue_matiere_links' => 0, 'planifications' => 0, 'matiere_filiere_links' => 0, 'lmd_resultats_ecues' => 0, 'moyennes_enregistrees' => 0],
            'soft_deleted_count' => 0,
            'blocking' => ['evaluations' => 0, 'notes' => 0],
        ];
    }
}
