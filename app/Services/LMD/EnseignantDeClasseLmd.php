<?php

namespace App\Services\LMD;

use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPTeacher;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Résout l'enseignant RÉEL d'un ECUE pour une classe LMD.
 *
 * La planification porte un pool d'enseignants possible pour le parcours / niveau
 * / semestre. Une classe choisit ensuite son enseignant dans ce pool. Les traces
 * déjà présentes sur ses évaluations et ses séances servent de preuve : si elles
 * convergent, KLASSCI sait qui enseigne ; si elles divergent, une confirmation
 * explicite est demandée au lieu de choisir arbitrairement le « principal ».
 */
final class EnseignantDeClasseLmd
{
    public function __construct(private readonly EnseignantDePlanificationLmd $planning) {}

    /**
     * @return array<string,mixed>
     */
    public function resoudre(
        ESBTPClasse $classe,
        int $matiereId,
        int $anneeId,
        mixed $periode = null,
    ): array {
        $semestre = $this->semestre($classe, $matiereId, $anneeId, $periode);
        if ($semestre === null) {
            return $this->vide('Impossible de rattacher cet ECUE à un semestre LMD de cette classe.');
        }

        $plan = $this->planning->resoudre($classe, $matiereId, $anneeId, $semestre, false);
        if (! $plan['applicable'] || ! $plan['dans_maquette']) {
            return array_merge($this->vide($plan['message'] ?? 'ECUE LMD invalide.'), [
                'applicable' => $plan['applicable'],
                'dans_maquette' => $plan['dans_maquette'],
                'semestre' => $semestre,
                'planification' => $plan['planification'],
            ]);
        }

        /** @var Collection<int,User> $pool */
        $pool = $plan['enseignants']->filter()->unique('id')->values();
        $evaluations = $this->preuvesEvaluations($classe, $matiereId, $anneeId, $semestre);
        $seances = $this->preuvesSeances($classe, $matiereId, $anneeId);

        $preuves = $evaluations['users']->merge($seances['users'])->filter()->unique('id')->values();
        $candidats = $pool->merge($preuves)->filter()->unique('id')->values();
        $externes = $evaluations['externes'];

        $selection = null;
        $source = 'aucun';
        $confirmation = true;
        $message = null;

        if ($preuves->count() === 1) {
            $selection = $preuves->first();
            $source = 'classe';
            $confirmation = false;
            $message = $pool->contains('id', $selection->id)
                ? 'Évaluations et/ou séances de cette classe confirment cet enseignant.'
                : 'Cet enseignant est déjà utilisé par la classe mais n’est pas encore dans le pool du planning.';
        } elseif ($preuves->count() > 1) {
            $source = 'conflit';
            $message = 'Plusieurs enseignants différents existent déjà sur les évaluations ou séances de cette classe. Confirmez le vrai professeur pour harmoniser les données.';
        } elseif ($pool->count() === 1) {
            $selection = $pool->first();
            $source = 'planning_unique';
            $confirmation = false;
            $message = 'Un seul enseignant est prévu dans le planning : il est proposé automatiquement pour cette classe.';
        } elseif ($pool->count() > 1) {
            $source = 'planning_pool';
            $message = 'Plusieurs enseignants sont prévus dans le planning. Choisissez celui qui assure cet ECUE pour cette classe.';
        } else {
            $message = 'Aucun enseignant n’est encore prévu dans le planning pour cet ECUE.';
        }

        if ($externes->isNotEmpty() && $preuves->isEmpty()) {
            $source = 'nom_externe';
            $message = 'Un nom d’enseignant a été saisi sur une ancienne évaluation sans compte KLASSCI. Recherchez un enseignant existant ou confirmez une nouvelle personne.';
        }

        return [
            'applicable' => true,
            'dans_maquette' => true,
            'semestre' => $semestre,
            'planification' => $plan['planification'],
            'pool' => $pool,
            'preuves' => $preuves,
            'candidats' => $candidats,
            'enseignant' => $selection,
            'enseignant_id' => $selection?->id,
            'enseignant_nom' => $selection?->name,
            'source' => $source,
            'confirmation_requise' => $confirmation,
            'conflit' => $preuves->count() > 1,
            'noms_externes' => $externes,
            'details' => [
                'evaluations' => $evaluations['details'],
                'seances' => $seances['details'],
            ],
            'message' => $message,
        ];
    }

