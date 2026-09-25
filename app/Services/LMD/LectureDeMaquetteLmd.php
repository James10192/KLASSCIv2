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
                // Comme le planning : une unite desactivee n'est vue par personne.
                ->where('esbtp_unites_enseignement.is_active', true)
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
            'anomalies' => $this->anomalies($vues),
        ];
    }

    private function vueUnite(ESBTPUniteEnseignement $ue, ESBTPLMDParcours $p, Collection $heures): array
    {
        $ecues = $ue->getEcuesEffectifs((int) $p->id);
        $idsPivot = $ue->ecues->pluck('id')->all();

        return [
            'id' => $ue->id,
            'code' => $ue->code,
            'code_imprime' => $ue->code_affiche,
            'name' => $ue->name,
            'semestre' => (int) $ue->pivot->semestre,
            'niveau' => $ue->niveau?->name,
            'credit' => $ue->pivot->credit ?? $ue->credit,
            'partagee_avec' => $ue->parcoursMultiple->pluck('code')->unique()
                ->reject(fn ($code) => $code === $p->code)->values(),
            'ecues' => $ecues->map(function ($e) use ($idsPivot, $heures, $p, $ue) {
                // Pas de niveau dans la cle : la fiche d'une unite partagee garde
                // celui du premier parcours importe, alors que la planification
                // du second est ecrite avec le sien. Le planning cherche lui
                // aussi par filiere et matiere.
                $h = $heures->get($p->filiere_id . ':' . $ue->pivot->semestre . ':' . $e->id);

                // Reserve a un AUTRE parcours dans le pivot, mais vu ici par une
                // ligne commune ou par la cle etrangere : c'est la « fuite ».
                $reserveAilleurs = $ue->ecues
                    ->where('id', $e->id)
                    ->map(fn ($l) => (int) ($l->pivot->parcours_id ?? 0))
                    ->reject(fn ($id) => $id === 0 || $id === (int) $p->id)
                    ->isNotEmpty();

                return [
                    'id' => $e->id,
                    'code' => $e->code,
                    'code_imprime' => $e->code_affiche,
                    'name' => $e->name,
                    'origine' => $this->origine($e, $idsPivot),
                    'reserve_ailleurs' => $reserveAilleurs,
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
            'code_imprime' => $ue->code_affiche,
            'name' => $ue->name,
            'parcours' => $codes->unique()->values(),
            'lignes' => $lignes->values(),
        ];
    }

    /**
     * Ce qui surprend l'ecole, et seulement cela. Une composition commune est
     * le fonctionnement normal d'une unite partagee : on ne la signale pas.
     */
    private function anomalies(Collection $vues): array
    {
        $anomalies = [];

        foreach ($vues as $p) {
            if (! $p['filiere_id']) {
                $anomalies[] = ['type' => 'parcours_sans_filiere', 'parcours' => $p['code']];
            }

            foreach ($p['unites'] as $u) {
                $partagee = count($u['partagee_avec']) > 0;

                // Deux elements de meme intitule dans une meme maquette.
                $parNom = collect($u['ecues'])->groupBy(fn ($e) => mb_strtolower(trim($e['name'])));
                foreach ($parNom->filter(fn ($g) => $g->count() > 1) as $groupe) {
                    $anomalies[] = [
                        'type' => 'doublon_d_intitule',
                        'parcours' => $p['code'],
                        'ue' => $u['code'],
                        'ecues' => $groupe->map(fn ($e) => $e['code'] . ' (' . $e['origine'] . ')')->values(),
                    ];
                }

                foreach ($u['ecues'] as $e) {
                    if ($e['reserve_ailleurs']) {
                        $anomalies[] = [
                            'type' => 'reserve_ailleurs_mais_visible',
                            'parcours' => $p['code'],
                            'ue' => $u['code'],
                            'ecue' => $e['code'] . ' — ' . $e['name'],
                            'origine' => $e['origine'],
                        ];
                    } elseif ($partagee && $e['origine'] === 'cle_etrangere') {
                        $anomalies[] = [
                            'type' => 'cle_etrangere_dans_unite_partagee',
                            'parcours' => $p['code'],
                            'ue' => $u['code'],
                            'ecue' => $e['code'] . ' — ' . $e['name'],
                            'aussi_vu_par' => $u['partagee_avec'],
                        ];
                    }

                    if ($p['filiere_id'] && $e['heures'] === null) {
                        $anomalies[] = ['type' => 'sans_masse_horaire', 'parcours' => $p['code'], 'ue' => $u['code'], 'ecue' => $e['code']];
                    }
                }
            }
        }

        return $anomalies;
    }

    /** Planifications de l'annee courante, indexees filiere:semestre:matiere. */
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
            ->keyBy(fn ($pl) => $pl->filiere_id . ':' . $pl->semestre . ':' . $pl->matiere_id);
    }
}
