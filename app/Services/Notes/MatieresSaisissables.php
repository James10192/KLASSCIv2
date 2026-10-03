<?php

namespace App\Services\Notes;

use App\Domain\BtsTroncCommun\BtsBulletinSubjectResolver;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPMatiere;
use App\Services\LMD\MatiereTreeBuilder;
use Illuminate\Support\Collection;

/**
 * Les matières / ECUE proposées à la saisie des notes et aux écrans qui
 * créent une évaluation pour une classe.
 *
 * BTS : maquette canonique du bulletin + matières déjà évaluées ; si aucune
 * source n'existe, repli sur le catalogue BTS historique.
 *
 * LMD : éléments de la maquette de LA classe via MatiereTreeBuilder, la source
 * canonique imposée par `.claude/rules/lmd-bts-matieres-single-source.md`, plus
 * les ECUE déjà évalués dans cette classe/année pour qu'une donnée historique
 * reste corrigeable. Aucun repli vers le catalogue BTS n'est permis.
 */
class MatieresSaisissables
{
    public function __construct(
        private BtsBulletinSubjectResolver $maquette,
        private MatiereTreeBuilder $lmd,
    ) {
    }

    /**
     * @return array{source: string, matieres: array<int, array{id: int, name: string, hors_maquette: bool}>}
     */
    public function pour(ESBTPClasse $classe, ?int $anneeId): array
    {
        if ((string) $classe->systeme_academique === 'LMD') {
            return $this->pourLmd($classe, $anneeId);
        }

        return $this->pourBts($classe, $anneeId);
    }

    private function pourBts(ESBTPClasse $classe, ?int $anneeId): array
    {
        $deLaMaquette = $this->maquette->subjectsForClasse($classe)->keyBy('id');

        $evaluees = ESBTPMatiere::btsOnly()
            ->whereIn('id', $this->evaluationIds($classe, $anneeId))
            ->get()
            ->keyBy('id');

        if ($deLaMaquette->isEmpty() && $evaluees->isEmpty()) {
            return ['source' => 'catalogue', 'matieres' => $this->lignes(
                ESBTPMatiere::btsOnly()->orderBy('name')->get(), collect()
            )];
        }

        $toutes = $deLaMaquette->union($evaluees)->sortBy(fn ($m) => mb_strtolower((string) $m->name))->values();

        return ['source' => 'maquette', 'matieres' => $this->lignes($toutes, $deLaMaquette)];
    }

    private function pourLmd(ESBTPClasse $classe, ?int $anneeId): array
    {
        $deLaMaquette = $this->lmd
            ->loadLmdMatieresForClasse($classe)
            ->keyBy('id');

        $evaluees = ESBTPMatiere::query()
            ->whereNotNull('unite_enseignement_id')
            ->whereIn('id', $this->evaluationIds($classe, $anneeId))
            ->get()
            ->keyBy('id');

        $toutes = $deLaMaquette
            ->union($evaluees)
            ->sortBy(fn ($m) => mb_strtolower((string) $m->name))
            ->values();

        return [
            'source' => $deLaMaquette->isEmpty() ? 'referentiel_absent' : 'maquette_lmd',
            'matieres' => $this->lignes($toutes, $deLaMaquette),
        ];
    }

    private function evaluationIds(ESBTPClasse $classe, ?int $anneeId)
    {
        return ESBTPEvaluation::query()
            ->where('classe_id', $classe->id)
            ->when($anneeId, fn ($q) => $q->where('annee_universitaire_id', $anneeId))
            ->where('status', '!=', ESBTPEvaluation::STATUS_CANCELLED)
            ->select('matiere_id');
    }

    private function lignes(Collection $matieres, Collection $deLaMaquette): array
    {
        return $matieres->map(fn (ESBTPMatiere $m) => [
            'id' => (int) $m->id,
            'name' => $m->name ?: 'Matière sans nom',
            'hors_maquette' => $deLaMaquette->isNotEmpty() && ! $deLaMaquette->has($m->id),
        ])->values()->all();
    }
}