    /**
     * Confirme le professeur d'une classe et remet toutes ses traces d'accord.
     * Le professeur est ajouté au pool du planning sans retirer les collègues
     * affectés aux autres classes du même parcours.
     *
     * @return array{evaluation_count:int,seance_count:int,teacher_profile_id:int|null,added_to_pool:bool}
     */
    public function confirmer(
        ESBTPClasse $classe,
        int $matiereId,
        int $anneeId,
        mixed $periode,
        int $enseignantUserId,
        int $auteurId,
    ): array {
        $resolution = $this->resoudre($classe, $matiereId, $anneeId, $periode);
        if (! $resolution['dans_maquette']) {
            throw ValidationException::withMessages(['matiere_id' => $resolution['message'] ?? 'ECUE LMD invalide.']);
        }

        $user = User::find($enseignantUserId);
        if (! $user || ! $user->hasRole('enseignant')) {
            throw ValidationException::withMessages(['enseignant_id' => 'La personne choisie n’est pas un enseignant KLASSCI.']);
        }

        /** @var ESBTPPlanificationAcademique|null $planification */
        $planification = $resolution['planification'];
        if (! $planification) {
            throw ValidationException::withMessages([
                'planification' => 'Configurez d’abord cet ECUE dans le planning LMD avant de confirmer son professeur.',
            ]);
        }

        $teacher = ESBTPTeacher::where('user_id', $enseignantUserId)->first();
        $semestre = (int) $resolution['semestre'];
        $addedToPool = false;
        $evaluationCount = 0;
        $seanceCount = 0;

        DB::transaction(function () use (
            $planification, $enseignantUserId, $auteurId, $classe, $matiereId,
            $anneeId, $semestre, $teacher, &$addedToPool, &$evaluationCount, &$seanceCount
        ): void {
            $locked = ESBTPPlanificationAcademique::whereKey($planification->id)->lockForUpdate()->firstOrFail();
            $poolIds = collect([$locked->enseignant_principal_id])
                ->merge($locked->enseignants_secondaires ?? [])
                ->filter()->map(fn ($id) => (int) $id)->unique()->values();

            if (! $poolIds->contains($enseignantUserId)) {
                if (! $locked->enseignant_principal_id) {
                    $locked->enseignant_principal_id = $enseignantUserId;
                } else {
                    $locked->enseignants_secondaires = collect($locked->enseignants_secondaires ?? [])
                        ->push($enseignantUserId)->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
                }
                $locked->updated_by = $auteurId;
                $locked->save();
                $addedToPool = true;
            }

            if ($teacher) {
                $locked->teachers()->syncWithoutDetaching([$teacher->id]);
            }

            $periodes = $this->periodes($semestre);
            $evaluationCount = ESBTPEvaluation::query()
                ->where('classe_id', $classe->id)
                ->where('matiere_id', $matiereId)
                ->where('annee_universitaire_id', $anneeId)
                ->whereIn('periode', $periodes)
                ->where('status', '!=', ESBTPEvaluation::STATUS_CANCELLED)
                ->update([
                    'enseignant_id' => $enseignantUserId,
                    'enseignant_externe_nom' => null,
                    'updated_at' => now(),
                ]);

            if ($teacher) {
                $seanceCount = ESBTPSeanceCours::query()
                    ->where('classe_id', $classe->id)
                    ->where('matiere_id', $matiereId)
                    ->where('annee_universitaire_id', $anneeId)
                    ->update(['teacher_id' => $teacher->id, 'updated_at' => now()]);
            }
        });

        return [
            'evaluation_count' => $evaluationCount,
            'seance_count' => $seanceCount,
            'teacher_profile_id' => $teacher?->id,
            'added_to_pool' => $addedToPool,
        ];
    }

