<?php

namespace App\Domain\Assistant\Actions\Notes;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Notes\SaisieGroupeeDeNotes;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Services\Notes\NoteStudentCohortService;
use Illuminate\Support\Str;

/**
 * L'assistant propose des notes pour une évaluation ; l'enseignant (ou la
 * scolarité) les relit et valide. Mêmes gardes que l'écran de saisie, par
 * SaisieGroupeeDeNotes : publication, droit sur l'évaluation, fenêtre de
 * saisie, cohorte, barème, note déjà validée.
 *
 * Un étudiant est reconnu par son matricule, ou par son nom complet (dans un
 * ordre ou dans l'autre). Un nom qui désigne zéro ou plusieurs étudiants de la
 * classe n'est jamais deviné : c'est un manque, et l'assistant doit demander.
 */
class SaisirNotes extends ActionAgent
{
    private const MAX_LIGNES = 200;

    public function __construct(
        private SaisieGroupeeDeNotes $saisie,
        private NoteStudentCohortService $cohorte,
    ) {
    }

    public function cle(): string
    {
        return 'saisie_notes';
    }

    public function libelle(): string
    {
        return 'Préparation des notes à enregistrer…';
    }

    public function description(): string
    {
        return "PROPOSE des notes pour UNE évaluation (retrouvée d'abord avec search_evaluations, qui donne son id). "
            . "N'enregistre rien : l'utilisateur voit le tableau et valide. Chaque étudiant est désigné par son matricule "
            . "ou son nom complet exactement comme donné par l'utilisateur ; n'invente ni nom, ni note. Absent = absent: true, sans note. "
            . "Si le résultat contient des manques, pose la question à l'utilisateur au lieu de corriger toi-même.";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'evaluation_id' => ['type' => 'integer', 'description' => "Identifiant de l'évaluation (id rendu par search_evaluations)."],
                'notes' => [
                    'type' => 'array',
                    'description' => 'Une entrée par étudiant.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'etudiant' => ['type' => 'string', 'description' => 'Matricule, ou nom et prénoms, tels que donnés par l\'utilisateur.'],
                            'note' => ['type' => 'number', 'description' => 'Note sur le barème de l\'évaluation. Omettre si absent.'],
                            'absent' => ['type' => 'boolean'],
                        ],
                        'required' => ['etudiant'],
                    ],
                ],
                'valider' => ['type' => 'boolean', 'description' => 'true seulement si l\'utilisateur demande de VALIDER (soumettre) les notes ; sinon brouillon.'],
            ],
            'required' => ['evaluation_id', 'notes'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $evaluation = ESBTPEvaluation::with(['classe', 'matiere'])->find((int) ($args['evaluation_id'] ?? 0));
        if (! $evaluation) {
            return $this->manque("Évaluation introuvable : retrouve-la avec search_evaluations et donne son id.");
        }
        if (! $this->saisie->peutGerer($user, $evaluation)) {
            return $this->manque("Cet utilisateur ne peut pas saisir de notes sur cette évaluation (droits ou période de saisie close).");
        }

        $bareme = (float) ($evaluation->bareme ?: 20);
        $lignes = array_values(array_filter((array) ($args['notes'] ?? []), 'is_array'));
        if ($lignes === []) {
            return $this->manque('Aucune note fournie.');
        }
        if (count($lignes) > self::MAX_LIGNES) {
            return $this->manque('Trop de lignes en une fois (' . self::MAX_LIGNES . ' au plus).');
        }

        $etudiants = $this->cohorte->studentsForEvaluation($evaluation);
        $manques = [];
        $entrees = [];
        $vus = [];

        foreach ($lignes as $i => $ligne) {
            $designation = trim((string) ($ligne['etudiant'] ?? ''));
            [$trouves, $proches] = $this->reconnaitre($designation, $etudiants);
            if (count($trouves) !== 1) {
                $liste = fn (array $es) => implode(', ', array_map(fn ($e) => $this->nom($e) . ' (' . $e->matricule . ')', array_slice($es, 0, 5)));
                $manques[] = match (true) {
                    $designation === '' => 'Ligne ' . ($i + 1) . ' : étudiant non précisé.',
                    count($trouves) > 1 => "« {$designation} » désigne plusieurs étudiants : " . $liste($trouves) . '. Lequel ?',
                    $proches !== [] => "« {$designation} » ne correspond exactement à personne ; plusieurs étudiants possibles : " . $liste($proches) . '. Lequel ?',
                    default => "« {$designation} » : aucun étudiant de cette classe ne correspond. Demande le matricule.",
                };
                continue;
            }
            $etudiant = $trouves[0];
            if (isset($vus[$etudiant->id])) {
                $manques[] = $this->nom($etudiant) . ' apparaît deux fois : laquelle garder ?';
                continue;
            }
            $vus[$etudiant->id] = true;

            $absent = filter_var($ligne['absent'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $note = $ligne['note'] ?? null;
            if (! $absent) {
                if (! is_numeric($note)) {
                    $manques[] = $this->nom($etudiant) . ' : note absente. Donne la note ou dis s\'il était absent.';
                    continue;
                }
                $note = round((float) $note, 2);
                if ($note < 0 || $note > $bareme) {
                    $manques[] = $this->nom($etudiant) . " : {$note} n'est pas entre 0 et " . $this->nombre($bareme) . ' (barème).';
                    continue;
                }
            }

            $entrees[] = [
                'etudiant_id' => (int) $etudiant->id,
                'evaluation_id' => (int) $evaluation->id,
                'note' => $absent ? null : $note,
                'is_absent' => $absent,
            ];
        }

        $analyse = $entrees === [] ? [] : $this->saisie->analyser($entrees, $user);
        $tableau = [];
        $etat = [];
        $parId = $etudiants->keyBy('id');
        $compte = ['creation' => 0, 'modification' => 0, 'inchange' => 0];

        foreach ($analyse as $a) {
            $etudiant = $parId->get($a['entree']['etudiant_id']);
            if ($a['statut'] === 'refus') {
                $manques[] = $this->nom($etudiant) . ' : ' . $a['raison'] . '.';
                continue;
            }
            $compte[$a['statut']]++;
            $etat[$a['entree']['etudiant_id']] = $a['avant'];
            $tableau[] = [
                $this->nom($etudiant),
                (string) $etudiant->matricule,
                $this->affichage($a['avant']),
                $a['entree']['is_absent'] ? 'Absent' : $this->nombre($a['entree']['note']),
                ['creation' => 'Nouvelle', 'modification' => 'Remplace', 'inchange' => 'Inchangée'][$a['statut']],
            ];
        }

        $avertissements = [];
        $restants = $etudiants->reject(fn ($e) => isset($vus[$e->id]));
        $dejaNotes = $this->saisie->notesExistantes($restants->map(fn ($e) => ['etudiant_id' => $e->id, 'evaluation_id' => $evaluation->id])->values()->all());
        $sansNote = $restants->reject(fn ($e) => $dejaNotes->has($e->id . '_' . $evaluation->id));
        if ($sansNote->isNotEmpty()) {
            $avertissements[] = $sansNote->count() . ' étudiant(s) de la classe resteront sans note : '
                . $sansNote->take(5)->map(fn ($e) => $this->nom($e))->implode(', ') . ($sansNote->count() > 5 ? '…' : '') . '.';
        }
        if ($compte['modification'] > 0) {
            $avertissements[] = $compte['modification'] . ' note(s) déjà saisie(s) seront remplacées.';
        }
        $valider = filter_var($args['valider'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if ($valider) {
            $avertissements[] = 'Les notes seront VALIDÉES (soumises), pas laissées en brouillon.';
        }

        $intitule = trim(($evaluation->titre ?: 'Évaluation') . ' — ' . ($evaluation->matiere?->name ?? '') . ' — ' . ($evaluation->classe?->name ?? ''), ' —');

        return new Proposition(
            titre: 'Notes : ' . $intitule,
            resume: sprintf('%d nouvelle(s), %d remplacée(s), %d inchangée(s), sur %s. %s.',
                $compte['creation'], $compte['modification'], $compte['inchange'], $this->nombre($bareme), $valider ? 'Validation finale' : 'Brouillon'),
            tableau: ['colonnes' => ['Étudiant', 'Matricule', 'Avant', 'Après', 'Effet'], 'lignes' => $tableau],
            manques: $manques,
            avertissements: $avertissements,
            donnees: ['evaluation_id' => (int) $evaluation->id, 'entrees' => $entrees, 'valider' => $valider],
            etat: $etat,
            risque: $compte['modification'] > 0 || $valider ? 'eleve' : 'moyen',
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        $resultat = $this->saisie->enregistrer($proposition->donnees['entrees'], $user, (bool) $proposition->donnees['valider']);
        if ($resultat['refused'] !== []) {
            // L'état a été vérifié à l'instant ; un refus ici est une course : rien n'est à moitié écrit.
            throw new \RuntimeException(count($resultat['refused']) . ' note(s) refusée(s) à l\'enregistrement.');
        }

        return [
            'message' => $resultat['saved'] . ' note(s) enregistrée(s)' . ($proposition->donnees['valider'] ? ' et validée(s)' : ' en brouillon') . '.',
            'lien' => route('esbtp.notes.saisie-rapide', ['evaluation' => $proposition->donnees['evaluation_id']], false),
            'model_type' => ESBTPEvaluation::class,
            'model_id' => (int) $proposition->donnees['evaluation_id'],
            'details' => ['enregistrees' => $resultat['saved']],
        ];
    }

    /**
     * Matricule exact, sinon nom complet exact (mots dans n'importe quel ordre).
     * Les « proches » (tous les mots donnés figurent dans le nom) ne sont JAMAIS
     * retenus : ils servent seulement à poser la bonne question.
     *
     * @return array{0: ESBTPEtudiant[], 1: ESBTPEtudiant[]}
     */
    private function reconnaitre(string $designation, $etudiants): array
    {
        if ($designation === '') {
            return [[], []];
        }
        $cle = $this->normaliser($designation);
        $parMatricule = $etudiants->filter(fn ($e) => $this->normaliser((string) $e->matricule) === $cle)->values()->all();
        if ($parMatricule !== []) {
            return [$parMatricule, []];
        }

        $mots = collect(explode(' ', $cle))->filter()->sort()->values();
        $exacts = [];
        $proches = [];
        foreach ($etudiants as $e) {
            $siens = collect(explode(' ', $this->normaliser($e->nom . ' ' . $e->prenoms)))->filter()->sort()->values();
            if ($siens->all() === $mots->all()) {
                $exacts[] = $e;
            } elseif ($mots->diff($siens)->isEmpty()) {
                $proches[] = $e;
            }
        }

        return [$exacts, $proches];
    }

    private function normaliser(string $texte): string
    {
        return trim(preg_replace('/\s+/', ' ', preg_replace('/[^a-z0-9 ]/', ' ', Str::lower(Str::ascii($texte)))));
    }

    private function nom(?ESBTPEtudiant $e): string
    {
        return $e ? trim(mb_strtoupper((string) $e->nom, 'UTF-8') . ' ' . $e->prenoms) : 'Étudiant';
    }

    private function affichage(?array $avant): string
    {
        if ($avant === null) {
            return '—';
        }

        return ($avant['absent'] ? 'Absent' : $this->nombre($avant['note'])) . ($avant['validee'] ? ' (validée)' : '');
    }

    private function nombre($n): string
    {
        return rtrim(rtrim(number_format((float) $n, 2, ',', ' '), '0'), ',');
    }

    private function manque(string $message): Proposition
    {
        return new Proposition(titre: 'Saisie de notes', resume: '', manques: [$message]);
    }
}
