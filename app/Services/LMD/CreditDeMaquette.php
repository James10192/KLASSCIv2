<?php

namespace App\Services\LMD;

use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPPlanificationAcademique;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Le credit d'un ECUE dans la maquette d'une filiere, et la reparation des
 * planifications que la saisie d'heures creait a zero credit.
 *
 * Une ECUE partagee peut valoir 3 credits chez LPA et 2 chez LPV : prendre la
 * premiere ligne venue graverait le credit d'un autre parcours, que l'ecran lit
 * ensuite avant tout repli. D'ou l'ordre : ligne reservee au parcours de la
 * filiere, sinon ligne commune, sinon credit de la matiere.
 */
class CreditDeMaquette
{
    public function pourFiliere(int $ecueId, int $filiereId): int
    {
        $parcours = ESBTPLMDParcours::where('filiere_id', $filiereId)->pluck('id')->map(fn ($id) => (int) $id)->all();

        $lignes = DB::table('esbtp_ue_matiere')->where('matiere_id', $ecueId)
            ->whereNotNull('credit_ecue')
            ->whereIn('parcours_id', array_merge([0], $parcours))
            ->get(['parcours_id', 'credit_ecue']);

        $reserves = $lignes->where('parcours_id', '!=', 0)->pluck('credit_ecue')->map(fn ($c) => (int) $c)->unique();
        if ($reserves->count() === 1) {
            return $reserves->first();
        }
        // Plusieurs parcours sur la meme filiere avec des credits differents :
        // on ne devine pas.
        if ($reserves->count() > 1) {
            Log::warning('LMD : credits divergents pour une meme filiere, credit reserve ignore', [
                'matiere_id' => $ecueId, 'filiere_id' => $filiereId, 'credits' => $reserves->values()->all(),
            ]);
        }

        $commun = $lignes->firstWhere('parcours_id', 0);

        return (int) ($commun->credit_ecue ?? ESBTPMatiere::whereKey($ecueId)->value('credit_ecue') ?? 0);
    }

    /**
     * Planifications LMD a 0 credit alors que la maquette en donne, sans trace
     * d'une decision humaine.
     *
     * Sont ecartees les lignes dont le journal d'audit montre une creation
     * (auditee depuis septembre 2026 : toute ligne creee depuis porte donc son
     * vrai credit ou un 0 choisi) ou une modification du credit. Ce qui reste
     * est anterieur : un 0 saisi a la creation, avant cet audit, n'y a laisse
     * AUCUNE trace et ne se distingue pas d'un 0 laisse par la saisie d'heures.
     * D'ou l'ecriture sur liste relue (reparer()), jamais d'office.
     *
     * @return Collection<int, array{id:int, matiere:string, filiere_id:int, semestre:int, annee_universitaire_id:int, credit_attendu:int}>
     */
    public function creditsNulsAReparer(): Collection
    {
        $tracees = DB::table('audits')
            ->where('auditable_type', ESBTPPlanificationAcademique::class)
            ->where(fn ($q) => $q->where('event', 'created')
                ->orWhere(fn ($m) => $m->where('event', 'updated')->where('new_values', 'like', '%"credits_ects"%')))
            ->pluck('auditable_id')
            ->map(fn ($id) => (int) $id)
            ->flip();

        return ESBTPPlanificationAcademique::query()
            ->where('credits_ects', 0)
            ->whereHas('matiere', fn ($q) => $q->whereNotNull('unite_enseignement_id'))
            ->with('matiere:id,code,name')
            ->get()
            ->reject(fn ($pl) => $tracees->has((int) $pl->id))
            ->map(fn ($pl) => [
                'id' => (int) $pl->id,
                'matiere' => trim(($pl->matiere->code ?? '') . ' ' . ($pl->matiere->name ?? '')),
                'filiere_id' => (int) $pl->filiere_id,
                'semestre' => (int) $pl->semestre,
                'annee_universitaire_id' => (int) $pl->annee_universitaire_id,
                'credit_attendu' => $this->pourFiliere((int) $pl->matiere_id, (int) $pl->filiere_id),
            ])
            ->filter(fn ($l) => $l['credit_attendu'] > 0)
            ->values();
    }

    /**
     * Simulation : liste les candidates. Ecriture : seulement les identifiants
     * fournis, relus par l'ecole, et encore candidats au moment d'ecrire.
     *
     * @param  array<int, int>  $ids
     * @return array{candidates: int, reparees: int, lignes: array}
     */
    public function reparer(bool $simulation = true, array $ids = []): array
    {
        // Sans audit, « aucune trace » ne prouve plus rien.
        if (! $simulation && ! config('audit.enabled')) {
            throw new \RuntimeException("L'audit est desactive sur cette instance : impossible de distinguer un credit laisse a 0 par la saisie d'un 0 choisi. Rien n'a ete modifie.");
        }
        if (! $simulation && $ids === []) {
            throw new \InvalidArgumentException("Indiquez les identifiants relus (ids) : un 0 saisi avant septembre 2026 ne laisse aucune trace, seule l'ecole peut trancher.");
        }

        $lignes = $this->creditsNulsAReparer();
        $reparees = 0;

        if (! $simulation) {
            $voulus = array_flip(array_map('intval', $ids));
            $aEcrire = $lignes->filter(fn ($l) => isset($voulus[$l['id']]));

            DB::transaction(function () use ($aEcrire, &$reparees) {
                foreach ($aEcrire as $l) {
                    // save() et non un update en masse : l'audit garde la trace.
                    $pl = ESBTPPlanificationAcademique::lockForUpdate()->find($l['id']);
                    if ($pl && (int) $pl->credits_ects === 0) {
                        $pl->credits_ects = $l['credit_attendu'];
                        $pl->save();
                        $reparees++;
                    }
                }
            });
        }

        return [
            'candidates' => $lignes->count(),
            'reparees' => $reparees,
            'lignes' => $lignes->all(),
        ];
    }
}