    public function candidatAutorise(array $resolution, int $userId): bool
    {
        return collect($resolution['candidats'] ?? [])->contains(fn ($user) => (int) $user->id === $userId);
    }

    private function semestre(ESBTPClasse $classe, int $matiereId, int $anneeId, mixed $periode): ?int
    {
        if ($periode !== null && $periode !== '') {
            $numero = $this->numeroSemestre($periode);
            if ($numero !== null) {
                return $numero;
            }
        }

        foreach ($classe->getSemestresLMD() as $semestre) {
            $r = $this->planning->resoudre($classe, $matiereId, $anneeId, $semestre, false);
            if ($r['dans_maquette']) {
                return (int) $semestre;
            }
        }

        return null;
    }

    /** @return array{users:Collection<int,User>,externes:Collection<int,string>,details:Collection<int,array<string,mixed>>} */
    private function preuvesEvaluations(ESBTPClasse $classe, int $matiereId, int $anneeId, int $semestre): array
    {
        $evaluations = ESBTPEvaluation::query()
            ->where('classe_id', $classe->id)
            ->where('matiere_id', $matiereId)
            ->where('annee_universitaire_id', $anneeId)
            ->whereIn('periode', $this->periodes($semestre))
            ->where('status', '!=', ESBTPEvaluation::STATUS_CANCELLED)
            ->with('enseignant:id,name,email')
            ->get();

        return [
            'users' => $evaluations->pluck('enseignant')->filter(fn (User $u) => $u->hasAnyRole(['enseignant', 'teacher']))->unique('id')->values(),
            'externes' => $evaluations->pluck('enseignant_externe_nom')->map(fn ($n) => trim((string) $n))->filter()->unique()->values(),
            'details' => $evaluations->map(fn (ESBTPEvaluation $e) => [
                'id' => $e->id,
                'titre' => $e->titre,
                'enseignant_id' => $e->enseignant_id,
                'enseignant_nom' => $e->enseignant?->name ?: $e->enseignant_externe_nom,
            ])->values(),
        ];
    }

    /** @return array{users:Collection<int,User>,details:Collection<int,array<string,mixed>>} */
    private function preuvesSeances(ESBTPClasse $classe, int $matiereId, int $anneeId): array
    {
        $seances = ESBTPSeanceCours::query()
            ->where('classe_id', $classe->id)
            ->where('matiere_id', $matiereId)
            ->where('annee_universitaire_id', $anneeId)
            ->whereNotNull('teacher_id')
            ->with('teacher.user:id,name,email')
            ->get();

        return [
            'users' => $seances->map(fn (ESBTPSeanceCours $s) => $s->teacher?->user)->filter()->unique('id')->values(),
            'details' => $seances->map(fn (ESBTPSeanceCours $s) => [
                'id' => $s->id,
                'teacher_id' => $s->teacher_id,
                'enseignant_id' => $s->teacher?->user_id,
                'enseignant_nom' => $s->teacher?->user?->name,
            ])->values(),
        ];
    }

    private function periodes(int $semestre): array
    {
        return [(string) $semestre, 'semestre'.$semestre, 'S'.$semestre, 'Semestre '.$semestre, 'semestre '.$semestre];
    }

    private function numeroSemestre(mixed $periode): ?int
    {
        if (is_int($periode) || (is_string($periode) && preg_match('/^\d{1,2}$/', $periode))) {
            return (int) $periode;
        }

        return is_string($periode) && preg_match('/(\d{1,2})/', $periode, $m) ? (int) $m[1] : null;
    }

    private function vide(?string $message): array
    {
        return [
            'applicable' => false,
            'dans_maquette' => false,
            'semestre' => null,
            'planification' => null,
            'pool' => collect(),
            'preuves' => collect(),
            'candidats' => collect(),
            'enseignant' => null,
            'enseignant_id' => null,
            'enseignant_nom' => null,
            'source' => 'aucun',
            'confirmation_requise' => true,
            'conflit' => false,
            'noms_externes' => collect(),
            'details' => ['evaluations' => collect(), 'seances' => collect()],
            'message' => $message,
        ];
    }
}
