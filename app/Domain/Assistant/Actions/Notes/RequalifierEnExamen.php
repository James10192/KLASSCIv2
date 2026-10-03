<?php

namespace App\Domain\Assistant\Actions\Notes;

use App\Domain\Academique\CoherenceSystemeAcademique;
use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Designations;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Domain\Notes\RequalificationEnExamen;
use App\Models\ESBTPClasse;
use Illuminate\Validation\ValidationException;

/**
 * Faire des régularisations d'un relevé les examens qu'elles étaient :
 * RequalificationEnExamen, le même chemin que le bandeau de l'écran des notes LMD.
 *
 * Le cas d'origine : ESBTP Abidjan, octobre 2026. Les notes d'avril 2026 avaient
 * été saisies en « Régularisation » avant que l'école précise que c'étaient les
 * notes d'examen, le contrôle continu venant à part.
 */
class RequalifierEnExamen extends ActionAgent
{
    use Designations;

    public function __construct(private RequalificationEnExamen $requalification)
    {
    }

    public function cle(): string
    {
        return 'requalification_examen';
    }

    public function libelle(): string
    {
        return 'Recherche des régularisations à requalifier…';
    }

    public function description(): string
    {
        return "PROPOSE de requalifier en examen les évaluations « Régularisation SEMESTREn — … » d'une classe LMD : "
            . 'elles deviennent « Examen SEMESTREn — … », de type examen. Aucune note ne change. '
            . "À utiliser quand l'école dit que des notes saisies par relevé étaient les notes d'examen. Rien n'est écrit avant « Valider ».";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'classe' => ['type' => 'string', 'description' => 'Code, nom exact ou identifiant de la classe LMD.'],
                'annee' => ['type' => 'string', 'description' => "Année universitaire, ex. « 2025-2026 ». Par défaut l'année en cours."],
                'semestre' => ['type' => 'string', 'description' => 'Limiter à un semestre (S1, S2…). Vide : tous.'],
            ],
            'required' => ['classe'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = 'Requalifier en examen';
        if ($refus = $this->refusDroits($user)) {
            return $this->seulManque($titre, $refus);
        }
        [$classe, $manque] = $this->designerClasse($args['classe'] ?? '');
        if (! $classe) {
            return $this->seulManque($titre, $manque);
        }
        if (! CoherenceSystemeAcademique::classeEstLmd($classe->systeme_academique)) {
            return $this->seulManque($titre, "{$classe->name} n'est pas une classe LMD.");
        }
        [$annee, $manque] = $this->designerAnnee($args);
        if (! $annee) {
            return $this->seulManque($titre, $manque);
        }
        $periode = null;
        if (trim((string) ($args['semestre'] ?? '')) !== '') {
            if (! preg_match('/(\d{1,2})\s*$/', (string) $args['semestre'], $m) || ! in_array((int) $m[1], $classe->getSemestresLMD(), true)) {
                return $this->seulManque($titre, "Quel semestre ? Pour {$classe->name} : "
                    . implode(' ou ', array_map(fn ($s) => "S{$s}", $classe->getSemestresLMD())) . '.');
            }
            $periode = 'semestre' . (int) $m[1];
        }

        $aRequalifier = array_filter($this->requalification->inventaire($classe, (int) $annee->id),
            fn (array $l) => $periode === null || $l['periode'] === $periode);
        if ($aRequalifier === []) {
            return Proposition::sansObjet($titre, "Aucune régularisation à requalifier pour {$classe->name} en {$annee->name}.");
        }

        try {
            $lignes = $this->requalification->appliquer($classe, (int) $annee->id, $periode, true, (int) $user->id);
        } catch (ValidationException $e) {
            return $this->seulManque($titre, collect($e->errors())->flatten()->implode(' '));
        }

        return new Proposition(
            titre: "Requalifier en examen — {$classe->name} {$annee->name}",
            resume: sprintf('%d évaluation(s) de régularisation (%d note(s)) deviennent des examens. Aucune note ne change.',
                count($lignes), array_sum(array_column($lignes, 'notes'))),
            tableau: [
                'colonnes' => ['Avant', 'Après', 'Notes'],
                'lignes' => array_map(fn ($l) => [$l['titre'], $l['nouveau_titre'], (string) $l['notes']], $lignes),
            ],
            avertissements: ['Le type ne change pas la moyenne : le bulletin LMD compte chaque évaluation selon son coefficient.'],
            donnees: ['classe_id' => (int) $classe->id, 'annee_universitaire_id' => (int) $annee->id, 'periode' => $periode],
            etat: ['lignes' => $lignes],
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        if ($refus = $this->refusDroits($user)) {
            throw new PropositionPerimee($refus);
        }
        $d = $proposition->donnees;
        $classe = ESBTPClasse::find((int) $d['classe_id']) ?? throw new PropositionPerimee("Cette classe n'existe plus.");

        try {
            if ($this->requalification->appliquer($classe, (int) $d['annee_universitaire_id'], $d['periode'], true, (int) $user->id) !== $proposition->etat['lignes']) {
                throw new PropositionPerimee('Les évaluations de cette classe ont changé depuis la proposition.');
            }
            $lignes = $this->requalification->appliquer($classe, (int) $d['annee_universitaire_id'], $d['periode'], false, (int) $user->id);
        } catch (ValidationException $e) {
            throw new PropositionPerimee(collect($e->errors())->flatten()->implode(' '));
        }

        return [
            'message' => sprintf('%d évaluation(s) requalifiée(s) en examen pour %s.', count($lignes), $classe->name),
            'lien' => route('esbtp.lmd.notes.index', [], false),
            'model_type' => ESBTPClasse::class,
            'model_id' => (int) $classe->id,
        ];
    }

    /** Mêmes conditions que l'écran : les deux permissions, et pas un enseignant seul. */
    private function refusDroits($user): ?string
    {
        if (! $user->can('lmd.notes.manage') || ! $user->can('evaluations.edit')) {
            return "Cet utilisateur n'a pas le droit de modifier les évaluations LMD.";
        }
        if ($user->can('identity.teach') && ! $user->can('identity.coordinate')) {
            return "La requalification des évaluations est réservée à l'administration.";
        }

        return null;
    }
}
