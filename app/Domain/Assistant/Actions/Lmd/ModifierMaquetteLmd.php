<?php

namespace App\Domain\Assistant\Actions\Lmd;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPUniteEnseignement;
use App\Services\LMD\CodeDeMaquette;
use App\Services\LMD\CompositionUe;
use App\Services\LMD\EcritureEcue;
use App\Services\LMD\ParcoursUeSyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Aligner une maquette LMD sur un bulletin officiel : codes, crédits,
 * coefficients et ordre d'affichage des UE et de leurs éléments, et ajout d'un
 * élément manquant. Les gestes des modals de /esbtp/lmd/ue, par les mêmes
 * services : EcritureEcue pour les éléments, ParcoursUeSyncService pour le rang
 * d'une UE dans un parcours.
 *
 * Le cas d'origine : ESBTP Abidjan, octobre 2026. Le L1 S1 de Bâtiment et
 * Travaux Publics a été recodé et réordonné d'après les bulletins officiels, et
 * « Initiation au génie civil » ajoutée.
 *
 * Le code d'une UE ne se change ici que s'il n'est pas propre à un parcours
 * (sans suffixe) : sinon l'écran, qui connaît la case « propre au parcours ».
 */
class ModifierMaquetteLmd extends ActionAgent
{
    use DesigneLaMaquette;
    private const MAX_LIGNES = 60;

    public function __construct(
        private EcritureEcue $ecritures,
        private CompositionUe $composition,
        private ParcoursUeSyncService $sync,
        private CodeDeMaquette $maquette,
    ) {
    }

    public function cle(): string
    {
        return 'modification_maquette_lmd';
    }

    public function libelle(): string
    {
        return 'Préparation des modifications de la maquette…';
    }

