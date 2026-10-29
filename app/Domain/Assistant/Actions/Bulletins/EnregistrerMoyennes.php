<?php

namespace App\Domain\Assistant\Actions\Bulletins;

use App\Domain\Academique\CoherenceSystemeAcademique;
use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Designations;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Domain\Notes\SaisieDeMoyennes;
use App\Models\ESBTPClasse;
use App\Models\ESBTPInscription;
use App\Models\ESBTPResultat;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Enregistrer ou retirer des moyennes de matière d'UN étudiant (BTS), sur une
 * classe et un semestre : SaisieDeMoyennes, le même chemin que l'écran
 * « Modifier les moyennes » et que `POST /api/cli/resultats/moyennes`.
 * Une moyenne enregistrée l'emporte sur les notes au bulletin : motif obligatoire.
 */
class EnregistrerMoyennes extends ActionAgent
{
    use Designations;

    private const MAX = 40;

    public function __construct(private SaisieDeMoyennes $saisie)
    {
    }

    public function cle(): string
    {
        return 'saisie_moyennes';
    }

    public function libelle(): string
    {
        return 'Préparation des moyennes à enregistrer…';
    }

    public function description(): string
    {
        return "PROPOSE d'enregistrer (ou de retirer, retirer: true) des moyennes de matière d'UN étudiant BTS sur une classe et un semestre. "
            . "Une moyenne enregistrée l'emporte sur les notes au bulletin : quand la matière a des notes, préfère proposer_correction_notes. "
            . "Valeurs exactement comme données, sur 20 ; motif obligatoire. Classe LMD : refusée.";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'etudiant_id' => ['type' => 'integer'],
                'matricule' => ['type' => 'string'],
                'classe' => ['type' => 'string', 'description' => 'Code ou identifiant de la classe.'],
                'periode' => ['type' => 'string', 'description' => 'S1 ou S2.'],
                'annee' => ['type' => 'string', 'description' => "Libellé exact de l'année. Omettre pour l'année courante."],
                'motif' => ['type' => 'string', 'description' => 'La réclamation ou la décision, telle que dite (10 caractères au moins).'],
                'moyennes' => [
                    'type' => 'array',
                    'items' => ['type' => 'object', 'properties' => [
                        'matiere_id' => ['type' => 'integer'],
                        'matiere' => ['type' => 'string', 'description' => 'Code ou intitulé exact, si l\'identifiant est inconnu.'],
                        'moyenne' => ['type' => 'number', 'description' => 'Sur 20.'],
                        'retirer' => ['type' => 'boolean', 'description' => 'true pour retirer la moyenne enregistrée (les notes reprennent la main), sans moyenne.'],
                    ]],
                ],
            ],
            'required' => ['classe', 'periode', 'motif', 'moyennes'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = 'Saisie de moyennes';
        if (! $user->can('bulletins.edit')) {
            return $this->seulManque($titre, "Cet utilisateur n'a pas le droit de modifier les moyennes des bulletins.");
        }
        [$etudiant, $mEtudiant] = $this->designerEtudiant($args);
        [$classe, $mClasse] = $this->designerClasse($args['classe_id'] ?? $args['classe'] ?? '');
        [$annee, $mAnnee] = $this->designerAnnee($args);
        $periode = $this->designerSemestre($args['periode'] ?? '');
        $motif = trim((string) ($args['motif'] ?? ''));
        $manques = array_values(array_filter([$mEtudiant, $mClasse, $mAnnee,
            $periode === null ? 'Quel semestre (S1 ou S2) ?' : null,
            mb_strlen($motif) < 10 ? 'Quel est le motif (réclamation, décision du conseil…) ? Il est journalisé.' : null,
        ]));
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: $manques);
        }
        if (CoherenceSystemeAcademique::classeEstLmd($classe->systeme_academique)) {
            return $this->seulManque($titre, 'Classe LMD : ses relevés passent par /esbtp/lmd/bulletins, pas par une moyenne de matière.');
        }
        if (! $this->estInscrit($etudiant->id, $classe->id, $annee->id)) {
            return $this->seulManque($titre, "L'étudiant n'est pas inscrit dans « {$classe->name} » pour {$annee->name}.");
        }

        [$lignes, $manques] = $this->lignes((array) ($args['moyennes'] ?? []));
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: $manques);
        }

        try {
            $rapport = $this->saisie->appliquer((int) $etudiant->id, $classe, (int) $annee->id, $periode, $lignes, true, (int) $user->id);
        } catch (ValidationException $e) {
            return $this->seulManque($titre, collect($e->errors())->flatten()->implode(' '));
        }
        $effectives = array_values(array_filter($rapport['lignes'], fn ($l) => in_array($l['action'], ['creee', 'modifiee', 'retiree'], true)));
        if ($effectives === []) {
            return Proposition::sansObjet($titre, 'Ces moyennes sont déjà enregistrées (ou déjà absentes).');
        }
        $effets = ['creee' => 'Enregistrée', 'modifiee' => 'Remplacée', 'retiree' => 'Retirée (les notes reprennent la main)'];

        return new Proposition(
            titre: 'Moyennes de ' . trim($etudiant->nom . ' ' . $etudiant->prenoms),
            resume: sprintf('%d moyenne(s) pour %s (%s), %s, %s, %s. Motif : %s', count($effectives), trim($etudiant->nom . ' ' . $etudiant->prenoms),
                $etudiant->matricule, $classe->name, $this->libelleSemestre($periode), $annee->name, $motif),
            tableau: [
                'colonnes' => ['Matière', 'Avant', 'Après', 'Effet'],
                'lignes' => array_map(fn ($l) => [(string) $l['matiere'], $this->nombre($l['avant']), $this->nombre($l['apres']), $effets[$l['action']]], $effectives),
            ],
            avertissements: array_values(array_filter([
                'Une moyenne enregistrée l\'emporte sur les notes de la matière au bulletin.',
                $rapport['bulletins_a_regenerer'] !== [] ? count($rapport['bulletins_a_regenerer']) . ' bulletin(s) déjà généré(s) garderont l\'ancienne moyenne tant qu\'ils ne sont pas régénérés.' : null,
            ])),
            donnees: [
                'etudiant_id' => (int) $etudiant->id, 'classe_id' => (int) $classe->id, 'annee_universitaire_id' => (int) $annee->id,
                'periode' => $periode, 'moyennes' => $lignes, 'motif' => $motif,
            ],
            etat: ['lignes' => $rapport['lignes']],
            risque: 'eleve',
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        if (! $user->can('bulletins.edit')) {
            throw new PropositionPerimee("Vous n'avez plus le droit de modifier ces moyennes.");
        }
        $d = $proposition->donnees;
        $classe = ESBTPClasse::find($d['classe_id']);
        if (! $classe || ! $this->estInscrit($d['etudiant_id'], $d['classe_id'], $d['annee_universitaire_id'])) {
            throw new PropositionPerimee("L'inscription de l'étudiant dans cette classe a changé.");
        }
        $appliquer = fn (bool $simuler) => $this->saisie->appliquer((int) $d['etudiant_id'], $classe, (int) $d['annee_universitaire_id'], $d['periode'], $d['moyennes'], $simuler, (int) $user->id);
        try {
            if ($appliquer(true)['lignes'] !== $proposition->etat['lignes']) {
                throw new PropositionPerimee('Ces moyennes ont changé depuis la proposition.');
            }
            $resultat = $appliquer(false);
        } catch (ValidationException $e) {
            throw new PropositionPerimee(collect($e->errors())->flatten()->implode(' '));
        }

        Log::warning('assistant: moyennes enregistrees', [
            'etudiant_id' => $d['etudiant_id'], 'classe' => $classe->name, 'periode' => $d['periode'],
            'annee_universitaire_id' => $d['annee_universitaire_id'], 'motif' => $d['motif'],
            'lignes' => $resultat['lignes'], 'user_id' => $user->id,
        ]);
        $aRegenerer = count($resultat['bulletins_a_regenerer']);

        return [
            'message' => 'Moyennes enregistrées.' . ($aRegenerer > 0 ? " Régénérez {$aRegenerer} bulletin(s) pour qu'ils en tiennent compte." : ''),
            'lien' => route('esbtp.bulletins.select', [], false),
            'model_type' => ESBTPResultat::class,
            'model_id' => null,
            'details' => ['lignes' => $resultat['lignes'], 'bulletins_a_regenerer' => $resultat['bulletins_a_regenerer']],
        ];
    }

    /** @return array{0: list<array{matiere_id: int, moyenne: ?float}>, 1: string[]} */
    private function lignes(array $saisies): array
    {
        $lignes = [];
        $manques = [];
        if ($saisies === [] || count($saisies) > self::MAX) {
            return [[], [$saisies === [] ? 'Quelles moyennes ?' : 'Trop de matières en une fois (' . self::MAX . ' au plus).']];
        }
        foreach ($saisies as $s) {
            $s = (array) $s;
            [$matiere, $manque] = $this->designerMatiere($s['matiere_id'] ?? 0, $s['matiere'] ?? '');
            if (! $matiere) {
                $manques[] = $manque;
                continue;
            }
            $retirer = filter_var($s['retirer'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if (! $retirer && ! isset($s['moyenne'])) {
                $manques[] = "{$matiere->name} : quelle moyenne (ou faut-il la retirer) ?";
                continue;
            }
            $m = $retirer ? null : $s['moyenne'];
            if ($m !== null && (! is_numeric($m) || (float) $m < 0 || (float) $m > 20)) {
                $manques[] = "{$matiere->name} : « {$m} » n'est pas une moyenne entre 0 et 20.";
                continue;
            }
            if (isset($lignes[$matiere->id])) {
                $manques[] = "{$matiere->name} apparaît deux fois : quelle valeur garder ?";
                continue;
            }
            $lignes[$matiere->id] = ['matiere_id' => (int) $matiere->id, 'moyenne' => $m === null ? null : (float) $m];
        }

        return [array_values($lignes), $manques];
    }

    private function estInscrit(int $etudiantId, int $classeId, int $anneeId): bool
    {
        return ESBTPInscription::where('etudiant_id', $etudiantId)->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeId)->exists();
    }
}
