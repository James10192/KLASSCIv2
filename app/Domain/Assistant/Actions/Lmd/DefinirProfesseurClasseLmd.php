<?php

namespace App\Domain\Assistant\Actions\Lmd;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPLMDResultatECUE;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPTeacher;
use App\Services\LMD\EnseignantDeClasseLmd;
use Illuminate\Support\Facades\Route;

/**
 * Confirmation du professeur réel d'un ECUE pour UNE classe LMD.
 * C'est le même service que l'écran Professeurs des bulletins LMD : le pool,
 * les évaluations et les séances sont harmonisés, puis les brouillons de
 * bulletin reprennent immédiatement le professeur avec son titre académique.
 */
class DefinirProfesseurClasseLmd extends ActionAgent
{
    public function __construct(private readonly EnseignantDeClasseLmd $enseignants) {}

    public function cle(): string
    {
        return 'professeur_classe_lmd';
    }

    public function description(): string
    {
        return "PROPOSE de confirmer le professeur d'un ECUE pour une classe, une année et un semestre LMD. Utilise l'ID enseignant retourné par search_teachers. Cette confirmation aligne les évaluations/séances de la classe et rafraîchit le professeur affiché sur les bulletins LMD non publiés ; un bulletin déjà publié reste figé.";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'classe_id' => ['type' => 'integer'],
                'annee_universitaire_id' => ['type' => 'integer'],
                'semestre' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 10],
                'matiere_id' => ['type' => 'integer', 'description' => "ID de l'ECUE/matière."],
                'enseignant_id' => ['type' => 'integer', 'description' => 'ID du profil enseignant retourné par search_teachers.'],
            ],
            'required' => ['classe_id', 'annee_universitaire_id', 'semestre', 'matiere_id', 'enseignant_id'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $classe = ESBTPClasse::find((int) ($args['classe_id'] ?? 0));
        $annee = ESBTPAnneeUniversitaire::find((int) ($args['annee_universitaire_id'] ?? 0));
        $matiere = ESBTPMatiere::find((int) ($args['matiere_id'] ?? 0));
        $teacher = ESBTPTeacher::with('user')->find((int) ($args['enseignant_id'] ?? 0));
        $semestre = (int) ($args['semestre'] ?? 0);

        if (! $classe || ! $annee || ! $matiere || ! $teacher?->user || $semestre < 1 || $semestre > 10) {
            return new Proposition('Définir le professeur LMD', '', manques: ['Classe, année, semestre, ECUE ou enseignant introuvable. Utilisez les outils de recherche avant de proposer.']);
        }
        if (($classe->systeme_academique ?? '') !== 'LMD') {
            return new Proposition('Définir le professeur LMD', '', manques: ['La classe choisie n’est pas une classe LMD.']);
        }

        $resolution = $this->enseignants->resoudre($classe, (int) $matiere->id, (int) $annee->id, $semestre);
        if (! ($resolution['dans_maquette'] ?? false)) {
            return new Proposition('Définir le professeur LMD', '', manques: [$resolution['message'] ?? 'Cet ECUE ne fait pas partie de la maquette de ce semestre.']);
        }

        $avantId = (int) ($resolution['enseignant_id'] ?? 0);
        if ($avantId === (int) $teacher->user_id) {
            return Proposition::sansObjet('Définir le professeur LMD', $teacher->user->name.' est déjà le professeur résolu pour cet ECUE et cette classe.');
        }

        $avant = trim((string) ($resolution['enseignant_nom'] ?? '')) ?: 'Non défini / conflit';
        return new Proposition(
            titre: 'Confirmer le professeur LMD',
            resume: $teacher->user->name.' sera confirmé pour '.$matiere->name.' dans '.$classe->name.'.',
            tableau: ['colonnes' => ['Classe', 'Semestre', 'ECUE', 'Avant', 'Après'], 'lignes' => [[
                $classe->name, 'S'.$semestre, $matiere->name, $avant, $teacher->user->name,
            ]]],
            donnees: [
                'classe_id' => (int) $classe->id,
                'annee_universitaire_id' => (int) $annee->id,
                'semestre' => $semestre,
                'matiere_id' => (int) $matiere->id,
                'enseignant_user_id' => (int) $teacher->user_id,
                'enseignant_profile_id' => (int) $teacher->id,
            ],
            etat: [
                'classe_updated_at' => (string) $classe->updated_at,
                'matiere_updated_at' => (string) $matiere->updated_at,
                'enseignant_user_updated_at' => (string) $teacher->user->updated_at,
                'resolution_enseignant_id' => $avantId ?: null,
                'resolution_source' => (string) ($resolution['source'] ?? ''),
            ],
            avertissements: [
                'Les évaluations et séances existantes de CETTE classe et de cet ECUE seront harmonisées sur ce professeur.',
                'Les bulletins LMD déjà publiés restent figés ; seuls les brouillons/futurs bulletins reflètent cette confirmation.',
            ],
            risque: 'eleve',
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        if (! $user->can('lmd.planning.edit')) {
            throw new PropositionPerimee("Vous n'avez plus le droit de modifier les professeurs LMD.");
        }

        $d = $proposition->donnees;
        $classe = ESBTPClasse::find($d['classe_id']);
        $matiere = ESBTPMatiere::find($d['matiere_id']);
        $teacher = ESBTPTeacher::with('user')->find($d['enseignant_profile_id']);
        if (! $classe || ! $matiere || ! $teacher?->user || (int) $teacher->user_id !== (int) $d['enseignant_user_id']) {
            throw new PropositionPerimee('La classe, l’ECUE ou l’enseignant a changé depuis la proposition.');
        }

        $resolution = $this->enseignants->resoudre($classe, (int) $matiere->id, (int) $d['annee_universitaire_id'], (int) $d['semestre']);
        $courant = (int) ($resolution['enseignant_id'] ?? 0);
        if (($courant ?: null) !== ($proposition->etat['resolution_enseignant_id'] ?: null)
            || (string) ($resolution['source'] ?? '') !== $proposition->etat['resolution_source']) {
            throw new PropositionPerimee('La résolution du professeur a changé depuis la proposition. Relancez la demande.');
        }

        $resultat = $this->enseignants->confirmer(
            $classe,
            (int) $matiere->id,
            (int) $d['annee_universitaire_id'],
            (int) $d['semestre'],
            (int) $d['enseignant_user_id'],
            (int) $user->id,
        );

        // Même rafraîchissement que l'écran Professeurs : la sauvegarde recalcule
        // le snapshot enseignant des résultats ECUE tant que le bulletin est brouillon.
        ESBTPLMDResultatECUE::query()
            ->where('matiere_id', $matiere->id)
            ->whereHas('bulletin', fn ($q) => $q
                ->where('classe_id', $classe->id)
                ->where('annee_universitaire_id', $d['annee_universitaire_id'])
                ->where('semestre', $d['semestre'])
                ->where('is_published', false))
            ->with('bulletin')->get()->each->save();

        return [
            'message' => 'Professeur confirmé pour '.$classe->name.' — '.$matiere->name.'.',
            'lien' => Route::has('esbtp.lmd.bulletins.professeurs') ? route('esbtp.lmd.bulletins.professeurs', [
                'classe_id' => $classe->id,
                'annee_universitaire_id' => $d['annee_universitaire_id'],
                'semestre' => $d['semestre'],
            ], false) : null,
            'model_type' => ESBTPTeacher::class,
            'model_id' => $teacher->id,
            'details' => [
                'evaluations_harmonisees' => $resultat['evaluation_count'],
                'seances_harmonisees' => $resultat['seance_count'],
                'ajoute_au_pool' => $resultat['added_to_pool'],
            ],
        ];
    }
}
