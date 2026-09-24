<?php

namespace App\Services\LMD;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\ESBTPUniteEnseignement;
use Illuminate\Support\Collection;

/**
 * La maquette LMD telle que chaque parcours la VOIT, pour un diagnostic a
 * distance. Lecture seule.
 *
 * L'ecran /esbtp/lmd/ue montre une unite a la fois. Quand une ecole dit « je
 * vois dans Productions Vegetales un element que j'avais reserve a Productions
 * Animales », il faut savoir, pour chaque unite partagee, QUELLE ligne donne cet
 * element a quelle maquette. Trois origines possibles, et l'ecran ne les
 * distingue pas toutes :
 *
 *  - une ligne de pivot `parcours_id = 0` : composition commune, visible par
 *    tous les parcours de l'unite — c'est voulu ;
 *  - une ligne de pivot reservee a un parcours : visible par lui seul ;
 *  - aucune ligne de pivot, seulement la cle etrangere
 *    `esbtp_matieres.unite_enseignement_id` : `getEcuesEffectifs()` la lit
 *    comme commune, donc visible partout. C'est le cas qui surprend.
 *
 * La composition affichee par parcours passe par `getEcuesEffectifs()`, la
 * meme methode que le planning, les bulletins et l'ecran : on montre ce que
 * l'ecole voit, pas une reconstruction.
 */
class LectureDeMaquetteLmd
{
    /**
     * @return array{parcours: array, unites_partagees: array, anomalies: array}
     */
    public function lire(?int $parcoursId = null, ?string $codeUe = null): array
    {
        $parcours = ESBTPLMDParcours::query()
            ->when($parcoursId, fn ($q) => $q->whereKey($parcoursId))
            ->with(['unitesEnseignement' => fn ($q) => $q
                ->when($codeUe, fn ($q) => $q->where('esbtp_unites_enseignement.code', $codeUe))
                ->with(['ecues', 'matieres', 'parcoursMultiple:id,code', 'niveau:id,name'])])
            ->orderBy('name')
            ->get();

        $heures = $this->heuresPlanifiees($parcours);

        $vues = $parcours->map(fn (ESBTPLMDParcours $p) => [
            'id' => $p->id,
            'code' => $p->code,
            'name' => $p->name,
            'filiere_id' => $p->filiere_id,
            'unites' => $p->unitesEnseignement
                ->sortBy([['pivot.semestre', 'asc'], ['code', 'asc']])
                ->map(fn (ESBTPUniteEnseignement $ue) => $this->vueUnite($ue, $p, $heures))
                ->values(),
        ])->values();

        $partagees = $parcours->flatMap->unitesEnseignement
            ->unique('id')
            ->filter(fn (ESBTPUniteEnseignement $ue) => $ue->parcoursMultiple->pluck('id')->unique()->count() > 1)
            ->map(fn (ESBTPUniteEnseignement $ue) => $this->compositionBrute($ue))
            ->values();

        return [
            'parcours' => $vues,
            'unites_partagees' => $partagees,
            'anomalies' => $this->anomalies($vues, $partagees),
        ];
    }

    private function vueUnite(ESBTPUniteEnseignement $ue, ESBTPLMDParcours $p, Collection $heures): array
    {
        $ecues = $ue->getEcuesEffectifs((int) $p->id);
        $idsPivot = $ue->ecues->pluck('id')->all();

        return [
            'id' => $ue->id,
            'code' => $ue->code,
            'name' => $ue->name,
            'semestre' => (int) $ue->pivot->semestre,
            'niveau' => $ue->niveau?->name,
            'credit' => $ue->pivot->credit ?? $ue->credit,
            'partagee_avec' => $ue->parcoursMultiple->pluck('code')->unique()
                ->reject(fn ($code) => $code === $p->code)->values(),
            'ecues' => $ecues->map(function ($e) use ($idsPivot, $heures, $p, $ue) {
                $h = $heures->get($p->filiere_id . ':' . $ue->niveau_id . ':' . $ue->pivot->semestre . ':' . $e->id);

                return [
                    'id' => $e->id,
                    'code' => $e->code,
                    'name' => $e->name,
                    'origine' => $this->origine($e, $idsPivot),
                    'credit' => $e->pivot->credit_ecue ?? $e->credit_ecue,
                    'heures' => $h ? [
                        'cm' => (int) $h->volume_horaire_cm,
                        'td' => (int) $h->volume_horaire_td,
                        'tp' => (int) $h->volume_horaire_tp,
                        'projet' => (int) $h->volume_horaire_projet,
                        'tpe' => (int) $h->volume_horaire_tpe,
                        'total' => (int) $h->volume_horaire_total,
                    ] : null,
                ];
            })->values(),
        ];
    }

