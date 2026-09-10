<?php

declare(strict_types=1);

namespace App\Domain\BtsTroncCommun;

use App\Models\ESBTPMatiereFilierNiveau;
use App\Models\ESBTPPlanificationAcademique;
use Illuminate\Support\Facades\DB;

/**
 * Alimente la maquette BTS a partir du planning general.
 *
 * Le planning est ANNUEL, la maquette ne l'est pas : l'annee choisie ici ne
 * sert qu'a designer le planning source. Ce que l'import ecrit vaut ensuite
 * pour toutes les annees, puisque la maquette est un referentiel de cursus.
 * C'est pourquoi l'import est un geste explicite, jamais automatique.
 *
 * L'import NE CREE AUCUNE LIAISON matiere <-> combo : il renseigne le semestre
 * de celles qui existent deja. Creer une liaison ferait apparaitre une matiere
 * de plus sur le bulletin, ce qui deborde « renseigner des semestres ». Les
 * matieres planifiees mais absentes de la maquette sont signalees, pas ecrites.
 */
final class PlanningToMaquetteImporter
{
    /** Semestres qu'une maquette BTS sait representer. */
    private const SEMESTRES_ADMIS = [1, 2];

    /**
     * Ce que l'import changerait, sans rien ecrire.
     *
     * @return array{
     *     lignes: list<array<string, mixed>>,
     *     hors_maquette: list<array<string, mixed>>,
     *     planning_lignes: int,
     *     changements: int,
     *     empreinte: string
     * }
     */
    public function diff(int $filiereId, int $niveauId, int $anneeId): array
    {
        $planning = $this->semestresDuPlanning($filiereId, $niveauId, $anneeId);
        $maquette = $this->lignesDeMaquette($filiereId, $niveauId);

        $lignes = [];
        $changements = 0;

        foreach ($maquette as $matiereId => $ligne) {
            $propose = array_key_exists($matiereId, $planning)
                ? $this->semestrePropose($planning[$matiereId])
                : $ligne['semestre'];

            $change = $propose !== $ligne['semestre'] || ! $ligne['semestre_renseigne'];

            if ($change) {
                $changements++;
            }

            $lignes[] = [
                'matiere_id' => $matiereId,
                'name' => $ligne['name'],
                'actuel' => $ligne['semestre'],
                'actuel_renseigne' => $ligne['semestre_renseigne'],
                'propose' => $propose,
                'dans_le_planning' => array_key_exists($matiereId, $planning),
                'change' => $change,
            ];
        }

        return [
            'lignes' => $lignes,
            'hors_maquette' => $this->matieresHorsMaquette($planning, $maquette),
            'planning_lignes' => array_sum(array_map('count', $planning)),
            'changements' => $changements,
            'empreinte' => $this->empreinte($planning, $maquette),
        ];
    }

    /**
     * Applique l'import.
     *
     * `$empreinteAttendue` est celle rendue par `diff()`. Si le planning ou la
     * maquette a bouge entretemps, on refuse : l'utilisateur validerait un
     * apercu perime, et ecraserait en silence le travail d'un autre.
     *
     * @throws \RuntimeException quand la source a change depuis l'apercu
     */
    public function appliquer(int $filiereId, int $niveauId, int $anneeId, ?string $empreinteAttendue): array
    {
        $diff = $this->diff($filiereId, $niveauId, $anneeId);

        // Pas d'empreinte, pas d'application. Une garde qu'on peut sauter en
        // n'envoyant pas le champ ne garde rien.
        if ($empreinteAttendue === null || $empreinteAttendue !== $diff['empreinte']) {
            throw new \RuntimeException(
                'Le planning ou la maquette a change depuis l\'apercu. Relancez l\'apercu avant d\'appliquer.'
            );
        }

        DB::transaction(function () use ($diff, $filiereId, $niveauId) {
            foreach ($diff['lignes'] as $ligne) {
                ESBTPMatiereFilierNiveau::query()
                    ->where('filiere_id', $filiereId)
                    ->where('niveau_etude_id', $niveauId)
                    ->where('matiere_id', $ligne['matiere_id'])
                    ->update([
                        'semestre' => $ligne['propose'],
                        // Valider le combo, meme quand rien ne change de semestre :
                        // « toutes les matieres aux deux semestres » est une reponse,
                        // pas une absence de reponse.
                        'semestre_renseigne' => true,
                    ]);
            }
        });

        return $diff;
    }

