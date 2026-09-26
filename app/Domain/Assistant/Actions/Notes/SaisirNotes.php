<?php

namespace App\Domain\Assistant\Actions\Notes;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Domain\Assistant\Pieces\PiecesJointes;
use App\Domain\Notes\Exceptions\SaisieInterrompue;
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
    private const MAX_LIGNES = 500;

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
            . "N'enregistre rien : l'utilisateur voit le tableau et valide. Notes venues d'un fichier joint : passe « piece » (piece_id + noms exacts des colonnes), jamais les valeurs recopiées. "
            . "Sinon, chaque étudiant est désigné par son matricule "
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
                'piece' => [
                    'type' => 'object',
                    'description' => 'À la place de « notes », quand les notes viennent d\'un fichier joint : le serveur relit le fichier, ne recopie rien.',
                    'properties' => [
                        'piece_id' => ['type' => 'string', 'description' => 'piece_id donné dans <pieces_jointes>.'],
                        'colonnes_etudiant' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Colonne(s) qui désignent l\'étudiant : le matricule, ou le nom et les prénoms (plusieurs colonnes sont réunies).'],
                        'colonne_note' => ['type' => 'string', 'description' => 'Nom exact de la colonne des notes.'],
                        'colonne_absent' => ['type' => 'string', 'description' => 'Facultatif : colonne qui marque les absents.'],
                    ],
                    'required' => ['piece_id', 'colonnes_etudiant', 'colonne_note'],
                ],
                'valider' => ['type' => 'boolean', 'description' => 'true seulement si l\'utilisateur demande de VALIDER (soumettre) les notes ; sinon brouillon.'],
            ],
            'required' => ['evaluation_id'],
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
        if ((float) $evaluation->bareme <= 0) {
            return $this->manque("Le barème de cette évaluation n'est pas défini : il faut le renseigner avant de saisir des notes.");
        }
        [$lignes, $manquesPiece, $avertissementsPiece] = isset($args['piece']) && is_array($args['piece'])
            ? $this->lignesDepuisLaPiece($args['piece'], $user)
            : [array_values(array_filter((array) ($args['notes'] ?? []), 'is_array')), [], []];
        if ($manquesPiece !== []) {
            return new Proposition(titre: 'Saisie de notes', resume: '', manques: $manquesPiece);
        }
        if ($lignes === [] || count($lignes) > self::MAX_LIGNES) {
            return $this->manque($lignes === [] ? 'Aucune note fournie.' : 'Trop de lignes en une fois (' . self::MAX_LIGNES . ' au plus).');
        }

        $valider = filter_var($args['valider'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $etudiants = $this->cohorte->studentsForEvaluation($evaluation);
        [$entrees, $manques, $vus] = $this->resoudreLignes($lignes, $etudiants, $evaluation);
        [$tableau, $etat, $retenues, $compte, $refus] = $this->comparerALExistant($entrees, $etudiants, $user, $valider);

        return new Proposition(
            titre: 'Notes : ' . $this->intitule($evaluation),
            resume: sprintf('%d nouvelle(s), %d remplacée(s), %d à valider, %d inchangée(s), sur %s. %s.',
                $compte['creation'], $compte['modification'], $compte['validation'], $compte['inchange'],
                $this->nombre($evaluation->bareme), $valider ? 'Validation finale' : 'Brouillon'),
            tableau: ['colonnes' => ['Étudiant', 'Matricule', 'Avant', 'Après', 'Effet'], 'lignes' => $tableau],
            manques: array_merge($manques, $refus, $retenues === [] && $refus === [] && $manques === [] ? ['Rien à changer : ces notes sont déjà enregistrées.'] : []),
            avertissements: array_merge($avertissementsPiece, $this->avertissements($etudiants, $vus, $evaluation, $compte, $valider)),
            donnees: ['evaluation_id' => (int) $evaluation->id, 'entrees' => $retenues, 'valider' => $valider],
            // Tout ce qui, en changeant, rendrait la proposition trompeuse : les notes
            // actuelles ET l'évaluation (un barème passé de 20 à 40 ferait d'un 15 un 7,5).
            etat: ['notes' => $etat, 'evaluation' => [
                'bareme' => (float) $evaluation->bareme, 'publiee' => (bool) $evaluation->is_published,
                'classe_id' => (int) $evaluation->classe_id, 'matiere_id' => (int) $evaluation->matiere_id,
                'periode' => (string) $evaluation->periode,
            ]],
            risque: $compte['modification'] > 0 || $valider ? 'eleve' : 'moyen',
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        try {
            $resultat = $this->saisie->enregistrerToutOuRien(
                $proposition->donnees['entrees'], $user, (bool) $proposition->donnees['valider'], $proposition->etat['notes']
            );
        } catch (SaisieInterrompue $e) {
            // Annulée avant le commit : rien n'est écrit, aucun avis d'absence n'est parti.
            throw new PropositionPerimee($e->getMessage());
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
     * Les lignes d'un fichier joint, lues par le serveur : le modèle n'a désigné
     * que la pièce et ses colonnes. Une cellule de note vide n'est pas une note
     * (ligne ignorée, et dit) ; « abs », « absent » valent absence ; « 12,5 » vaut 12,5.
     *
     * @return array{0: array, 1: string[], 2: string[]}
     */
    private function lignesDepuisLaPiece(array $source, $user): array
    {
        $piece = app(PiecesJointes::class)->pour((int) $user->id, (string) ($source['piece_id'] ?? ''));
        if (! $piece) {
            return [[], ['Le fichier joint n\'est plus disponible (deux heures au plus) : demande à la personne de le joindre de nouveau.'], []];
        }

        $index = array_flip(array_map(fn ($c) => mb_strtolower(trim($c)), $piece['colonnes']));
        $trouver = fn ($nom) => $index[mb_strtolower(trim((string) $nom))] ?? null;
        $colsEtudiant = array_map($trouver, (array) ($source['colonnes_etudiant'] ?? []));
        $colNote = $trouver($source['colonne_note'] ?? '');
        $colAbsent = isset($source['colonne_absent']) ? $trouver($source['colonne_absent']) : null;
        if ($colsEtudiant === [] || in_array(null, $colsEtudiant, true) || $colNote === null || (isset($source['colonne_absent']) && $colAbsent === null)) {
            return [[], ['Colonne introuvable dans « ' . $piece['nom'] . ' ». Colonnes disponibles : ' . implode(', ', $piece['colonnes']) . '.'], []];
        }

        $lignes = [];
        $vides = 0;
        foreach ($piece['lignes'] as $l) {
            $etudiant = trim(implode(' ', array_map(fn ($i) => $l[$i] ?? '', $colsEtudiant)));
            $valeur = trim((string) ($l[$colNote] ?? ''));
            $absent = preg_match('/^(abs|absent|absente)\.?$/iu', $valeur)
                || ($colAbsent !== null && preg_match('/^(x|oui|1|abs|absent|absente)$/iu', trim((string) ($l[$colAbsent] ?? ''))));
            if ($etudiant === '' && $valeur === '') {
                continue;
            }
            if ($valeur === '' && ! $absent) {
                $vides++;
                continue;
            }
            $nombre = str_replace([',', ' ', "\u{00A0}"], ['.', '', ''], $valeur);
            $lignes[] = ['etudiant' => $etudiant] + ($absent ? ['absent' => true] : ['note' => is_numeric($nombre) ? (float) $nombre : $valeur]);
        }

        return [$lignes, [], $vides > 0 ? ["{$vides} ligne(s) du fichier sans note sont ignorées (rien n'est écrit pour elles)."] : []];
    }

    /**
     * Chaque ligne donnée par la personne → une entrée résolue, ou un manque.
     *
     * @return array{0: array, 1: string[], 2: array<int, bool>}
     */
    private function resoudreLignes(array $lignes, $etudiants, ESBTPEvaluation $evaluation): array
    {
        $bareme = (float) $evaluation->bareme;
        $manques = [];
        $entrees = [];
        $vus = [];

        foreach ($lignes as $i => $ligne) {
            $designation = trim((string) ($ligne['etudiant'] ?? ''));
            [$trouves, $proches] = $this->reconnaitre($designation, $etudiants);
            if (count($trouves) !== 1) {
                $manques[] = $this->manqueDEtudiant($designation, $i, $trouves, $proches);
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
            if (! $absent && ! is_numeric($note)) {
                $manques[] = $this->nom($etudiant) . (! is_scalar($note) || $note === ''
                    ? ' : note absente. Donne la note ou dis s\'il était absent.'
                    : ' : « ' . mb_substr((string) $note, 0, 30) . " » n'est pas une note.");
                continue;
            }
            $note = $absent ? null : round((float) $note, 2);
            if ($note !== null && ($note < 0 || $note > $bareme)) {
                $manques[] = $this->nom($etudiant) . " : {$this->nombre($note)} n'est pas entre 0 et " . $this->nombre($bareme) . ' (barème).';
                continue;
            }

            $entrees[] = ['etudiant_id' => (int) $etudiant->id, 'evaluation_id' => (int) $evaluation->id, 'note' => $note, 'is_absent' => $absent];
        }

        return [$entrees, $manques, $vus];
    }

    /**
     * Ce que change chaque entrée par rapport à la base, avec les mêmes gardes que
     * l'écran. Une ligne identique n'est pas réécrite, sauf pour être validée.
     *
     * @return array{0: array, 1: array, 2: array, 3: array<string,int>, 4: string[]}
     */
    private function comparerALExistant(array $entrees, $etudiants, $user, bool $valider): array
    {
        $parId = $etudiants->keyBy('id');
        $tableau = [];
        $etat = [];
        $retenues = [];
        $refus = [];
        $compte = ['creation' => 0, 'modification' => 0, 'validation' => 0, 'inchange' => 0];
        $effets = ['creation' => 'Nouvelle', 'modification' => 'Remplace', 'validation' => 'Validée', 'inchange' => 'Inchangée'];

        foreach ($entrees === [] ? [] : $this->saisie->analyser($entrees, $user) as $a) {
            $etudiant = $parId->get($a['entree']['etudiant_id']);
            if ($a['statut'] === 'refus') {
                $refus[] = $this->nom($etudiant) . ' : ' . $a['raison'] . '.';
                continue;
            }
            $statut = $a['statut'] === 'inchange' && $valider && ! ($a['avant']['validee'] ?? false) ? 'validation' : $a['statut'];
            $compte[$statut]++;
            if ($statut !== 'inchange') {
                $retenues[] = $a['entree'];
                $etat[$a['entree']['etudiant_id']] = $a['avant'];
            }
            $tableau[] = [
                $this->nom($etudiant),
                (string) $etudiant->matricule,
                $this->affichage($a['avant']),
                $a['entree']['is_absent'] ? 'Absent' : $this->nombre($a['entree']['note']),
                $effets[$statut],
            ];
        }

        return [$tableau, $etat, $retenues, $compte, $refus];
    }

    /** @return string[] */
    private function avertissements($etudiants, array $vus, ESBTPEvaluation $evaluation, array $compte, bool $valider): array
    {
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
        if ($valider) {
            $avertissements[] = 'Les notes seront VALIDÉES (soumises), pas laissées en brouillon.';
        }

        return $avertissements;
    }

    private function manqueDEtudiant(string $designation, int $i, array $trouves, array $proches): string
    {
        $liste = fn (array $es) => implode(', ', array_map(fn ($e) => $this->nom($e) . ' (' . $e->matricule . ')', array_slice($es, 0, 5)));

        return match (true) {
            $designation === '' => 'Ligne ' . ($i + 1) . ' : étudiant non précisé.',
            count($trouves) > 1 => "« {$designation} » désigne plusieurs étudiants : " . $liste($trouves) . '. Lequel ?',
            $proches !== [] => "« {$designation} » ne correspond exactement à personne ; plusieurs étudiants possibles : " . $liste($proches) . '. Lequel ?',
            default => "« {$designation} » : aucun étudiant de cette classe ne correspond. Demande le matricule.",
        };
    }

    private function intitule(ESBTPEvaluation $evaluation): string
    {
        return implode(' — ', array_filter([$evaluation->titre ?: 'Évaluation', $evaluation->matiere?->name, $evaluation->classe?->name]));
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