    /** « commun », « reserve », ou « cle_etrangere » (sans ligne de pivot, donc vu partout). */
    private function origine($ecue, array $idsPivot): string
    {
        if (! in_array($ecue->id, $idsPivot, true)) {
            return 'cle_etrangere';
        }

        return (int) ($ecue->pivot->parcours_id ?? 0) === 0 ? 'commun' : 'reserve';
    }

    /** Toutes les lignes qui composent une unite partagee, maquette par maquette. */
    private function compositionBrute(ESBTPUniteEnseignement $ue): array
    {
        $codes = $ue->parcoursMultiple->pluck('code', 'id');
        $idsPivot = $ue->ecues->pluck('id')->all();

        $lignes = $ue->ecues->map(fn ($e) => [
            'ecue' => $e->code . ' — ' . $e->name,
            'maquette' => (int) ($e->pivot->parcours_id ?? 0) === 0
                ? 'commun'
                : ($codes[(int) $e->pivot->parcours_id] ?? 'parcours #' . $e->pivot->parcours_id),
        ])->concat($ue->matieres->where('is_active', true)
            ->reject(fn ($m) => in_array($m->id, $idsPivot, true))
            ->map(fn ($m) => ['ecue' => $m->code . ' — ' . $m->name, 'maquette' => 'commun (cle etrangere, sans pivot)']));

        return [
            'id' => $ue->id,
            'code' => $ue->code,
            'name' => $ue->name,
            'parcours' => $codes->unique()->values(),
            'lignes' => $lignes->values(),
        ];
    }

    private function anomalies(Collection $vues, Collection $partagees): array
    {
        $anomalies = [];

        foreach ($vues as $p) {
            foreach ($p['unites'] as $u) {
                // Deux elements de meme intitule dans une meme maquette : la
                // version commune et une version reservee cohabitent.
                $parNom = collect($u['ecues'])->groupBy(fn ($e) => mb_strtolower(trim($e['name'])));
                foreach ($parNom->filter(fn ($g) => $g->count() > 1) as $groupe) {
                    $anomalies[] = [
                        'type' => 'doublon_d_intitule',
                        'parcours' => $p['code'],
                        'ue' => $u['code'],
                        'ecues' => $groupe->map(fn ($e) => $e['code'] . ' (' . $e['origine'] . ')')->values(),
                    ];
                }

                // Unite partagee dont un element commun apparait chez tous :
                // c'est ce que l'ecole prend pour une fuite.
                if (count($u['partagee_avec']) > 0) {
                    foreach ($u['ecues'] as $e) {
                        if ($e['origine'] !== 'reserve') {
                            $anomalies[] = [
                                'type' => 'element_commun_dans_unite_partagee',
                                'parcours' => $p['code'],
                                'ue' => $u['code'],
                                'ecue' => $e['code'] . ' — ' . $e['name'],
                                'origine' => $e['origine'],
                                'aussi_vu_par' => $u['partagee_avec'],
                            ];
                        }
                    }
                }

                foreach ($u['ecues'] as $e) {
                    if ($e['heures'] === null) {
                        $anomalies[] = ['type' => 'sans_masse_horaire', 'parcours' => $p['code'], 'ue' => $u['code'], 'ecue' => $e['code']];
                    }
                }
            }
        }

        return $anomalies;
    }

    /** Planifications de l'annee courante, indexees filiere:niveau:semestre:matiere. */
    private function heuresPlanifiees(Collection $parcours): Collection
    {
        $annee = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        if (! $annee) {
            return collect();
        }

        $filieres = $parcours->pluck('filiere_id')->filter()->unique()->all();

        return ESBTPPlanificationAcademique::where('annee_universitaire_id', $annee->id)
            ->whereIn('filiere_id', $filieres)
            ->get()
            ->keyBy(fn ($pl) => $pl->filiere_id . ':' . $pl->niveau_etude_id . ':' . $pl->semestre . ':' . $pl->matiere_id);
    }
}
