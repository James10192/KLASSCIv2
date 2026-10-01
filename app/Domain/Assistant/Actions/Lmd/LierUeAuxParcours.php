<?php

namespace App\Domain\Assistant\Actions\Lmd;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPUniteEnseignement;
use App\Services\LMD\ParcoursUeSyncService;
use Illuminate\Support\Facades\DB;

/**
 * Propose de retirer une UE d'un ou plusieurs parcours, ou de l'y ajouter sur un
 * semestre. Le cas d'origine : une UE partagée entre deux parcours alors qu'elle
 * n'appartient qu'à l'un des deux (USAT, AGR2103 entre LPA et LPV).
 *
 * L'écriture passe par ParcoursUeSyncService::syncPourUnite, le même chemin que
 * l'écran « Lier à des parcours » : seuls les liens qui changent sont touchés,
 * le crédit propre à une maquette et l'ordre des autres sont gardés.
 */
class LierUeAuxParcours extends ActionAgent
{
    public function __construct(private ParcoursUeSyncService $sync)
    {
    }

    public function cle(): string
    {
        return 'liaison_ue_parcours';
    }

    public function libelle(): string
    {
        return 'Préparation de la liaison UE ↔ parcours…';
    }

    public function description(): string
    {
        return "PROPOSE de retirer une UE LMD d'un ou plusieurs parcours, ou de l'ajouter à un parcours sur un semestre. "
            . "Désigne l'UE par son identifiant ou son code imprimé, et les parcours par leur CODE (LPA, LPV…). Rien n'est écrit avant « Valider ».";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'ue_id' => ['type' => 'integer', 'description' => "Identifiant de l'UE, si connu."],
                'ue_code' => ['type' => 'string', 'description' => "Code imprimé de l'UE (ex. AGR2103), si l'identifiant est inconnu."],
                'retirer' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Codes des parcours dont retirer l\'UE (tous ses semestres dans ce parcours).'],
                'ajouter' => [
                    'type' => 'array',
                    'description' => 'Parcours où ajouter l\'UE.',
                    'items' => ['type' => 'object', 'properties' => [
                        'parcours' => ['type' => 'string', 'description' => 'Code du parcours.'],
                        'semestre' => ['type' => 'integer', 'description' => 'Semestre (1 à 10).'],
                    ]],
                ],
            ],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = 'Lier une UE aux parcours';
        [$ue, $manque] = $this->ue($args);
        if (! $ue) {
            return new Proposition(titre: $titre, resume: '', manques: [$manque]);
        }

        $actuels = $this->liens($ue);
        $parCode = ESBTPLMDParcours::query()->get(['id', 'code', 'name'])->keyBy(fn ($p) => mb_strtoupper(trim((string) $p->code)));
        $manques = [];

        $retirer = [];
        foreach ((array) ($args['retirer'] ?? []) as $code) {
            $p = $parCode[mb_strtoupper(trim((string) $code))] ?? null;
            if (! $p) {
                $manques[] = "Parcours inconnu : {$code}.";
            } elseif (! collect($actuels)->contains('parcours_id', $p->id)) {
                $manques[] = "L'UE n'est pas liée au parcours {$p->code} : rien à retirer.";
            } else {
                $retirer[] = (int) $p->id;
            }
        }
        $ajouter = [];
        foreach ((array) ($args['ajouter'] ?? []) as $item) {
            $p = $parCode[mb_strtoupper(trim((string) ($item['parcours'] ?? '')))] ?? null;
            $sem = (int) ($item['semestre'] ?? 0);
            if (! $p) {
                $manques[] = 'Parcours inconnu : '.($item['parcours'] ?? '?').'.';
            } elseif ($sem < 1 || $sem > 10) {
                $manques[] = "Sur quel semestre ajouter l'UE au parcours {$p->code} ?";
            } else {
                $ajouter[] = ['parcours_id' => (int) $p->id, 'semestre' => $sem];
            }
        }
        if ($retirer === [] && $ajouter === [] && $manques === []) {
            $manques[] = 'Quels parcours retirer ou ajouter ?';
        }
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: $manques);
        }

        $voulus = collect($actuels)->reject(fn ($l) => in_array($l['parcours_id'], $retirer, true))
            ->map(fn ($l) => ['parcours_id' => $l['parcours_id'], 'semestre' => $l['semestre']])
            ->merge($ajouter)->unique(fn ($l) => $l['parcours_id'].'_'.$l['semestre'])->values()->all();

        $nom = fn (int $id) => $parCode->first(fn ($p) => (int) $p->id === $id)?->code ?? '#'.$id;
        $decrire = fn (array $liens) => collect($liens)->map(fn ($l) => $nom($l['parcours_id']).' (S'.$l['semestre'].')')->implode(', ') ?: 'aucun';

        $avertissements = [];
        if ($voulus === []) {
            $avertissements[] = "L'UE ne sera plus liée à aucun parcours : elle n'apparaîtra plus dans les maquettes.";
        }
        $reserves = DB::table('esbtp_ue_matiere')->where('unite_enseignement_id', $ue->id)->whereIn('parcours_id', $retirer)->count();
        if ($reserves > 0) {
            $avertissements[] = "{$reserves} élément(s) (ECUE) de cette UE sont réservés à un parcours retiré : ils resteront rattachés mais ne seront plus affichés dans sa maquette.";
        }

        return new Proposition(
            titre: $titre,
            resume: sprintf('%s (%s) : %s → %s.', $ue->name, $ue->code_affiche ?? $ue->code, $decrire($actuels), $decrire($voulus)),
            tableau: [
                'colonnes' => ['UE', 'Liée aujourd\'hui à', 'Après validation'],
                'lignes' => [[(string) ($ue->code_affiche ?? $ue->code).' — '.$ue->name, $decrire($actuels), $decrire($voulus)]],
            ],
            avertissements: $avertissements,
            donnees: ['ue_id' => (int) $ue->id, 'liens' => $voulus],
            etat: ['liens' => $actuels],
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        $ue = ESBTPUniteEnseignement::find((int) $proposition->donnees['ue_id']);
        if (! $ue) {
            throw new PropositionPerimee("Cette UE n'existe plus.");
        }
        if ($this->liens($ue) !== $proposition->etat['liens']) {
            throw new PropositionPerimee('Les parcours de cette UE ont changé depuis la proposition.');
        }

        $bilan = $this->sync->syncPourUnite($ue, $proposition->donnees['liens']);

        return [
            'message' => sprintf('Parcours de l’UE %s mis à jour : %d lien(s) retiré(s), %d ajouté(s).', $ue->code_affiche ?? $ue->code, $bilan['detached'], $bilan['attached']),
            'lien' => route('esbtp.lmd.ue.index', [], false),
            'model_type' => ESBTPUniteEnseignement::class,
            'model_id' => (int) $ue->id,
            'details' => $bilan,
        ];
    }

    /** @return array<int, array{parcours_id: int, semestre: int}> triés, pour comparer à l'identique */
    private function liens(ESBTPUniteEnseignement $ue): array
    {
        return DB::table('esbtp_lmd_parcours_ue')->where('unite_enseignement_id', $ue->id)
            ->orderBy('parcours_id')->orderBy('semestre')
            ->get(['parcours_id', 'semestre'])
            ->map(fn ($l) => ['parcours_id' => (int) $l->parcours_id, 'semestre' => (int) $l->semestre])->all();
    }

    /** @return array{0: ?ESBTPUniteEnseignement, 1: ?string} */
    private function ue(array $args): array
    {
        if (($id = (int) ($args['ue_id'] ?? 0)) > 0) {
            $ue = ESBTPUniteEnseignement::find($id);

            return [$ue, $ue ? null : "UE #{$id} introuvable."];
        }
        $code = mb_strtoupper(trim((string) ($args['ue_code'] ?? '')));
        if ($code === '') {
            return [null, 'Quelle UE (code ou identifiant) ?'];
        }
        // Le code imprimé n'est unique que dans un parcours : `AGR2103~LPA` s'affiche AGR2103.
        $trouvees = ESBTPUniteEnseignement::query()
            ->where(fn ($q) => $q->whereRaw('UPPER(code) = ?', [$code])->orWhereRaw('UPPER(code) LIKE ?', [$code.'~%']))
            ->get();

        return $trouvees->count() === 1
            ? [$trouvees->first(), null]
            : [null, $trouvees->isEmpty() ? "Aucune UE au code {$code}." : "Plusieurs UE portent le code {$code} : indique son identifiant."];
    }
}
