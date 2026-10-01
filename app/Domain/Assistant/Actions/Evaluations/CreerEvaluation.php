<?php

namespace App\Domain\Assistant\Actions\Evaluations;

use App\Domain\Academique\CoherenceSystemeAcademique;
use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPMatiere;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Nanan prépare la création d'UNE évaluation, comme l'écran « Nouvelle
 * évaluation » (/esbtp/evaluations/create) : classe, matière, type, date et
 * horaires, barème, coefficient, période. Rien n'est créé avant « Valider ».
 *
 * C'est la marche qui manquait avant la saisie de notes : SaisirNotes exige
 * une évaluation existante, que l'enseignant devait jusqu'ici créer à l'écran.
 *
 * Rien n'est deviné : barème et coefficient sont demandés, jamais posés par
 * défaut. Une évaluation identique (même classe, matière, jour et titre) est
 * refusée, pour qu'une demande répétée ne crée pas de doublon.
 */
final class CreerEvaluation extends ActionAgent
{
    public function cle(): string
    {
        return 'creation_evaluation';
    }

    public function description(): string
    {
        return "PROPOSE la création d'UNE évaluation (devoir, examen, TP…) pour une classe et une matière. "
            . "Retrouve d'abord les identifiants avec search_classes et search_subjects ; n'invente jamais un identifiant. "
            . "Barème et coefficient sont obligatoires : s'ils ne sont pas donnés, demande-les, ne les suppose pas. "
            . "Rien n'est créé avant le clic Valider. Une fois créée, la saisie des notes se fait avec proposer_saisie_notes.";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'classe_id' => ['type' => 'integer', 'description' => 'Identifiant de la classe (search_classes).'],
                'matiere_id' => ['type' => 'integer', 'description' => 'Identifiant de la matière (search_subjects).'],
                'titre' => ['type' => 'string', 'description' => 'Intitulé, tel que donné par l\'utilisateur.'],
                'type' => ['type' => 'string', 'enum' => ESBTPEvaluation::TYPES_SAISISSABLES],
                'date' => ['type' => 'string', 'description' => 'Date AAAA-MM-JJ.'],
                'heure_debut' => ['type' => 'string', 'description' => 'HH:MM'],
                'heure_fin' => ['type' => 'string', 'description' => 'HH:MM, après l\'heure de début.'],
                'bareme' => ['type' => 'number', 'description' => 'Note maximale (20 le plus souvent) : à demander si non donnée.'],
                'coefficient' => ['type' => 'number', 'description' => 'Coefficient de l\'évaluation : à demander si non donné.'],
                'periode' => ['type' => 'string', 'description' => 'Semestre : S1, S2… (ou semestre1, semestre2…).'],
                'publier' => ['type' => 'boolean', 'description' => 'true seulement si l\'utilisateur demande de la publier tout de suite ; sinon brouillon.'],
            ],
            'required' => ['classe_id', 'matiere_id', 'titre', 'type', 'date', 'heure_debut', 'heure_fin', 'periode'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $manques = [];

        $classe = ESBTPClasse::find((int) ($args['classe_id'] ?? 0));
        $matiere = ESBTPMatiere::find((int) ($args['matiere_id'] ?? 0));
        if (! $classe || ! $matiere) {
            return $this->manque('Classe ou matière introuvable : retrouve-les avec search_classes et search_subjects, puis donne leurs identifiants.');
        }
        if (! CoherenceSystemeAcademique::estCoherente($classe->systeme_academique, $matiere->unite_enseignement_id)) {
            return $this->manque("« {$matiere->name} » ne relève pas du même système académique (BTS / LMD) que la classe {$classe->name} : choisis une autre matière.");
        }

        $annee = ESBTPAnneeUniversitaire::anneeCourante();
        if (! $annee) {
            return $this->manque('Aucune année universitaire courante : elle doit être créée avant d\'ajouter une évaluation.');
        }

        if (! $this->peutCreerPour($user, $classe, (int) $matiere->id, (int) $annee->id)) {
            return $this->manque("Cet utilisateur n'est pas affecté à « {$matiere->name} » pour la classe {$classe->name} cette année.");
        }

        $titre = trim((string) ($args['titre'] ?? ''));
        if ($titre === '' || mb_strlen($titre) > 255) {
            $manques[] = $titre === '' ? 'Intitulé de l\'évaluation absent.' : 'Intitulé trop long (255 caractères au plus).';
        }

        $type = (string) ($args['type'] ?? '');
        if (! in_array($type, ESBTPEvaluation::TYPES_SAISISSABLES, true)) {
            $manques[] = 'Type d\'évaluation inconnu : ' . implode(', ', ESBTPEvaluation::TYPES_SAISISSABLES) . '.';
        }

        $periode = self::normaliserPeriode((string) ($args['periode'] ?? ''));
        if ($periode === null) {
            $manques[] = 'Période inconnue : indique le semestre (S1, S2…).';
        }

        $horaires = self::horaires((string) ($args['date'] ?? ''), (string) ($args['heure_debut'] ?? ''), (string) ($args['heure_fin'] ?? ''));
        if ($horaires === null) {
            $manques[] = 'Date ou horaires invalides : date AAAA-MM-JJ, heures HH:MM, la fin après le début.';
        }

        $bareme = self::nombre($args['bareme'] ?? null);
        if ($bareme === null || $bareme < 0.1 || $bareme > 100) {
            $manques[] = $bareme === null ? 'Barème non donné : demande-le (20 le plus souvent), ne le suppose pas.' : 'Barème hors bornes (entre 0,1 et 100).';
        }

        $coefficient = self::nombre($args['coefficient'] ?? null);
        if ($coefficient === null || $coefficient < 0.1 || $coefficient > 10) {
            $manques[] = $coefficient === null ? 'Coefficient non donné : demande-le, ne le suppose pas.' : 'Coefficient hors bornes (entre 0,1 et 10).';
        }

        if ($manques !== []) {
            return new Proposition(titre: 'Nouvelle évaluation', resume: '', manques: $manques);
        }

        [$debut, $fin] = $horaires;
        $memeJour = $this->evaluationsDuMemeJour((int) $classe->id, (int) $matiere->id, (int) $annee->id, $debut);
        $doublon = $memeJour->first(fn (ESBTPEvaluation $e) => mb_strtolower(trim((string) $e->titre)) === mb_strtolower($titre));
        if ($doublon) {
            return $this->manque("Cette évaluation existe déjà (« {$doublon->titre} », n° {$doublon->id}, le {$debut->format('d/m/Y')}) : utilise son identifiant au lieu d'en créer une seconde.");
        }

        $publier = filter_var($args['publier'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $avertissements = [];
        if ($memeJour->isNotEmpty()) {
            $avertissements[] = $memeJour->count() . ' autre(s) évaluation(s) de cette matière existe(nt) déjà ce jour-là pour cette classe : vérifie qu\'il ne s\'agit pas de la même.';
        }
        if ($debut->isPast()) {
            $avertissements[] = 'La date est passée : l\'évaluation sera créée pour une épreuve déjà tenue.';
        }

        return new Proposition(
            titre: 'Nouvelle évaluation · ' . $classe->name,
            resume: sprintf('%s « %s » en %s, le %s de %s à %s, sur %s, coefficient %s, %s. %s.',
                ucfirst($type), $titre, $matiere->name, $debut->format('d/m/Y'), $debut->format('H:i'), $fin->format('H:i'),
                self::texte($bareme), self::texte($coefficient), self::libellePeriode($periode),
                $publier ? 'Publiée dès la création' : 'Brouillon (non visible des étudiants)'),
            tableau: [
                'colonnes' => ['Classe', 'Matière', 'Type', 'Date', 'Barème', 'Coefficient', 'Période'],
                'lignes' => [[
                    (string) $classe->name, (string) $matiere->name, ucfirst($type),
                    $debut->format('d/m/Y H:i') . ' – ' . $fin->format('H:i'),
                    self::texte($bareme), self::texte($coefficient), self::libellePeriode($periode),
                ]],
            ],
            avertissements: $avertissements,
            donnees: [
                'classe_id' => (int) $classe->id,
                'matiere_id' => (int) $matiere->id,
                'annee_universitaire_id' => (int) $annee->id,
                'titre' => $titre,
                'type' => $type,
                'debut' => $debut->format('Y-m-d H:i'),
                'fin' => $fin->format('Y-m-d H:i'),
                'bareme' => $bareme,
                'coefficient' => $coefficient,
                'periode' => $periode,
                'publier' => $publier,
            ],
            // Une évaluation créée entre-temps le même jour (par l'écran ou un
            // autre onglet) change l'empreinte : la validation repropose.
            etat: ['meme_jour' => $memeJour->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all()],
            risque: 'faible',
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        $d = $proposition->donnees;
        $debut = Carbon::createFromFormat('Y-m-d H:i', $d['debut']);
        $fin = Carbon::createFromFormat('Y-m-d H:i', $d['fin']);

        $evaluation = DB::transaction(function () use ($d, $debut, $fin, $user) {
            $doublon = $this->evaluationsDuMemeJour((int) $d['classe_id'], (int) $d['matiere_id'], (int) $d['annee_universitaire_id'], $debut)
                ->contains(fn (ESBTPEvaluation $e) => mb_strtolower(trim((string) $e->titre)) === mb_strtolower($d['titre']));
            if ($doublon) {
                throw new PropositionPerimee('Une évaluation identique vient d\'être créée.');
            }

            $evaluation = new ESBTPEvaluation;
            $evaluation->titre = $d['titre'];
            $evaluation->type = $d['type'];
            $evaluation->date_evaluation = $debut;
            $evaluation->duree_minutes = (int) $debut->diffInMinutes($fin);
            $evaluation->bareme = $d['bareme'];
            $evaluation->coefficient = $d['coefficient'];
            $evaluation->classe_id = $d['classe_id'];
            $evaluation->matiere_id = $d['matiere_id'];
            $evaluation->annee_universitaire_id = $d['annee_universitaire_id'];
            $evaluation->periode = $d['periode'];
            $evaluation->created_by = $user->id;
            // Même règle que l'écran : l'enseignant qui crée est l'enseignant de l'épreuve.
            if ($user->can('identity.teach')) {
                $evaluation->enseignant_id = $user->id;
            }
            $evaluation->is_published = (bool) $d['publier'];
            $evaluation->status = $evaluation->is_published
                ? $evaluation->determineAutomaticStatus(null, false)
                : ESBTPEvaluation::STATUS_DRAFT;
            $evaluation->save();

            return $evaluation;
        });

        return [
            'message' => "Évaluation « {$evaluation->titre} » créée" . ($evaluation->is_published ? ' et publiée' : ' en brouillon') . " (n° {$evaluation->id}).",
            'lien' => route('esbtp.evaluations.show', $evaluation->id, false),
            'model_type' => ESBTPEvaluation::class,
            'model_id' => (int) $evaluation->id,
            'details' => ['evaluation_id' => (int) $evaluation->id],
        ];
    }

    /** « S1 », « semestre1 », « Semestre 1 », « 1 » → « semestre1 ». Null si inconnu. */
    public static function normaliserPeriode(string $brute): ?string
    {
        $s = mb_strtolower(trim($brute));
        if (preg_match('/^(?:s|sem|semestre)?\s*(\d{1,2})$/u', $s, $m) !== 1) {
            return null;
        }
        $n = (int) $m[1];

        return $n >= 1 && $n <= 10 ? 'semestre' . $n : null;
    }

    /** @return array{0: Carbon, 1: Carbon}|null */
    public static function horaires(string $date, string $debut, string $fin): ?array
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1
            || preg_match('/^\d{1,2}:\d{2}$/', $debut) !== 1
            || preg_match('/^\d{1,2}:\d{2}$/', $fin) !== 1) {
            return null;
        }
        try {
            $a = Carbon::createFromFormat('!Y-m-d H:i', $date . ' ' . $debut);
            $b = Carbon::createFromFormat('!Y-m-d H:i', $date . ' ' . $fin);
        } catch (\Throwable) {
            return null;
        }
        // createFromFormat déborde en silence (31/02 → mars) : on le refuse.
        if ($a->format('Y-m-d') !== $date || $b->lessThanOrEqualTo($a)) {
            return null;
        }

        return [$a, $b];
    }

    public static function libellePeriode(string $periode): string
    {
        return 'Semestre ' . (int) preg_replace('/\D/', '', $periode);
    }

    /**
     * Un enseignant qui ne coordonne pas ne crée que pour une matière que la
     * maquette de la classe lui confie cette année — la règle déjà appliquée
     * par search_notes. Les autres profils passent par leur seule permission.
     */
    private function peutCreerPour($user, ESBTPClasse $classe, int $matiereId, int $anneeId): bool
    {
        if (! $user->can('identity.teach') || $user->can('identity.coordinate')) {
            return true;
        }

        return DB::table('esbtp_planifications_academiques as p')
            ->where('p.filiere_id', $classe->filiere_id)
            ->where('p.niveau_etude_id', $classe->niveau_etude_id)
            ->where('p.matiere_id', $matiereId)
            ->where('p.annee_universitaire_id', $anneeId)
            ->where('p.is_active', true)
            ->where(fn ($q) => $q->where('p.enseignant_principal_id', $user->id)
                ->orWhereExists(fn ($t) => $t->selectRaw('1')
                    ->from('esbtp_planification_teachers as pt')
                    ->join('esbtp_teachers as t', 't.id', '=', 'pt.teacher_id')
                    ->whereColumn('pt.planification_id', 'p.id')
                    ->where('t.user_id', $user->id)))
            ->exists();
    }

    private function evaluationsDuMemeJour(int $classeId, int $matiereId, int $anneeId, Carbon $jour)
    {
        return ESBTPEvaluation::query()
            ->where('classe_id', $classeId)
            ->where('matiere_id', $matiereId)
            ->where('annee_universitaire_id', $anneeId)
            ->whereDate('date_evaluation', $jour->toDateString())
            ->orderBy('id')
            ->get(['id', 'titre']);
    }

    private static function nombre(mixed $valeur): ?float
    {
        if (is_int($valeur) || is_float($valeur)) {
            return (float) $valeur;
        }
        $s = str_replace([',', ' ', "\u{00A0}"], ['.', '', ''], trim((string) $valeur));

        return $s !== '' && is_numeric($s) ? (float) $s : null;
    }

    private static function texte(float $n): string
    {
        return rtrim(rtrim(number_format($n, 2, ',', ''), '0'), ',');
    }

    private function manque(string $message): Proposition
    {
        return new Proposition(titre: 'Nouvelle évaluation', resume: '', manques: [$message]);
    }
}
