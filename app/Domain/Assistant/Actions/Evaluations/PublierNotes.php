<?php

namespace App\Domain\Assistant\Actions\Evaluations;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use Illuminate\Support\Facades\DB;

/**
 * Nanan prépare la publication des notes d'une ou plusieurs évaluations — le
 * bouton « Publier les notes » de la liste des évaluations, évaluation par
 * évaluation. Publier rend les notes visibles des étudiants et des parents.
 *
 * Même règle que l'écran ({@see ESBTPEvaluation::canPublishNotes()}) : seule
 * une évaluation terminée et qui porte des notes se publie. Une évaluation qui
 * ne le permet pas n'est pas ignorée en silence : elle bloque la proposition,
 * avec la raison, pour que la personne sache ce qui n'est pas parti.
 *
 * Un enseignant qui ne coordonne pas ne publie que ses évaluations (celles
 * qu'il a créées ou qui le nomment).
 */
final class PublierNotes extends ActionAgent
{
    private const MAX_EVALUATIONS = 50;

    public function cle(): string
    {
        return 'publication_notes';
    }

    public function description(): string
    {
        return "PROPOSE de publier les notes d'une ou plusieurs évaluations (identifiants rendus par search_evaluations) : "
            . "les étudiants et les parents les verront. Seule une évaluation terminée et notée se publie. "
            . "Rien n'est publié avant le clic Valider. Ne l'utilise que si l'utilisateur demande explicitement de publier.";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'evaluation_ids' => [
                    'type' => 'array',
                    'items' => ['type' => 'integer'],
                    'description' => 'Identifiants des évaluations dont publier les notes (' . self::MAX_EVALUATIONS . ' au plus).',
                ],
            ],
            'required' => ['evaluation_ids'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $ids = collect((array) ($args['evaluation_ids'] ?? []))
            ->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->unique()->values();
        if ($ids->isEmpty() || $ids->count() > self::MAX_EVALUATIONS) {
            return $this->manque($ids->isEmpty()
                ? 'Aucune évaluation désignée : retrouve-les avec search_evaluations.'
                : 'Trop d\'évaluations en une fois (' . self::MAX_EVALUATIONS . ' au plus).');
        }

        $evaluations = ESBTPEvaluation::with(['classe:id,name', 'matiere:id,name'])
            ->withCount(['notes', 'notes as notes_brouillon_count' => fn ($q) => $q->where('submission_status', ESBTPNote::SUBMISSION_DRAFT)])
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $manques = [];
        $lignes = [];
        $etat = [];
        $brouillons = 0;
        foreach ($ids as $id) {
            $e = $evaluations->get($id);
            if (! $e) {
                $manques[] = "Évaluation n° {$id} introuvable.";
                continue;
            }
            $nom = $this->intitule($e);
            if (! $this->peutPublier($user, $e)) {
                $manques[] = "{$nom} : vous ne pouvez publier que les notes de vos évaluations.";
                continue;
            }
            if ($e->notes_published) {
                $manques[] = "{$nom} : notes déjà publiées.";
                continue;
            }
            if (! $e->canPublishNotes()) {
                $manques[] = $e->notes_count === 0
                    ? "{$nom} : aucune note saisie, rien à publier."
                    : "{$nom} : l'évaluation n'est pas terminée (statut « {$e->status_label} »), ses notes ne peuvent pas encore être publiées.";
                continue;
            }

            $brouillons += (int) $e->notes_brouillon_count;
            $lignes[] = [
                (string) ($e->classe?->name ?? '—'),
                (string) ($e->matiere?->name ?? '—'),
                (string) $e->titre,
                $e->date_evaluation?->format('d/m/Y') ?? '—',
                (string) $e->notes_count,
            ];
            $etat[$id] = [
                'notes_published' => (bool) $e->notes_published,
                'status' => (string) $e->status,
                'notes' => (int) $e->notes_count,
            ];
        }

        return new Proposition(
            titre: 'Publier les notes',
            resume: count($lignes) . ' évaluation(s) : leurs notes deviendront visibles des étudiants et des parents.',
            tableau: ['colonnes' => ['Classe', 'Matière', 'Évaluation', 'Date', 'Notes'], 'lignes' => $lignes],
            manques: $manques,
            avertissements: $brouillons > 0
                ? ["{$brouillons} note(s) sont encore en brouillon : elles seront visibles comme les autres une fois publiées."]
                : [],
            donnees: ['evaluation_ids' => array_map('intval', array_keys($etat))],
            etat: $etat,
            risque: 'moyen',
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        $ids = $proposition->donnees['evaluation_ids'];

        DB::transaction(function () use ($ids, $user): void {
            $evaluations = ESBTPEvaluation::whereIn('id', $ids)->lockForUpdate()->get();
            if ($evaluations->count() !== count($ids) || $evaluations->contains(fn (ESBTPEvaluation $e) => $e->notes_published)) {
                throw new PropositionPerimee('Une de ces évaluations a changé entre-temps.');
            }

            // Même écriture que le bouton de l'écran : le modèle est enregistré
            // (pas de mise à jour de masse), donc chaque publication est auditée.
            foreach ($evaluations as $e) {
                if (! $e->is_published) {
                    $e->is_published = true;
                    if ($e->status !== ESBTPEvaluation::STATUS_CANCELLED) {
                        $e->status = $e->determineAutomaticStatus(null, false);
                    }
                }
                $e->notes_published = true;
                $e->updated_by = $user->id;
                $e->save();
            }
        });

        $n = count($ids);

        return [
            'message' => "Notes publiées pour {$n} évaluation(s).",
            'lien' => $n === 1
                ? route('esbtp.evaluations.show', $ids[0], false)
                : route('esbtp.evaluations.index', [], false),
            'model_type' => ESBTPEvaluation::class,
            'model_id' => $n === 1 ? (int) $ids[0] : null,
            'details' => ['evaluation_ids' => $ids],
        ];
    }

    private function peutPublier($user, ESBTPEvaluation $e): bool
    {
        if (! $user->can('identity.teach') || $user->can('identity.coordinate')) {
            return true;
        }

        return (int) $e->enseignant_id === (int) $user->id || (int) $e->created_by === (int) $user->id;
    }

    private function intitule(ESBTPEvaluation $e): string
    {
        return '« ' . $e->titre . ' » (' . ($e->classe?->name ?? 'classe ?') . ', ' . ($e->matiere?->name ?? 'matière ?') . ')';
    }

    private function manque(string $message): Proposition
    {
        return new Proposition(titre: 'Publier les notes', resume: '', manques: [$message]);
    }
}
