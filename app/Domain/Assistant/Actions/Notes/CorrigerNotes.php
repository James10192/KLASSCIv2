<?php

namespace App\Domain\Assistant\Actions\Notes;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Designations;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Domain\Notes\CorrectionDeNotes;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPNote;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Corriger des notes DÉJÀ saisies d'un étudiant (réclamation), puis recalculer
 * ses moyennes : CorrectionDeNotes, le même chemin que `POST /api/cli/notes/corriger`.
 * Une note est désignée par l'étudiant et l'évaluation ; une note absente se
 * saisit avec proposer_saisie_notes, jamais ici. Motif obligatoire.
 */
class CorrigerNotes extends ActionAgent
{
    use Designations;

    private const MAX = 60;

    public function __construct(private CorrectionDeNotes $correction)
    {
    }

    public function cle(): string
    {
        return 'correction_notes';
    }

    public function libelle(): string
    {
        return 'Préparation de la correction des notes…';
    }

    public function description(): string
    {
        return "PROPOSE de corriger des notes DÉJÀ saisies d'un étudiant (réclamation), puis recalcule ses moyennes. "
            . "Étudiant par matricule ou identifiant ; chaque note par l'identifiant de son évaluation (search_evaluations) et la nouvelle valeur sur le barème. "
            . "Motif obligatoire (la réclamation, la copie revue). Une note jamais saisie passe par proposer_saisie_notes.";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'etudiant_id' => ['type' => 'integer'],
                'matricule' => ['type' => 'string', 'description' => "Matricule exact de l'étudiant, si l'identifiant est inconnu."],
                'motif' => ['type' => 'string', 'description' => 'Pourquoi la note change, tel que dit par la personne (10 caractères au moins).'],
                'notes' => [
                    'type' => 'array',
                    'items' => ['type' => 'object', 'properties' => [
                        'evaluation_id' => ['type' => 'integer'],
                        'note' => ['type' => 'number', 'description' => "Nouvelle note sur le barème de l'évaluation."],
                        'note_annoncee' => ['type' => 'number', 'description' => "Note actuelle telle que la personne l'a donnée (« 12,50 au lieu de… »). Omettre si elle n'en a pas donné, ou après qu'elle a confirmé l'écart."],
                        'periode_annoncee' => ['type' => 'string', 'description' => "Semestre nommé par la personne : semestre1 ou semestre2. Omettre s'il n'a pas été nommé, ou après confirmation de l'écart."],
                    ], 'required' => ['evaluation_id', 'note']],
                ],
            ],
            'required' => ['motif', 'notes'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = 'Correction de notes';
        if (! $user->can('notes.edit')) {
            return $this->seulManque($titre, "Cet utilisateur n'a pas le droit de modifier des notes.");
        }
        [$etudiant, $manque] = $this->designerEtudiant($args);
        if (! $etudiant) {
            return $this->seulManque($titre, $manque);
        }
        $motif = trim((string) ($args['motif'] ?? ''));
        $lignes = array_values(array_filter((array) ($args['notes'] ?? []), 'is_array'));
        $manques = [];
        if (mb_strlen($motif) < 10) {
            $manques[] = 'Quel est le motif de la correction (réclamation, copie revue…) ? Il est journalisé.';
        }
        if ($lignes === [] || count($lignes) > self::MAX) {
            $manques[] = $lignes === [] ? 'Quelles notes corriger ?' : 'Trop de notes en une fois (' . self::MAX . ' au plus).';
        }

        $cibles = [];
        $annonces = [];
        foreach ($lignes as $l) {
            $evaluationId = (int) ($l['evaluation_id'] ?? 0);
            if (! is_numeric($l['note'] ?? null)) {
                $manques[] = "Évaluation #{$evaluationId} : quelle note ?";
                continue;
            }
            $notes = ESBTPNote::where('etudiant_id', $etudiant->id)->where('evaluation_id', $evaluationId)->get(['id']);
            if ($notes->count() !== 1) {
                $manques[] = $notes->isEmpty()
                    ? "Aucune note de {$this->nom($etudiant)} sur l'évaluation #{$evaluationId} : une note jamais saisie se saisit avec proposer_saisie_notes."
                    : "L'évaluation #{$evaluationId} porte {$notes->count()} notes pour {$this->nom($etudiant)} : à dédoublonner avant toute correction.";
                continue;
            }
            // « S1 », « 1 », « premier » : un semestre illisible n'est pas ignoré,
            // la garde ne doit pas sauter en silence.
            $periodeAnnoncee = null;
            if (trim((string) ($l['periode_annoncee'] ?? '')) !== '') {
                $brut = mb_strtolower(trim((string) $l['periode_annoncee']), 'UTF-8');
                $periodeAnnoncee = match (true) {
                    (bool) preg_match('/^(?:s|semestre\s*)?([12])$/u', $brut, $m) => 'semestre'.$m[1],
                    (bool) preg_match('/^(?:le\s+)?premier(?:\s+semestre)?$/u', $brut) => 'semestre1',
                    (bool) preg_match('/^(?:le\s+)?(?:second|deuxi[eè]me)(?:\s+semestre)?$/u', $brut) => 'semestre2',
                    default => \App\Models\ESBTPEvaluation::periodeCanonique($brut),
                };
                if (! in_array($periodeAnnoncee, ['semestre1', 'semestre2'], true)) {
                    $manques[] = "Évaluation #{$evaluationId} : quel semestre la personne a-t-elle nommé (semestre1 ou semestre2) ?";
                    continue;
                }
            }
            $cibles[] = ['note_id' => (int) $notes->first()->id, 'note' => round((float) $l['note'], 2)];
            $annonces[(int) $notes->first()->id] = [
                'note' => is_numeric($l['note_annoncee'] ?? null) ? round((float) $l['note_annoncee'], 2) : null,
                'periode' => $periodeAnnoncee,
            ];
        }
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: $manques);
        }

        try {
            $rapport = $this->correction->appliquer((int) $etudiant->id, $cibles, true, (int) $user->id)['lignes'];
        } catch (ValidationException $e) {
            return $this->seulManque($titre, collect($e->errors())->flatten()->implode(' '));
        }

        $ecarts = $this->ecartsAvecLAnnonce($rapport, $annonces);
        if ($ecarts !== []) {
            return new Proposition(titre: $titre, resume: '', manques: $ecarts);
        }

        $changees = array_values(array_filter($rapport, fn ($r) => $r['avant'] !== $r['apres']));
        if ($changees === []) {
            return $this->seulManque($titre, 'Rien à changer : ces notes ont déjà ces valeurs.');
        }
        $retenues = array_values(array_filter($cibles, fn ($c) => in_array($c['note_id'], array_column($changees, 'note_id'), true)));
        $absents = count(array_filter($changees, fn ($r) => $r['avant'] === 'absent'));

        return new Proposition(
            titre: 'Corriger les notes de ' . $this->nom($etudiant),
            resume: count($changees) . ' note(s) corrigée(s) pour ' . $this->nom($etudiant) . ' (' . $etudiant->matricule . '), puis moyennes recalculées. Motif : ' . $motif,
            tableau: [
                'colonnes' => ['Matière', 'Évaluation', 'Période', 'Avant', 'Après'],
                'lignes' => array_map(fn ($r) => [
                    (string) $r['matiere'], (string) $r['evaluation'], $this->libelleSemestre($r['periode']),
                    $r['avant'] === 'absent' ? 'Absent' : $this->nombre($r['avant']), $this->nombre($r['apres']),
                ], $changees),
            ],
            avertissements: array_values(array_filter([
                $absents > 0 ? "{$absents} note(s) marquée(s) absent deviendront des notes : l'absence est levée." : null,
                'Les moyennes de ces matières sont recalculées tout de suite ; un bulletin déjà généré garde sa moyenne tant qu\'il n\'est pas régénéré.',
            ])),
            donnees: ['etudiant_id' => (int) $etudiant->id, 'notes' => $retenues, 'motif' => $motif],
            etat: ['lignes' => $changees],
            risque: 'eleve',
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        if (! $user->can('notes.edit')) {
            throw new PropositionPerimee("Vous n'avez plus le droit de modifier des notes.");
        }
        $d = $proposition->donnees;
        try {
            if ($this->correction->appliquer((int) $d['etudiant_id'], $d['notes'], true, (int) $user->id)['lignes'] !== $proposition->etat['lignes']) {
                throw new PropositionPerimee('Ces notes ont changé depuis la proposition.');
            }
            $resultat = $this->correction->appliquer((int) $d['etudiant_id'], $d['notes'], false, (int) $user->id);
        } catch (ValidationException $e) {
            throw new PropositionPerimee(collect($e->errors())->flatten()->implode(' '));
        }

        Log::warning('assistant: notes corrigees', [
            'etudiant_id' => $d['etudiant_id'], 'motif' => $d['motif'],
            'lignes' => $resultat['lignes'], 'moyennes' => $resultat['moyennes'], 'user_id' => $user->id,
        ]);

        return [
            'message' => count($resultat['lignes']) . ' note(s) corrigée(s) et moyennes recalculées. Régénérez le bulletin pour qu\'il en tienne compte.',
            'lien' => route('esbtp.etudiants.show', $d['etudiant_id'], false),
            'model_type' => ESBTPEtudiant::class,
            'model_id' => (int) $d['etudiant_id'],
            'details' => ['moyennes' => $resultat['moyennes']],
        ];
    }

    private function nom(ESBTPEtudiant $e): string
    {
        return trim(mb_strtoupper((string) $e->nom, 'UTF-8') . ' ' . $e->prenoms);
    }

    /**
     * Ce que la personne a annoncé (« 12,50 au premier semestre ») contre ce
     * que la base porte. Un écart n'empêche pas la correction : il la suspend
     * jusqu'à ce que la personne le confirme. Constat du 2 octobre 2026 sur
     * presentation : seule une note de 15 au second semestre existait, et la
     * correction partait sur elle sans que personne le remarque.
     *
     * @param  array<int, array<string, mixed>>  $rapport
     * @param  array<int, array{note: ?float, periode: ?string}>  $annonces
     * @return list<string>
     */
    private function ecartsAvecLAnnonce(array $rapport, array $annonces): array
    {
        $ecarts = [];
        foreach ($rapport as $r) {
            $annonce = $annonces[(int) $r['note_id']] ?? null;
            if (! $annonce) {
                continue;
            }
            $quoi = "{$r['matiere']} ({$r['evaluation']})";
            $periode = \App\Models\ESBTPEvaluation::periodeCanonique((string) $r['periode']);
            if ($annonce['periode'] !== null && $annonce['periode'] !== $periode) {
                $ecarts[] = "{$quoi} est au {$this->libelleSemestre($periode)}, pas au {$this->libelleSemestre($annonce['periode'])} annoncé. Est-ce bien cette note-là ?";
            }
            $actuelle = $r['avant'] === 'absent' ? null : round((float) $r['avant'], 2);
            if ($annonce['note'] !== null && $actuelle !== $annonce['note']) {
                $lue = $actuelle === null ? 'absent' : $this->nombre($actuelle);
                $ecarts[] = "{$quoi} : la note actuelle en base est {$lue}, pas {$this->nombre($annonce['note'])} comme annoncé. Confirme avant de corriger.";
            }
        }

        return $ecarts;
    }

}
