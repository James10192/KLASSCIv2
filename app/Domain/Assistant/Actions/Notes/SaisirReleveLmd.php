<?php

namespace App\Domain\Assistant\Actions\Notes;

use App\Domain\Academique\CoherenceSystemeAcademique;
use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Designations;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Domain\Notes\RegularisationDeNotesLmd;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Saisir le relevé officiel d'une classe LMD pour un semestre, souvent d'une
 * année écoulée, quand les évaluations n'existent pas : RegularisationDeNotesLmd,
 * le même chemin que `POST /api/cli/lmd/evaluations/regulariser-notes`.
 *
 * Fait pour ESBTP Abidjan (octobre 2026) : les relevés 2025-2026 de L1 arrivent
 * en fin d'année, matière par colonne. Chaque colonne du relevé est rattachée à
 * un élément de la maquette du semestre ; une colonne qui n'en désigne aucun, ou
 * plusieurs, est une question, jamais un choix. Un zéro aussi : il peut dire
 * « épreuve non composée ».
 */
class SaisirReleveLmd extends ActionAgent
{
    use Designations;
    use ReconnaitDesEtudiants;

    private const MAX_ETUDIANTS = 120;

    private const MAX_NOTES = 40;

    public function __construct(private RegularisationDeNotesLmd $regularisation)
    {
    }

    public function cle(): string
    {
        return 'releve_notes_lmd';
    }

    public function libelle(): string
    {
        return 'Préparation de la saisie du relevé…';
    }

