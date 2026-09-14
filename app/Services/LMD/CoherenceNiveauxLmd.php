<?php

namespace App\Services\LMD;

use App\Models\ESBTPNiveauEtude;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Releve les niveaux LMD dont l'annee contredit le cycle, et ce qui en depend.
 *
 * Corriger l'annee d'un niveau deplace tout ce qui s'y rattache vers d'autres
 * semestres : un Master 1 passe de S1-S2 a S7-S8, et perd du meme coup les
 * unites de Licence qu'il recevait a tort. Si des notes, des evaluations ou des
 * bulletins ont deja ete ecrits sur ces unites, la correction les detacherait.
 *
 * On ne corrige donc rien ici : on montre, pour chaque niveau incoherent, ce
 * que la correction toucherait. La decision revient a qui connait l'ecole.
 *
 * Lecture seule.
 */
class CoherenceNiveauxLmd
{
    /** @return array{niveaux_lmd: int, incoherents: list<array<string,mixed>>} */
    public function rapport(): array
    {
        $lmd = ESBTPNiveauEtude::whereIn('type', array_keys(ESBTPNiveauEtude::ANNEES_PAR_CYCLE_LMD))
            ->orderBy('type')->orderBy('year')->get();

        $incoherents = $lmd
            ->filter(fn ($n) => $n->anneeCoherenteAvecSonCycle() === false)
            ->map(fn ($n) => $this->detail($n))
            ->values()
            ->all();

        return ['niveaux_lmd' => $lmd->count(), 'incoherents' => $incoherents];
    }

    /** @return array<string,mixed> */
    private function detail(ESBTPNiveauEtude $niveau): array
    {
        $classes = $this->vivants('esbtp_classes')->where('niveau_etude_id', $niveau->id)->pluck('id')->all();
        $emplois = $this->vivants('esbtp_emploi_temps')->whereIn('classe_id', $classes)->pluck('id')->all();
        $actuel = ((int) $niveau->year - 1) * 2 + 1;
        $annees = ESBTPNiveauEtude::ANNEES_PAR_CYCLE_LMD[$niveau->type];

        return [
            'id' => $niveau->id,
            'nom' => $niveau->name,
            'type' => $niveau->type,
            'annee' => (int) $niveau->year,
            'annees_attendues' => $annees,
            'semestres_actuels' => [$actuel, $actuel + 1],
            'dependances' => [
                'classes' => count($classes),
                'inscriptions' => $this->vivants('esbtp_inscriptions')->where('niveau_id', $niveau->id)->count(),
                'unites_enseignement' => $this->vivants('esbtp_unites_enseignement')->where('niveau_id', $niveau->id)->count(),
                'emplois_du_temps' => count($emplois),
                'seances' => $this->vivants('esbtp_seance_cours')->whereIn('emploi_temps_id', $emplois)->count(),
                'evaluations' => $this->vivants('esbtp_evaluations')->whereIn('classe_id', $classes)->count(),
                'notes' => $this->vivants('esbtp_notes')->whereIn('classe_id', $classes)->count(),
                'bulletins_lmd' => $this->vivants('esbtp_lmd_bulletins')->whereIn('classe_id', $classes)->count(),
                'jurys' => $this->vivants('esbtp_lmd_jurys')->whereIn('classe_id', $classes)->count(),
            ],
        ];
    }

    private function vivants(string $table)
    {
        $query = DB::table($table);

        return Schema::hasColumn($table, 'deleted_at') ? $query->whereNull('deleted_at') : $query;
    }
}
