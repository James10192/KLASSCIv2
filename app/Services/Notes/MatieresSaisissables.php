<?php

namespace App\Services\Notes;

use App\Domain\BtsTroncCommun\BtsBulletinSubjectResolver;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPMatiere;
use Illuminate\Support\Collection;

/**
 * Les matières proposées à la saisie des notes pour une classe BTS.
 *
 * La maquette de la classe (source canonique, tronc commun compris), plus
 * les matières déjà évaluées dans la classe cette année : une évaluation
 * créée hors maquette doit rester atteignable, sinon ses notes deviennent
 * impossibles à corriger. Sans maquette ni évaluation, le catalogue BTS
 * entier est rendu, marqué comme tel : mieux vaut une liste longue qu'une
 * saisie impossible.
 */
class MatieresSaisissables
{
    public function __construct(private BtsBulletinSubjectResolver $maquette)
    {
    }

    /**
     * @return array{source: string, matieres: array<int, array{id: int, name: string, hors_maquette: bool}>}
     */
    public function pour(ESBTPClasse $classe, ?int $anneeId): array
    {
        $deLaMaquette = $this->maquette->subjectsForClasse($classe)->keyBy('id');

        $evaluees = ESBTPMatiere::btsOnly()
            ->whereIn('id', ESBTPEvaluation::query()
                ->where('classe_id', $classe->id)
                ->when($anneeId, fn ($q) => $q->where('annee_universitaire_id', $anneeId))
                ->select('matiere_id'))
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

    private function lignes(Collection $matieres, Collection $deLaMaquette): array
    {
        return $matieres->map(fn (ESBTPMatiere $m) => [
            'id' => (int) $m->id,
            'name' => $m->name ?: 'Matière sans nom',
            'hors_maquette' => $deLaMaquette->isNotEmpty() && ! $deLaMaquette->has($m->id),
        ])->values()->all();
    }
}