    public function description(): string
    {
        return "PROPOSE d'enregistrer le relevé de notes d'une classe LMD pour un semestre, y compris d'une année écoulée, quand les évaluations n'ont jamais été créées. "
            . "Crée une évaluation de régularisation par élément (comptée au bulletin, non publiée aux étudiants) et y pose les notes. "
            . "Classe, année (« 2025-2026 »), semestre, motif, puis une ligne par étudiant (matricule ou nom complet tel que sur le relevé) avec ses notes, "
            . "chaque note rattachée à l'élément de la maquette par son code ou son intitulé exact. N'invente ni note ni correspondance de colonne. "
            . "Pour une évaluation qui existe déjà, utilise proposer_saisie_notes.";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'classe' => ['type' => 'string', 'description' => 'Code, nom exact ou identifiant de la classe LMD.'],
                'annee' => ['type' => 'string', 'description' => "Année universitaire du relevé, ex. « 2025-2026 ». Par défaut l'année en cours."],
                'semestre' => ['type' => 'string', 'description' => 'Semestre de la maquette, numéroté en continu : S1-S2 en L1, S3-S4 en L2, S5-S6 en L3.'],
                'motif' => ['type' => 'string', 'description' => "D'où viennent ces notes (relevé officiel transmis par…), 20 caractères au moins."],
                'date' => ['type' => 'string', 'description' => 'Date du relevé ou de la session (AAAA-MM-JJ). Par défaut aujourd\'hui.'],
                'zeros_confirmes' => ['type' => 'boolean', 'description' => 'true seulement si la personne a confirmé que les 0 sont de vraies notes et non des épreuves non composées.'],
                'etudiants' => [
                    'type' => 'array',
                    'items' => ['type' => 'object', 'properties' => [
                        'etudiant' => ['type' => 'string', 'description' => 'Matricule, ou nom et prénoms tels que sur le relevé.'],
                        'notes' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => [
                            'element' => ['type' => 'string', 'description' => "Code ou intitulé exact de l'élément (ECUE) de la maquette."],
                            'note' => ['type' => 'number', 'description' => 'Note sur 20.'],
                        ], 'required' => ['element', 'note']]],
                    ], 'required' => ['etudiant', 'notes']],
                ],
            ],
            'required' => ['classe', 'semestre', 'motif', 'etudiants'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = 'Saisie de relevé';
        if (! $user->can('notes.edit') || ! $user->can('evaluations.create')) {
            return $this->seulManque($titre, "Cet utilisateur n'a pas le droit de créer des évaluations et d'y poser des notes.");
        }
        [$classe, $manque] = $this->designerClasse($args['classe'] ?? '');
        if (! $classe) {
            return $this->seulManque($titre, $manque);
        }
        if (! CoherenceSystemeAcademique::classeEstLmd($classe->systeme_academique)) {
            return $this->seulManque($titre, "{$classe->name} n'est pas une classe LMD : pour une classe BTS, crée l'évaluation puis utilise proposer_saisie_notes.");
        }
        [$annee, $manque] = $this->designerAnnee($args);
        if (! $annee) {
            return $this->seulManque($titre, $manque);
        }
        $periode = $this->semestreDeLaClasse((string) ($args['semestre'] ?? ''), $classe);
        $motif = trim((string) ($args['motif'] ?? ''));
        $date = trim((string) ($args['date'] ?? '')) ?: now()->toDateString();
        $lignes = array_values(array_filter((array) ($args['etudiants'] ?? []), 'is_array'));

        // Seul ce qui empêche de lire le relevé arrête ici. Le reste — semestre,
        // motif, date — rejoint les questions des colonnes, des 0 et des étudiants :
        // la personne reçoit TOUTES les questions en une fois, pas une par tour.
        if ($lignes === [] || count($lignes) > self::MAX_ETUDIANTS) {
            return $this->seulManque($titre, $lignes === [] ? 'Quelles notes saisir ?' : 'Trop d\'étudiants en une fois (' . self::MAX_ETUDIANTS . ' au plus) : découpe le relevé.');
        }
        $manques = array_values(array_filter([
            $periode === null ? "Quel semestre ? Pour {$classe->name} : " . $this->semestresPossibles($classe) . ' (numérotation de la maquette : une L2 a S3 et S4).' : null,
            mb_strlen($motif) < 20 ? "D'où viennent ces notes (relevé officiel transmis par qui) ? Le motif est gardé sur chaque note." : null,
            ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? 'Quelle date pour ce relevé (AAAA-MM-JJ) ?'
                : ($date > now()->toDateString() ? "La date {$date} est dans le futur : quelle est la date réelle du relevé ou de la session ?" : null),
        ]));

        // Sans semestre, la maquette est inconnue : les colonnes attendront, mais les
        // étudiants et les 0 se contrôlent quand même.
        $unites = $periode === null ? null : $this->regularisation->unitesDeLaMaquette($classe, (int) substr($periode, 8));
        [$entrees, $manquesDuReleve, $zeros] = $this->resoudre($lignes, $this->inscrits($classe, (int) $annee->id), $unites, $classe, (string) $periode);
        $manques = array_merge($manques, $manquesDuReleve);
        if ($zeros > 0 && ! filter_var($args['zeros_confirmes'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $manques[] = "{$zeros} note(s) valent 0 : ce sont de vraies notes de 0/20, ou des épreuves non composées ? Retire celles qui n'ont pas été composées, ou confirme (zeros_confirmes).";
        }
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: $manques);
        }

        $base = ['classe_id' => (int) $classe->id, 'annee_universitaire_id' => (int) $annee->id, 'periode' => $periode,
            'date_regularisation' => $date, 'motif' => $motif];
        try {
            $rapport = $this->simuler($base, $entrees, (int) $user->id);
        } catch (ValidationException $e) {
            return $this->seulManque($titre, collect($e->errors())->flatten()->implode(' '));
        }

        $changees = array_values(array_filter($rapport, fn ($r) => $r['avant'] !== $r['apres']));
        if ($changees === []) {
            return $this->seulManque($titre, 'Rien à changer : ce relevé est déjà enregistré.');
        }

        return new Proposition(
            titre: 'Relevé S' . substr($periode, 8) . " {$annee->name} — {$classe->name}",
            resume: sprintf('%d note(s) pour %d étudiant(s), %d élément(s) de la maquette %s. Une évaluation de régularisation par élément, comptée au bulletin, non publiée aux étudiants. Motif : %s',
                count($changees), count(array_unique(array_column($changees, 'etudiant_id'))), count(array_unique(array_column($changees, 'matiere_id'))),
                'S' . substr($periode, 8), $motif),
            tableau: [
                'colonnes' => ['Étudiant', 'Élément', 'Avant', 'Après'],
                'lignes' => array_map(fn ($r) => [$r['etudiant'], $r['matiere'], $r['avant'] === null ? '—' : $this->nombre($r['avant']), $this->nombre($r['apres'])], $changees),
            ],
            avertissements: array_values(array_filter([
                $this->dejaEnregistrees($rapport),
                count(array_filter($changees, fn ($r) => $r['avant'] !== null)) > 0 ? 'Des notes déjà saisies par régularisation seront remplacées (colonne « Avant »).' : null,
                'Ces notes comptent au bulletin LMD dès la validation ; les étudiants ne les voient qu\'une fois les évaluations publiées.',
            ])),
            donnees: $base + ['etudiants' => $entrees],
            etat: ['lignes' => $rapport],
            risque: 'eleve',
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        if (! $user->can('notes.edit') || ! $user->can('evaluations.create')) {
            throw new PropositionPerimee("Vous n'avez plus le droit de créer des évaluations et d'y poser des notes.");
        }
        $d = $proposition->donnees;
        $base = array_intersect_key($d, array_flip(['classe_id', 'annee_universitaire_id', 'periode', 'date_regularisation', 'motif']));
        try {
            if ($this->simuler($base, $d['etudiants'], (int) $user->id) !== $proposition->etat['lignes']) {
                throw new PropositionPerimee('Ces notes ont changé depuis la proposition.');
            }
            $ecrites = DB::transaction(fn () => array_sum(array_map(
                fn ($e) => count($this->regularisation->appliquer($base + $e, false, (int) $user->id, 'Nanan')['lignes']),
                $d['etudiants'],
            )));
        } catch (ValidationException $e) {
            throw new PropositionPerimee(collect($e->errors())->flatten()->implode(' '));
        }

        Log::warning('assistant: releve de notes LMD saisi', $base + ['etudiants' => count($d['etudiants']), 'notes' => $ecrites, 'user_id' => $user->id]);

        return [
            'message' => "{$ecrites} note(s) enregistrée(s) pour " . count($d['etudiants']) . ' étudiant(s). Elles comptent au bulletin LMD (à régénérer) ; publiez les évaluations pour que les étudiants les voient.',
            'lien' => route('esbtp.classes.show', $d['classe_id'], false),
            'model_type' => ESBTPClasse::class,
            'model_id' => (int) $d['classe_id'],
        ];
    }

    /** Les étudiants inscrits activement dans la classe cette année-là. */
    private function inscrits(ESBTPClasse $classe, int $anneeId): Collection
    {
        return ESBTPEtudiant::query()
            ->whereHas('inscriptions', fn ($q) => $q->where('classe_id', $classe->id)
                ->where('annee_universitaire_id', $anneeId)->where('status', 'active'))
            ->get();
    }

    /**
     * @return array{0: list<array{etudiant_id: int, notes: list<array{matiere_id: int, note: float}>}>, 1: list<string>, 2: int}
     */
    private function resoudre(array $lignes, Collection $inscrits, ?Collection $unites, ESBTPClasse $classe, string $periode): array
    {
        $entrees = [];
        $manques = [];
        $zeros = 0;
        $vus = [];
        foreach ($lignes as $i => $ligne) {
            $designation = trim((string) ($ligne['etudiant'] ?? ''));
            [$trouves, $proches] = $this->reconnaitre($designation, $inscrits);
            if (count($trouves) !== 1) {
                $manques[] = $this->manqueDEtudiant($designation, $i, $trouves, $proches) . " (inscrits en {$classe->name} cette année-là)";
                continue;
            }
            $etudiant = $trouves[0];
            if (isset($vus[$etudiant->id])) {
                $manques[] = $this->nom($etudiant) . ' apparaît deux fois : quelle ligne garder ?';
                continue;
            }
            $vus[$etudiant->id] = true;

            $notes = [];
            $sesNotes = array_values(array_filter((array) ($ligne['notes'] ?? []), 'is_array'));
            if (count($sesNotes) > self::MAX_NOTES) {
                $manques[] = $this->nom($etudiant) . ' a ' . count($sesNotes) . ' notes : ' . self::MAX_NOTES . ' au plus par étudiant et par semestre. Une colonne en double ?';
                continue;
            }
            foreach ($sesNotes as $n) {
                if ($unites === null) {
                    $zeros += is_numeric($n['note'] ?? null) && (float) $n['note'] == 0.0 ? 1 : 0;
                    continue;
                }
                [$element, $manque] = $this->element((string) ($n['element'] ?? ''), $unites, $periode);
                if (! $element) {
                    $manques[$manque] = $manque;
                    continue;
                }
                if (! is_numeric($n['note'] ?? null) || (float) $n['note'] < 0 || (float) $n['note'] > 20) {
                    $manques[] = $this->nom($etudiant) . " — {$element->name} : « " . mb_substr((string) ($n['note'] ?? ''), 0, 20) . " » n'est pas une note sur 20.";
                    continue;
                }
                if (isset($notes[$element->id])) {
                    $manques[] = $this->nom($etudiant) . " a deux notes pour {$element->name} : laquelle garder ?";
                    continue;
                }
                $zeros += (float) $n['note'] == 0.0 ? 1 : 0;
                $notes[$element->id] = ['matiere_id' => (int) $element->id, 'note' => round((float) $n['note'], 2)];
            }
            if ($notes !== []) {
                $entrees[] = ['etudiant_id' => (int) $etudiant->id, 'notes' => array_values($notes)];
            }
        }

        return [$entrees, array_values($manques), $zeros];
    }

    /**
     * Un élément de la maquette du semestre, par identifiant, code (imprimé ou
     * interne) ou intitulé exact. Une colonne qui nomme une UE (« Béton armé »)
     * n'est pas un élément : on dit laquelle et ce qu'elle contient, pour que la
     * personne tranche — répartir, ou rattacher à un seul élément.
     */
    private function element(string $designation, Collection $unites, string $periode): array
    {
        $cle = $this->normaliser($designation);
        $designe = fn ($o) => in_array($cle, [$this->normaliser((string) $o->code), $this->normaliser((string) $o->code_affiche), $this->normaliser((string) $o->name)], true);
        $maquette = $unites->flatMap(fn (array $u) => $u['ecues'])->unique('id');

        $trouves = $maquette->filter(fn ($m) => (ctype_digit($designation) && (int) $designation === (int) $m->id) || $designe($m))->values();
        if ($trouves->count() === 1) {
            return [$trouves->first(), null];
        }
        if ($trouves->count() > 1) {
            return [null, "« {$designation} » désigne plusieurs éléments (" . $trouves->map(fn ($m) => "{$m->code_affiche} {$m->name}")->implode(', ') . ') : lequel ?'];
        }

        $s = 'S' . substr($periode, 8);
        $ue = $unites->first(fn (array $u) => $designe($u['ue']));
        if ($ue) {
            return [null, $ue['ecues']->isEmpty()
                ? "« {$designation} » est une UE ({$ue['ue']->code_affiche}) qui n'a aucun élément dans la maquette : à quel élément rattacher cette colonne ?"
                : "« {$designation} » est une UE ({$ue['ue']->code_affiche}), pas un élément : elle compte "
                    . $ue['ecues']->map(fn ($m) => "{$m->code_affiche} {$m->name}")->implode(', ')
                    . '. À quel élément rattacher cette colonne, ou la même note vaut-elle pour chacun ?'];
        }

        $liste = $unites->map(fn (array $u) => $u['ue']->name . ' : ' . $u['ecues']->map(fn ($m) => "{$m->code_affiche} {$m->name}")->implode(', '))->implode(' ; ');

        return [null, "« {$designation} » n'est pas un élément de la maquette {$s} de cette classe. Éléments par UE — {$liste}. Lequel ?"];
    }

    /**
     * Les lignes du relevé déjà en base à l'identique ne sont pas reproposées :
     * on le dit, pour que personne ne les croie oubliées.
     */
    private function dejaEnregistrees(array $rapport): ?string
    {
        $memes = array_values(array_filter($rapport, fn ($r) => $r['avant'] === $r['apres']));
        if ($memes === []) {
            return null;
        }
        $liste = implode(', ', array_map(fn ($r) => "{$r['etudiant']} — {$r['matiere']} ({$this->nombre($r['apres'])})", array_slice($memes, 0, 5)));

        return count($memes) . " note(s) du relevé sont déjà enregistrées avec la même valeur et ne changent pas : {$liste}" . (count($memes) > 5 ? '…' : '') . '.';
    }

    /**
     * « S3 », « semestre 3 », « 3 » : le semestre de la maquette, qui doit être l'un
     * des deux de la classe. Une L2 n'a pas de S1 : on demande au lieu de deviner.
     */
    private function semestreDeLaClasse(string $valeur, ESBTPClasse $classe): ?string
    {
        if (! preg_match('/^(?:s|semestre\s*)?(\d{1,2})$/u', mb_strtolower(trim($valeur)), $m)) {
            return null;
        }

        return in_array((int) $m[1], $classe->getSemestresLMD(), true) ? 'semestre' . (int) $m[1] : null;
    }

    private function semestresPossibles(ESBTPClasse $classe): string
    {
        return implode(' ou ', array_map(fn ($s) => "S{$s}", $classe->getSemestresLMD()));
    }

    /** @return list<array{etudiant_id: int, etudiant: string, matiere_id: int, matiere: string, avant: ?float, apres: float}> */
    private function simuler(array $base, array $entrees, int $userId): array
    {
        $noms = ESBTPEtudiant::whereIn('id', array_column($entrees, 'etudiant_id'))->get()->keyBy('id');
        $rapport = [];
        foreach ($entrees as $e) {
            foreach ($this->regularisation->appliquer($base + $e, true, $userId, 'Nanan')['lignes'] as $l) {
                $rapport[] = ['etudiant_id' => (int) $e['etudiant_id'], 'etudiant' => $this->nom($noms->get($e['etudiant_id'])),
                    'matiere_id' => $l['matiere_id'], 'matiere' => $l['matiere'], 'avant' => $l['avant'], 'apres' => $l['apres']];
            }
        }

        return $rapport;
    }
}
