<?php

declare(strict_types=1);

namespace App\Domain\Notes\Reclamations;

use App\Domain\AcademicPilotage\Services\CoverageTeacherContactResolver;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPReclamationNote;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Qui doit voir une réclamation : l'enseignant de l'évaluation, et les
 * membres du personnel qui portent « notes.reclamations.traiter ».
 *
 * L'enseignant n'est PAS désigné par une permission : c'est le correcteur de
 * cette évaluation. Ordre de résolution, du plus sûr au plus large :
 *
 *   1. `esbtp_evaluations.enseignant_id`, posé à la création du devoir ;
 *   2. l'auteur de l'évaluation, s'il enseigne (`identity.teach`) — une
 *      secrétaire qui saisit pour le compte d'un professeur n'est pas le
 *      correcteur ;
 *   3. l'enseignant que le planning, le bulletin ou les autres évaluations
 *      désignent pour cette matière dans cette classe — et seulement s'il y en
 *      a un seul (CoverageTeacherContactResolver ne choisit jamais au hasard).
 *
 * Sans enseignant trouvé, la réclamation part quand même au personnel habilité : ne
 * pas savoir qui a corrigé ne doit pas bloquer l'élève.
 */
final class DestinatairesReclamation
{
    public const PERMISSION_TRAITER = 'notes.reclamations.traiter';

    public function __construct(
        private readonly CoverageTeacherContactResolver $enseignantsDeLaClasse,
    ) {}

    public function enseignantDe(ESBTPEvaluation $evaluation): ?User
    {
        if ($evaluation->enseignant_id && ($u = User::find($evaluation->enseignant_id))) {
            return $u;
        }

        if ($evaluation->created_by && ($u = User::find($evaluation->created_by)) && $u->can('identity.teach')) {
            return $u;
        }

        $classe = $evaluation->classe;
        $matiere = $evaluation->matiere;
        if (! $classe || ! $matiere) {
            return null;
        }

        $semestre = preg_match('/([12])/', (string) $evaluation->periode, $m) ? (int) $m[1] : null;
        $carte = $this->enseignantsDeLaClasse->pourLaClasse(
            $classe,
            (int) $evaluation->annee_universitaire_id,
            $semestre,
            collect([$matiere])
        );
        $id = $carte[(int) $matiere->id]['id'] ?? null;

        return $id ? User::find($id) : null;
    }

    /**
     * Les personnes qui tranchent : le personnel qui porte la permission, quel
     * que soit son rôle. Aucun rôle n'est désigné par le logiciel ; chaque
     * école donne la permission à qui elle veut (rule customizable-roles).
     *
     * Tant que personne ne la porte, les super-administrateurs sont prévenus
     * à la place, et on le journalise : une réclamation ne doit partir dans le
     * vide. Gate::before leur ouvre déjà la page de traitement.
     *
     * @return Collection<int, User>
     */
    public function traitants(): Collection
    {
        try {
            $porteurs = User::permission(self::PERMISSION_TRAITER)->where('is_active', true)->get();
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
            $porteurs = collect();
        }

        if ($porteurs->isNotEmpty()) {
            return $porteurs;
        }

        \Log::warning('[reclamations] personne ne porte la permission ; super-administrateurs prévenus à la place', [
            'permission' => self::PERMISSION_TRAITER,
        ]);

        try {
            return User::role('superAdmin')->where('is_active', true)->get();
        } catch (\Spatie\Permission\Exceptions\RoleDoesNotExist $e) {
            return collect();
        }
    }

    /** L'utilisateur peut-il ouvrir cette réclamation côté personnel ? */
    public function peutVoir(User $user, ESBTPReclamationNote $reclamation): bool
    {
        return $user->can(self::PERMISSION_TRAITER)
            || ($reclamation->enseignant_id !== null && (int) $reclamation->enseignant_id === (int) $user->id);
    }

    public function estLEnseignant(User $user, ESBTPReclamationNote $reclamation): bool
    {
        return $reclamation->enseignant_id !== null && (int) $reclamation->enseignant_id === (int) $user->id;
    }
}
