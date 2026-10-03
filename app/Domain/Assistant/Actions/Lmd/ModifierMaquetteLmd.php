<?php

namespace App\Domain\Assistant\Actions\Lmd;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPUniteEnseignement;
use App\Services\LMD\CodeDeMaquette;
use App\Services\LMD\CodeDUnite;
use App\Services\LMD\CompositionUe;
use App\Services\LMD\EcritureEcue;
use App\Services\LMD\ParcoursUeSyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Aligner une maquette LMD sur un bulletin officiel : codes, crédits,
 * coefficients et ordre d'affichage des UE et de leurs éléments, et ajout d'un
 * élément manquant. Les gestes des modals de /esbtp/lmd/ue, par les mêmes
 * services : CodeDUnite pour le code d'une UE, EcritureEcue pour les éléments,
 * ParcoursUeSyncService pour le rang d'une UE dans un parcours.
 *
 * Le cas d'origine : ESBTP Abidjan, octobre 2026. Le L1 S1 de Bâtiment et
 * Travaux Publics a été recodé et réordonné d'après les bulletins officiels, et
 * « Initiation au génie civil » ajoutée.
 *
 * Une UE et ses éléments communs sont PARTAGÉS par les parcours qui les
 * utilisent. Quand un parcours est nommé, le crédit de l'UE se pose sur SA
 * maquette (`esbtp_lmd_parcours_ue.credit`, que lit son bulletin) dès que l'UE
 * sert d'autres parcours ; tout autre changement d'un objet partagé est dit.
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
        private CodeDUnite $codes,
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
                    'ue' => ['type' => 'string', 'description' => "Code imprimé ACTUEL ou identifiant de l'UE."],
                    'code' => ['type' => 'string', 'description' => 'Nouveau code.'],
                    'intitule' => ['type' => 'string'],
                    'credit' => ['type' => 'integer'],
                    'rang' => ['type' => 'integer', 'description' => "Rang de l'UE sur le bulletin du parcours, en ordre croissant : numérote toutes les UE du semestre (1, 2, 3…)."],
                ], 'required' => ['ue']]],
                'elements' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => [
                    'ue' => ['type' => 'string', 'description' => "Code imprimé ACTUEL ou identifiant de l'UE."],
                    'element' => ['type' => 'string', 'description' => "Code, intitulé exact ou identifiant de l'élément à modifier. Vide pour en AJOUTER un."],
                    'code' => ['type' => 'string'],
                    'intitule' => ['type' => 'string'],
                    'credit' => ['type' => 'integer'],
                    'coefficient' => ['type' => 'number'],
                    'ordre' => ['type' => 'integer', 'description' => "Ordre de l'élément dans son UE sur le bulletin, croissant : numérote tous les éléments de l'UE."],
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

        $parcours = null;
        if (($code = trim((string) ($args['parcours'] ?? ''))) !== '') {
            $parcours = ESBTPLMDParcours::whereRaw('UPPER(code) = ?', [mb_strtoupper($code)])->first();
            if (! $parcours) {
                return new Proposition(titre: $titre, resume: '', manques: ["Parcours inconnu : {$code}."]);
            }
        }

        [$opsUe, $m1] = $this->preparerUes($ues, $parcours);
        [$opsEl, $m2] = $this->preparerElements($elements, $parcours);
        $manques = array_merge($m1, $m2);
        if ($manques === []) {
            $manques = $this->depassementsDeCredits($opsUe, $opsEl, $parcours);
        }
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: $manques);
        }
        $lignes = array_merge(array_column($opsUe, 'ligne'), array_column($opsEl, 'ligne'));
        if ($lignes === []) {
            return Proposition::sansObjet($titre, 'La maquette porte déjà ces valeurs.');
        }
        $ueIds = array_values(array_unique(array_merge(array_column($opsUe, 'ue_id'), array_column($opsEl, 'ue_id'))));
        $sansLigne = fn (array $ops) => array_map(fn ($o) => array_diff_key($o, ['ligne' => 1, 'avertissement' => 1]), $ops);

        return new Proposition(
            titre: $titre . ($parcours ? " — {$parcours->code}" : ''),
            resume: sprintf('%d modification(s) sur %d UE.', count($lignes), count($ueIds)),
            tableau: ['colonnes' => ['UE', 'Élément', 'Avant', 'Après'], 'lignes' => $lignes],
            avertissements: array_values(array_unique(array_filter(array_merge(
                array_column($opsUe, 'avertissement'),
                array_column($opsEl, 'avertissement'),
                ['Les bulletins déjà générés gardent l\'ancienne maquette : régénérez-les.'],
            )))),
            donnees: ['ues' => $sansLigne($opsUe), 'elements' => $sansLigne($opsEl)],
            etat: $this->etat($ueIds),
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        // La fraîcheur (etat) est vérifiée par ExecutionDesPropositions, qui
        // reprépare la proposition et compare son empreinte avant d'appeler ici.
        $d = $proposition->donnees;

        try {
            DB::transaction(function () use ($d): void {
                foreach ($d['ues'] as $op) {
                    $this->ecrireUe($op);
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

    private function ecrireUe(array $op): void
    {
        $ue = ESBTPUniteEnseignement::findOrFail($op['ue_id']);
        if ($op['attributs'] !== []) {
            $ue->fill($op['attributs'] + ['updated_by' => auth()->id()])->save();
        }
        if ($op['credit_parcours'] !== null) {
            // Le crédit propre à CETTE maquette, comme l'import le grave
            // (LMDImportService::graverLesCreditsPropres) : la synchronisation
            // des liens ne touche jamais cette colonne.
            DB::table('esbtp_lmd_parcours_ue')
                ->where(['unite_enseignement_id' => $ue->id, 'parcours_id' => $op['parcours_id']])
                ->update(['credit' => $op['credit_parcours'], 'updated_at' => now()]);
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

    /** @return array{0: list<array>, 1: list<string>} les opérations et les manques */
    private function preparerUes(array $ues, ?ESBTPLMDParcours $parcours): array
    {
        $ops = [];
        $manques = [];
        foreach ($ues as $u) {
            [$ue, $manque] = $this->unite((string) ($u['ue'] ?? ''));
            if (! $ue) {
                $manques[] = $manque;
                continue;
            }
            $lie = $parcours && DB::table('esbtp_lmd_parcours_ue')->where(['unite_enseignement_id' => $ue->id, 'parcours_id' => $parcours->id])->exists();
            if ($parcours && ! $lie && (isset($u['rang']) || isset($u['credit']))) {
                $manques[] = "L'UE {$ue->code_affiche} n'est pas liée au parcours {$parcours->code} : lie-la d'abord.";
                continue;
            }
            $op = ['ue_id' => (int) $ue->id, 'parcours_id' => $parcours?->id, 'attributs' => [], 'credit_parcours' => null, 'rang' => null];
            $avant = [];
            $apres = [];

            $saisi = trim((string) ($u['code'] ?? ''));
            if ($saisi !== '' && $saisi !== $ue->code_affiche) {
                $code = $this->codes->resoudre($saisi, $ue, $parcours, false);
                if ($code['refus'] !== null) {
                    $manques[] = "UE {$ue->code_affiche} : {$code['refus']}";
                } else {
                    $op['attributs']['code'] = $code['cle'];
                    [$avant[], $apres[]] = ['code ' . $ue->code_affiche, 'code ' . $saisi];
                }
            }
            $nom = trim((string) ($u['intitule'] ?? ''));
            if ($nom !== '' && $nom !== $ue->name) {
                $op['attributs']['name'] = $nom;
                [$avant[], $apres[]] = [$ue->name, $nom];
            }
            if (isset($u['credit'])) {
                $nouveau = (int) $u['credit'];
                if ($nouveau < 0) {
                    $manques[] = "Crédit de l'UE {$ue->code_affiche} invalide : {$nouveau}.";
                }
                $propre = $lie ? $this->creditPropre($ue, (int) $parcours->id) : null;
                $actuel = $propre ?? (int) $ue->credit;
                if ($nouveau !== $actuel) {
                    // Sur la maquette du parcours si l'UE en sert d'autres ou porte déjà un crédit propre.
                    if ($lie && ($propre !== null || $this->autresParcours($ue, (int) $parcours->id) !== [])) {
                        $op['credit_parcours'] = $nouveau;
                    } else {
                        $op['attributs']['credit'] = $nouveau;
                    }
                    [$avant[], $apres[]] = [$actuel . ' crédit(s)', $nouveau . ' crédit(s)'];
                }
            }
            if (isset($u['rang'])) {
                if (! $parcours) {
                    $manques[] = "Pour quel parcours ranger l'UE {$ue->code_affiche} ?";
                } else {
                    $actuel = (int) DB::table('esbtp_lmd_parcours_ue')->where(['unite_enseignement_id' => $ue->id, 'parcours_id' => $parcours->id])->value('ordre');
                    if ($actuel !== (int) $u['rang']) {
                        $op['rang'] = (int) $u['rang'];
                        [$avant[], $apres[]] = ['rang ' . $actuel, 'rang ' . $op['rang']];
                    }
                }
            }
            if ($op['attributs'] !== [] || $op['credit_parcours'] !== null || $op['rang'] !== null) {
                $partage = $op['attributs'] !== [] ? $this->autresParcours($ue, $parcours?->id) : [];
                $op['avertissement'] = count($partage) >= ($parcours ? 1 : 2)
                    ? sprintf('L\'UE %s sert aussi %s : %s change aussi pour eux.', $ue->code_affiche, implode(', ', $partage),
                        implode(', ', array_map(fn ($k) => ['code' => 'son code', 'name' => 'son intitulé', 'credit' => 'son crédit'][$k], array_keys($op['attributs']))))
                    : null;
                $op['ligne'] = [(string) $ue->code_affiche, '—', implode(', ', $avant), implode(', ', $apres)];
                $ops[] = $op;
            }
        }

        return [$ops, $manques];
    }

    /** @return array{0: list<array>, 1: list<string>} */
    private function preparerElements(array $elements, ?ESBTPLMDParcours $parcours): array
    {
        $ops = [];
        $manques = [];
        foreach ($elements as $e) {
            [$ue, $manque] = $this->unite((string) ($e['ue'] ?? ''));
            if (! $ue) {
                $manques[] = $manque;
                continue;
            }
            $designation = trim((string) ($e['element'] ?? ''));
            [$matiere, $portee, $manque] = $designation === ''
                ? $this->porteeDUnAjout($ue, $parcours, ! empty($e['reserve_au_parcours']))
                : $this->element($ue, $designation, $parcours);
            if ($manque) {
                $manques[] = $manque;
                continue;
            }
            $actuel = $matiere ? $this->ligne($ue, $matiere, $portee) : ['coefficient_ecue' => null, 'credit_ecue' => null, 'ordre_bulletin' => 0];
            // Tous les champs : EcritureEcue réécrit la ligne de pivot entière.
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

            $avant = $matiere ? sprintf('%s · %s cr · coef %s · ordre %d', $matiere->code_affiche, $actuel['credit_ecue'] ?? '—', $actuel['coefficient_ecue'] ?? '—', $actuel['ordre_bulletin']) : '(nouveau)';
            $apres = sprintf('%s · %s cr · coef %s · ordre %d', $valeurs['code'], $valeurs['credit_ecue'] ?? '—', $valeurs['coefficient_ecue'] ?? '—', $valeurs['ordre_bulletin']);
            if ($matiere && $avant === $apres && $valeurs['name'] === $matiere->name) {
                continue;
            }
            $partage = $portee === CompositionUe::COMMUN ? $this->autresParcours($ue, $parcours?->id) : [];
            $ops[] = ['ue_id' => (int) $ue->id, 'matiere_id' => $matiere ? (int) $matiere->id : null, 'portee' => $portee, 'valeurs' => $valeurs,
                'ligne' => [(string) $ue->code_affiche, (string) $valeurs['name'], $avant, $apres],
                'avertissement' => count($partage) >= ($parcours ? 1 : 2)
                    ? sprintf('« %s » est commun à l\'UE %s : le changement vaut aussi pour %s.', $valeurs['name'], $ue->code_affiche, implode(', ', $partage))
                    : null];
        }

        return [$ops, $manques];
    }

    /**
     * Après la demande entière, chaque maquette touchée tient-elle dans le crédit
     * de son UE ? Les modifications d'une même demande s'additionnent : deux
     * hausses contrôlées chacune contre l'ancien total passeraient ensemble.
     *
     * @return list<string>
     */
    private function depassementsDeCredits(array $opsUe, array $opsEl, ?ESBTPLMDParcours $parcours): array
    {
        $parUe = [];
        foreach ($opsUe as $op) {
            $parUe[$op['ue_id']]['ue'] = $op;
        }
        foreach ($opsEl as $op) {
            $parUe[$op['ue_id']]['elements'][] = $op;
        }

        $manques = [];
        foreach ($parUe as $ueId => $touche) {
            $ue = ESBTPUniteEnseignement::findOrFail($ueId);
            $elements = $touche['elements'] ?? [];
            $maquettes = array_unique(array_merge(
                [$parcours && DB::table('esbtp_lmd_parcours_ue')->where(['unite_enseignement_id' => $ueId, 'parcours_id' => $parcours->id])->exists() ? (int) $parcours->id : CompositionUe::COMMUN],
                array_filter(array_column($elements, 'portee')),
            ));
            $exclus = array_values(array_filter(array_column($elements, 'matiere_id')));
            foreach ($maquettes as $m) {
                $budget = $this->budget($ue, $m, $touche['ue'] ?? null);
                $somme = $this->composition->creditsDe($ue, $m, $exclus) + array_sum(array_map(
                    fn ($op) => in_array($op['portee'], [CompositionUe::COMMUN, $m], true) ? (int) ($op['valeurs']['credit_ecue'] ?? 0) : 0,
                    $elements,
                ));
                if ($budget > 0 && $somme > $budget) {
                    $manques[] = "Les crédits des éléments de {$ue->code_affiche} dépasseraient ceux de l'UE ({$somme} > {$budget}) : "
                        . 'ajuste les crédits des éléments ou celui de l\'UE dans la même demande.';
                }
            }
        }

        return $manques;
    }

    /** Le crédit de l'UE dans cette maquette, après la demande. */
    private function budget(ESBTPUniteEnseignement $ue, int $maquette, ?array $op): int
    {
        $fiche = (int) ($op['attributs']['credit'] ?? $ue->credit);
        if ($maquette === CompositionUe::COMMUN) {
            return $fiche;
        }
        if ($op !== null && $op['credit_parcours'] !== null && (int) $op['parcours_id'] === $maquette) {
            return (int) $op['credit_parcours'];
        }

        return $this->creditPropre($ue, $maquette) ?? $fiche;
    }

    private function creditPropre(ESBTPUniteEnseignement $ue, int $parcoursId): ?int
    {
        $credit = DB::table('esbtp_lmd_parcours_ue')->where(['unite_enseignement_id' => $ue->id, 'parcours_id' => $parcoursId])
            ->whereNotNull('credit')->value('credit');

        return $credit === null ? null : (int) $credit;
    }

    /** @return array{0: null, 1: int, 2: ?string} */
    private function porteeDUnAjout(ESBTPUniteEnseignement $ue, ?ESBTPLMDParcours $parcours, bool $reserve): array
    {
        if (! $reserve) {
            return [null, CompositionUe::COMMUN, null];
        }
        if (! $parcours) {
            return [null, CompositionUe::COMMUN, "À quel parcours réserver le nouvel élément de {$ue->code_affiche} ?"];
        }
        // Le même contrôle que la fenêtre ECUE : un parcours qui n'utilise pas l'UE
        // créerait une composition qu'aucune maquette ne montre.
        $portee = $this->composition->porteeValide($ue, $parcours->id);

        return $portee === (int) $parcours->id
            ? [null, $portee, null]
            : [null, $portee, "L'UE {$ue->code_affiche} n'est pas liée au parcours {$parcours->code} : impossible d'y réserver un élément."];
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
        sort($ueIds);

        return [
            'ues' => ESBTPUniteEnseignement::whereIn('id', $ueIds)->orderBy('id')->get(['id', 'code', 'name', 'credit'])->map(fn ($u) => [$u->id, $u->code, $u->name, (int) $u->credit])->all(),
            'liens' => DB::table('esbtp_lmd_parcours_ue')->whereIn('unite_enseignement_id', $ueIds)->orderBy('id')->get(['unite_enseignement_id', 'parcours_id', 'semestre', 'ordre', 'credit'])->map(fn ($l) => (array) $l)->all(),
            'elements' => DB::table('esbtp_ue_matiere')->whereIn('unite_enseignement_id', $ueIds)->orderBy('id')
                ->get(['unite_enseignement_id', 'matiere_id', 'parcours_id', 'credit_ecue', 'coefficient_ecue', 'ordre_bulletin'])->map(fn ($l) => (array) $l)->all(),
        ];
    }

    /**
     * L'élément parmi ceux de l'unité, et la maquette où le modifier.
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