    public function description(): string
    {
        return "PROPOSE de modifier une maquette LMD existante : code, intitulé, crédit et rang au bulletin d'une UE dans un parcours ; "
            . "code, intitulé, crédit, coefficient et ordre au bulletin d'un élément (ECUE) ; ajout d'un élément à une UE. "
            . "N'envoie que les champs à changer. Pour créer une maquette complète, utilise proposer_import_maquette_lmd ; pour retirer un élément, proposer_retrait_ecue_lmd. "
            . "Rien n'est écrit avant « Valider ».";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'parcours' => ['type' => 'string', 'description' => "Code du parcours dont on aligne la maquette (requis pour un rang d'UE ou un élément réservé)."],
                'ues' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => [
                    'ue' => ['type' => 'string', 'description' => "Code imprimé actuel ou identifiant de l'UE."],
                    'code' => ['type' => 'string', 'description' => 'Nouveau code.'],
                    'intitule' => ['type' => 'string'],
                    'credit' => ['type' => 'integer'],
                    'rang' => ['type' => 'integer', 'description' => 'Rang de l\'UE sur le bulletin du parcours (1 = en premier).'],
                ], 'required' => ['ue']]],
                'elements' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => [
                    'ue' => ['type' => 'string', 'description' => "Code imprimé (après modification éventuelle : non, l'ACTUEL) ou identifiant de l'UE."],
                    'element' => ['type' => 'string', 'description' => "Code, intitulé exact ou identifiant de l'élément à modifier. Vide pour en AJOUTER un."],
                    'code' => ['type' => 'string'],
                    'intitule' => ['type' => 'string'],
                    'credit' => ['type' => 'integer'],
                    'coefficient' => ['type' => 'number'],
                    'ordre' => ['type' => 'integer', 'description' => "Ordre de l'élément dans son UE sur le bulletin (1 = en premier)."],
                    'reserve_au_parcours' => ['type' => 'boolean', 'description' => 'Ajout seulement : true pour un élément propre au parcours, sinon commun.'],
                ], 'required' => ['ue']]],
            ],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = 'Modifier la maquette LMD';
        $ues = array_values(array_filter((array) ($args['ues'] ?? []), 'is_array'));
        $elements = array_values(array_filter((array) ($args['elements'] ?? []), 'is_array'));
        if ($ues === [] && $elements === []) {
            return new Proposition(titre: $titre, resume: '', manques: ['Quelles UE ou quels éléments modifier ?']);
        }
        if (count($ues) + count($elements) > self::MAX_LIGNES) {
            return new Proposition(titre: $titre, resume: '', manques: ['Trop de modifications en une fois (' . self::MAX_LIGNES . ' au plus) : découpe par semestre.']);
        }

        $manques = [];
        $parcours = null;
        if (trim((string) ($args['parcours'] ?? '')) !== '') {
            $parcours = ESBTPLMDParcours::whereRaw('UPPER(code) = ?', [mb_strtoupper(trim((string) $args['parcours']))])->first();
            if (! $parcours) {
                $manques[] = 'Parcours inconnu : ' . $args['parcours'] . '.';
            }
        }

        [$opsUe, $credits, $m1] = $this->preparerUes($ues, $parcours);
        [$opsEl, $m2] = $this->preparerElements($elements, $parcours, $credits);
        $manques = array_merge($manques, $m1, $m2);
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: $manques);
        }
        $lignes = array_merge(array_column($opsUe, 'ligne'), array_column($opsEl, 'ligne'));
        if ($lignes === []) {
            return Proposition::sansObjet($titre, 'La maquette porte déjà ces valeurs.');
        }

        $ueIds = array_unique(array_merge(array_column($opsUe, 'ue_id'), array_column($opsEl, 'ue_id')));

        return new Proposition(
            titre: $titre . ($parcours ? " — {$parcours->code}" : ''),
            resume: sprintf('%d modification(s) sur %d UE.', count($lignes), count($ueIds)),
            tableau: ['colonnes' => ['UE', 'Élément', 'Avant', 'Après'], 'lignes' => $lignes],
            avertissements: ['Les bulletins déjà générés gardent l\'ancienne maquette : régénérez-les.'],
            donnees: ['ues' => array_map(fn ($o) => array_diff_key($o, ['ligne' => 1]), $opsUe),
                'elements' => array_map(fn ($o) => array_diff_key($o, ['ligne' => 1]), $opsEl)],
            etat: $this->etat($ueIds),
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        $d = $proposition->donnees;
        $ueIds = array_unique(array_merge(array_column($d['ues'], 'ue_id'), array_column($d['elements'], 'ue_id')));
        if ($this->etat($ueIds) !== $proposition->etat) {
            throw new PropositionPerimee('Ces UE ou leurs éléments ont changé depuis la proposition.');
        }

        try {
            DB::transaction(function () use ($d): void {
                foreach ($d['ues'] as $op) {
                    $ue = ESBTPUniteEnseignement::findOrFail($op['ue_id']);
                    if ($op['attributs'] !== []) {
                        $ue->fill($op['attributs'] + ['updated_by' => auth()->id()])->save();
                    }
                    if ($op['rang'] !== null) {
                        $liens = DB::table('esbtp_lmd_parcours_ue')->where('unite_enseignement_id', $ue->id)
                            ->get(['parcours_id', 'semestre'])
                            ->map(fn ($l) => ['parcours_id' => (int) $l->parcours_id, 'semestre' => (int) $l->semestre]
                                + ((int) $l->parcours_id === $op['parcours_id'] ? ['ordre' => $op['rang']] : []))
                            ->all();
                        $this->sync->syncPourUnite($ue, $liens);
                    }
                }
                foreach ($d['elements'] as $op) {
                    $ue = ESBTPUniteEnseignement::findOrFail($op['ue_id']);
                    $op['matiere_id'] === null
                        ? $this->ecritures->ajouter($ue, $op['portee'], $op['valeurs'])
                        : $this->ecritures->modifier($ue, ESBTPMatiere::findOrFail($op['matiere_id']), $op['portee'], $op['valeurs']);
                }
            });
        } catch (ValidationException $e) {
            throw new PropositionPerimee(collect($e->errors())->flatten()->implode(' '));
        }

        return [
            'message' => sprintf('Maquette mise à jour : %d UE, %d élément(s).', count($d['ues']), count($d['elements'])),
            'lien' => route('esbtp.lmd.ue.index', [], false),
        ];
    }

    /** @return array{0: list<array>, 1: array<int, int>, 2: list<string>} les opérations, le crédit voulu par UE, les manques */
    private function preparerUes(array $ues, ?ESBTPLMDParcours $parcours): array
    {
        $ops = [];
        $credits = [];
        $manques = [];
        foreach ($ues as $u) {
            [$ue, $manque] = $this->unite((string) ($u['ue'] ?? ''));
            if (! $ue) {
                $manques[] = $manque;
                continue;
            }
            $attributs = [];
            $avant = [];
            $apres = [];
            if (isset($u['code']) && trim((string) $u['code']) !== '' && trim((string) $u['code']) !== $ue->code_affiche) {
                $code = trim((string) $u['code']);
                if (CodeDeMaquette::suffixe($ue->code) !== null) {
                    $manques[] = "L'UE {$ue->code_affiche} est propre à un parcours : change son code depuis l'écran des UE.";
                } elseif (str_contains($code, CodeDeMaquette::SEPARATEUR)) {
                    $manques[] = "Le code {$code} contient un caractère réservé.";
                } elseif (ESBTPUniteEnseignement::withTrashed()->where('id', '!=', $ue->id)
                    ->where(fn ($q) => $q->where('code', $code)->orWhere('code', 'like', $code . CodeDeMaquette::SEPARATEUR . '%'))->exists()) {
                    $manques[] = "Le code {$code} est déjà pris par une autre UE.";
                } else {
                    $attributs['code'] = $code;
                    [$avant[], $apres[]] = ['code ' . $ue->code_affiche, 'code ' . $code];
                }
            }
            if (isset($u['intitule']) && trim((string) $u['intitule']) !== '' && trim((string) $u['intitule']) !== $ue->name) {
                $attributs['name'] = trim((string) $u['intitule']);
                [$avant[], $apres[]] = [$ue->name, $attributs['name']];
            }
            if (isset($u['credit']) && (int) $u['credit'] !== (int) $ue->credit) {
                if ((int) $u['credit'] < 1 || (int) $u['credit'] > 60) {
                    $manques[] = "Crédit de l'UE {$ue->code_affiche} invalide : {$u['credit']}.";
                }
                $attributs['credit'] = (int) $u['credit'];
                [$avant[], $apres[]] = [$ue->credit . ' crédit(s)', $attributs['credit'] . ' crédit(s)'];
            }
            $credits[(int) $ue->id] = (int) ($attributs['credit'] ?? $ue->credit);

            $rang = null;
            if (isset($u['rang'])) {
                $actuel = $parcours ? DB::table('esbtp_lmd_parcours_ue')->where('unite_enseignement_id', $ue->id)
                    ->where('parcours_id', $parcours->id)->value('ordre') : null;
                if (! $parcours) {
                    $manques[] = "Pour quel parcours ranger l'UE {$ue->code_affiche} ?";
                } elseif ($actuel === null) {
                    $manques[] = "L'UE {$ue->code_affiche} n'est pas liée au parcours {$parcours->code} : lie-la d'abord.";
                } elseif ((int) $actuel !== (int) $u['rang']) {
                    $rang = (int) $u['rang'];
                    [$avant[], $apres[]] = ['rang ' . $actuel, 'rang ' . $rang];
                }
            }
            if ($attributs !== [] || $rang !== null) {
                $ops[] = ['ue_id' => (int) $ue->id, 'attributs' => $attributs, 'rang' => $rang, 'parcours_id' => $parcours?->id,
                    'ligne' => [(string) $ue->code_affiche, '—', implode(', ', $avant), implode(', ', $apres)]];
            }
        }

        return [$ops, $credits, $manques];
    }

    /** @return array{0: list<array>, 1: list<string>} */
    private function preparerElements(array $elements, ?ESBTPLMDParcours $parcours, array $credits): array
    {
        $ops = [];
        $manques = [];
        $ajouts = [];
        foreach ($elements as $e) {
            [$ue, $manque] = $this->unite((string) ($e['ue'] ?? ''));
            if (! $ue) {
                $manques[] = $manque;
                continue;
            }
            $designation = trim((string) ($e['element'] ?? ''));
            [$matiere, $portee, $manque] = $designation === ''
                ? [null, ! empty($e['reserve_au_parcours']) && $parcours ? (int) $parcours->id : CompositionUe::COMMUN, null]
                : $this->element($ue, $designation, $parcours);
            if ($manque) {
                $manques[] = $manque;
                continue;
            }
            $actuel = $matiere ? $this->ligne($ue, $matiere, $portee) : ['coefficient_ecue' => null, 'credit_ecue' => null, 'ordre_bulletin' => 0];
            // Tous les champs : EcritureEcue reécrit la ligne de pivot entière.
            $valeurs = [
                'name' => trim((string) ($e['intitule'] ?? '')) ?: $matiere?->name,
                'code' => trim((string) ($e['code'] ?? '')) ?: ($matiere?->code_affiche),
                'credit_ecue' => isset($e['credit']) ? (int) $e['credit'] : $actuel['credit_ecue'],
                'coefficient_ecue' => isset($e['coefficient']) ? (float) $e['coefficient'] : $actuel['coefficient_ecue'],
                'ordre_bulletin' => isset($e['ordre']) ? (int) $e['ordre'] : $actuel['ordre_bulletin'],
            ];
            if (! $matiere && (! $valeurs['name'] || ! $valeurs['code'] || ! $valeurs['credit_ecue'])) {
                $manques[] = "Pour ajouter un élément à l'UE {$ue->code_affiche} : code, intitulé et crédit.";
                continue;
            }
            $cle = $this->maquette->resoudreElement($ue, (string) $valeurs['code'], null, $portee === CompositionUe::COMMUN ? null : $portee, saufMatiereId: $matiere?->id)['cle'];
            if ($cle !== $matiere?->code && ESBTPMatiere::where('code', $cle)->when($matiere, fn ($q) => $q->where('id', '!=', $matiere->id))->exists()) {
                $manques[] = "Le code {$valeurs['code']} est déjà celui d'une autre matière.";
                continue;
            }
            $credit = (int) ($valeurs['credit_ecue'] ?? 0);
            $budget = $credits[(int) $ue->id] ?? (int) $ue->credit;
            // Le plafond se compte dans la maquette du parcours nommé : un élément commun
            // y côtoie ceux qui lui sont réservés.
            $maquette = $parcours ? (int) $parcours->id : $portee;
            $autres = $this->composition->creditsDe($ue, $maquette, $matiere ? [(int) $matiere->id] : []) + ($ajouts[$ue->id . ':' . $maquette] ?? 0);
            if ($budget > 0 && $credit > 0 && $autres + $credit > $budget) {
                $manques[] = "Les crédits des éléments de {$ue->code_affiche} dépasseraient ceux de l'UE ({$autres} + {$credit} > {$budget}) : ajuste le crédit de l'UE dans la même demande.";
                continue;
            }
            $ajouts[$ue->id . ':' . $maquette] = ($ajouts[$ue->id . ':' . $maquette] ?? 0) + ($matiere ? 0 : $credit);

            $avant = $matiere ? sprintf('%s · %s cr · coef %s · ordre %d', $matiere->code_affiche, $actuel['credit_ecue'] ?? '—', $actuel['coefficient_ecue'] ?? '—', $actuel['ordre_bulletin']) : '(nouveau)';
            $apres = sprintf('%s · %s cr · coef %s · ordre %d', $valeurs['code'], $valeurs['credit_ecue'] ?? '—', $valeurs['coefficient_ecue'] ?? '—', $valeurs['ordre_bulletin']);
            if ($matiere && $avant === $apres && $valeurs['name'] === $matiere->name) {
                continue;
            }
            $ops[] = ['ue_id' => (int) $ue->id, 'matiere_id' => $matiere ? (int) $matiere->id : null, 'portee' => $portee, 'valeurs' => $valeurs,
                'ligne' => [(string) $ue->code_affiche, (string) $valeurs['name'], $avant, $apres]];
        }

        return [$ops, $manques];
    }

    /** La ligne de pivot de la maquette, ou les colonnes de la matière à défaut. */
    private function ligne(ESBTPUniteEnseignement $ue, ESBTPMatiere $m, int $portee): array
    {
        $l = DB::table('esbtp_ue_matiere')->where(['unite_enseignement_id' => $ue->id, 'matiere_id' => $m->id, 'parcours_id' => $portee])->first();

        return [
            'credit_ecue' => $l?->credit_ecue ?? $m->credit_ecue,
            'coefficient_ecue' => $l?->coefficient_ecue ?? $m->coefficient_ecue,
            'ordre_bulletin' => (int) ($l?->ordre_bulletin ?? $m->ordre_bulletin ?? 0),
        ];
    }

    /** @param  array<int, int>  $ueIds */
    private function etat(array $ueIds): array
    {
        $ueIds = array_values(array_unique(array_map('intval', $ueIds)));
        sort($ueIds);

        return [
            'ues' => ESBTPUniteEnseignement::whereIn('id', $ueIds)->orderBy('id')->get(['id', 'code', 'name', 'credit'])->map(fn ($u) => [$u->id, $u->code, $u->name, (int) $u->credit])->all(),
            'liens' => DB::table('esbtp_lmd_parcours_ue')->whereIn('unite_enseignement_id', $ueIds)->orderBy('id')->get(['unite_enseignement_id', 'parcours_id', 'semestre', 'ordre'])->map(fn ($l) => (array) $l)->all(),
            'elements' => DB::table('esbtp_ue_matiere')->whereIn('unite_enseignement_id', $ueIds)->orderBy('id')
                ->get(['unite_enseignement_id', 'matiere_id', 'parcours_id', 'credit_ecue', 'coefficient_ecue', 'ordre_bulletin'])->map(fn ($l) => (array) $l)->all(),
        ];
    }

    /**
     * L'élément parmi ceux de l'unité, et la maquette où le modifier : celle du
     * parcours s'il y est réservé, la composition commune sinon.
     *
     * @return array{0: ?ESBTPMatiere, 1: int, 2: ?string}
     */
    private function element(ESBTPUniteEnseignement $ue, string $designation, ?ESBTPLMDParcours $parcours): array
    {
        [$m, $manque] = $this->elementDeLUnite($ue, $designation);
        if (! $m) {
            return [null, CompositionUe::COMMUN, $manque];
        }
        [$portee, $manque] = $this->porteeDeLElement($ue, $m, $parcours);

        return [$manque ? null : $m, $portee, $manque];
    }
}