    /**
     * Semestres declares par le planning, par matiere.
     *
     * @return array<int, list<int>>
     */
    private function semestresDuPlanning(int $filiereId, int $niveauId, int $anneeId): array
    {
        $lignes = ESBTPPlanificationAcademique::query()
            ->where('annee_universitaire_id', $anneeId)
            ->where('filiere_id', $filiereId)
            ->where('niveau_etude_id', $niveauId)
            ->where('is_active', true)
            ->whereIn('semestre', self::SEMESTRES_ADMIS)
            ->get(['matiere_id', 'semestre']);

        $parMatiere = [];

        foreach ($lignes as $ligne) {
            $matiereId = (int) $ligne->matiere_id;
            $semestre = (int) $ligne->semestre;

            if (! in_array($semestre, $parMatiere[$matiereId] ?? [], true)) {
                $parMatiere[$matiereId][] = $semestre;
            }
        }

        foreach ($parMatiere as &$semestres) {
            sort($semestres);
        }

        return $parMatiere;
    }

    /**
     * Etat courant de la maquette du combo.
     *
     * @return array<int, array{name: string, semestre: int|null, semestre_renseigne: bool}>
     */
    private function lignesDeMaquette(int $filiereId, int $niveauId): array
    {
        $lignes = ESBTPMatiereFilierNiveau::query()
            ->with('matiere:id,name')
            ->where('filiere_id', $filiereId)
            ->where('niveau_etude_id', $niveauId)
            ->get(['matiere_id', 'semestre', 'semestre_renseigne']);

        $maquette = [];

        foreach ($lignes as $ligne) {
            $maquette[(int) $ligne->matiere_id] = [
                'name' => (string) ($ligne->matiere->name ?? 'Matiere #'.$ligne->matiere_id),
                'semestre' => $ligne->semestre === null ? null : (int) $ligne->semestre,
                'semestre_renseigne' => (bool) $ligne->semestre_renseigne,
            ];
        }

        return $maquette;
    }

    /**
     * Prevue aux deux semestres -> null. Prevue a un seul -> ce semestre.
     *
     * @param  list<int>  $semestres
     */
    private function semestrePropose(array $semestres): ?int
    {
        return count($semestres) === 1 ? $semestres[0] : null;
    }

    /**
     * Matieres que le planning connait et que la maquette ignore.
     *
     * @param  array<int, list<int>>  $planning
     * @param  array<int, array<string, mixed>>  $maquette
     * @return list<array<string, mixed>>
     */
    private function matieresHorsMaquette(array $planning, array $maquette): array
    {
        $absentes = array_diff_key($planning, $maquette);

        if ($absentes === []) {
            return [];
        }

        $noms = \App\Models\ESBTPMatiere::query()
            ->whereIn('id', array_keys($absentes))
            ->pluck('name', 'id');

        $hors = [];

        foreach ($absentes as $matiereId => $semestres) {
            $hors[] = [
                'matiere_id' => $matiereId,
                'name' => (string) ($noms[$matiereId] ?? 'Matiere #'.$matiereId),
                'propose' => $this->semestrePropose($semestres),
            ];
        }

        return $hors;
    }

    /**
     * Empreinte des donnees sources : elle change des que le planning ou la
     * maquette bouge, ce qui permet de refuser un apercu perime.
     *
     * @param  array<int, list<int>>  $planning
     * @param  array<int, array<string, mixed>>  $maquette
     */
    private function empreinte(array $planning, array $maquette): string
    {
        ksort($planning);
        ksort($maquette);

        $etat = [];

        foreach ($maquette as $matiereId => $ligne) {
            $etat[] = $matiereId.':'.($ligne['semestre'] ?? '-').':'.($ligne['semestre_renseigne'] ? '1' : '0');
        }

        foreach ($planning as $matiereId => $semestres) {
            $etat[] = 'p'.$matiereId.':'.implode(',', $semestres);
        }

        return hash('sha256', implode('|', $etat));
    }
}
