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
 * Le rapport montre, pour chaque niveau incoherent, ce que la correction
 * toucherait. La correction elle-meme n'est faite que sur demande, niveau par
 * niveau, et refusee des qu'une donnee academique s'y rattache : la decision
 * revient a qui connait l'ecole, et ce qui a deja ete note ne se deplace pas
 * en silence.
 */
class CoherenceNiveauxLmd
{
    /** Ce qui se detacherait si les semestres du niveau changeaient. */
    private const DEPENDANCES_BLOQUANTES = [
        'unites_enseignement', 'seances', 'evaluations', 'notes', 'bulletins_lmd', 'jurys',
    ];

    /**
     * Passe un niveau LMD sur une annee de son cycle. Sans `$appliquer`, rend
     * seulement ce qui serait fait.
     *
     * @return array{applique: bool, refus: list<string>, niveau: array<string,mixed>, annee_cible: int}
     */
    public function corrigerAnnee(ESBTPNiveauEtude $niveau, int $annee, bool $appliquer): array
    {
        $detail = $this->detail($niveau);
        $refus = $this->refus($niveau, $annee, $detail['dependances']);
        $applique = $appliquer && $refus === [];

        if ($applique) {
            $niveau->update(['year' => $annee]);
        }

        return ['applique' => $applique, 'refus' => $refus, 'niveau' => $detail, 'annee_cible' => $annee];
    }

    /** @return list<string> */
    private function refus(ESBTPNiveauEtude $niveau, int $annee, array $dependances): array
    {
        if (! $niveau->estUnCycleLmd()) {
            return ["{$niveau->name} n'est pas un niveau LMD."];
        }

        $refus = [];
        $annees = ESBTPNiveauEtude::ANNEES_PAR_CYCLE_LMD[$niveau->type];
        if (! in_array($annee, $annees, true)) {
            $refus[] = sprintf('Un %s porte une annee parmi %s, pas %d.', $niveau->type, implode(', ', $annees), $annee);
        }

        $homonyme = ESBTPNiveauEtude::where('type', $niveau->type)->where('year', $annee)
            ->whereKeyNot($niveau->getKey())->first();
        if ($homonyme) {
            $refus[] = "Le niveau {$homonyme->name} (id {$homonyme->id}) porte deja {$niveau->type} annee {$annee}.";
        }

        foreach ($this->bloquantesParmi($dependances) as $bloquante) {
            $refus[] = "{$bloquante} rattache(s) : la correction les detacherait de leurs semestres.";
        }

        return $refus;
    }

    /**
     * Ce qui interdit de changer l'annee du niveau, sous forme lisible
     * (« 12 notes »). Vide si rien ne s'y oppose.
     *
     * @return list<string>
     */
    public function dependancesBloquantes(ESBTPNiveauEtude $niveau): array
    {
        return $this->bloquantesParmi($this->detail($niveau)['dependances']);
    }

    /** @return list<string> */
    private function bloquantesParmi(array $dependances): array
    {
        $libelles = [
            'unites_enseignement' => 'unité(s) d\'enseignement', 'seances' => 'séance(s)',
            'evaluations' => 'évaluation(s)', 'notes' => 'note(s)',
            'bulletins_lmd' => 'bulletin(s)', 'jurys' => 'jury(s)',
        ];

        return collect(self::DEPENDANCES_BLOQUANTES)
            ->filter(fn ($cle) => $dependances[$cle] > 0)
            ->map(fn ($cle) => $dependances[$cle].' '.$libelles[$cle])
            ->values()
            ->all();
    }

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
