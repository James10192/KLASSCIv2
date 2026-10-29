<?php

namespace App\Http\Controllers;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\ESBTPTeacher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Reconduit une CONFIGURATION du planning general BTS, jamais des seances datees
 * ni des fiches de personnel. Aucune modification des lignes cible preexistantes.
 */
final class ESBTPPlanningYearCarryoverController extends Controller
{
    public function preview(Request $request): JsonResponse
    {
        $this->authorize('planning.manage');
        [$sourceYear, $targetYear] = $this->years($request);
        $source = $this->sourceRows($sourceYear->id);
        $existing = $this->targetKeys($targetYear->id);

        return response()->json([
            'success' => true,
            'source' => $sourceYear->name,
            'target' => $targetYear->name,
            'source_count' => $source->count(),
            'to_import' => $source->filter(fn ($row) => ! $existing->has($this->key($row)))->count(),
            'already_present' => $source->filter(fn ($row) => $existing->has($this->key($row)))->count(),
            'message' => 'Les configurations deja presentes pour l\'annee cible ne seront jamais ecrasees. Les seances et dates ne sont pas copiees.',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('planning.manage');
        [$sourceYear, $targetYear] = $this->years($request);

        $result = DB::transaction(function () use ($sourceYear, $targetYear) {
            $source = $this->sourceRows($sourceYear->id);
            if ($source->count() > 1000) {
                throw ValidationException::withMessages([
                    'source_annee_id' => 'Plus de 1 000 configurations. Contactez le support pour une reprise par lots.',
                ]);
            }

            // Lignes soft-deleted comprises : aucune resurrection ni ecrasement implicites.
            $existing = $this->targetKeys($targetYear->id, true);
            $activeTeachers = ESBTPTeacher::query()
                ->where('status', 'active')
                ->where('is_active', true)
                ->whereNotNull('user_id')
                ->get(['id', 'user_id']);
            $byId = $activeTeachers->keyBy('id');
            $byUserId = $activeTeachers->keyBy('user_id');

            $created = 0;
            $skipped = 0;
            $teachersLinked = 0;
            foreach ($source as $old) {
                $key = $this->key($old);
                if ($existing->has($key)) {
                    $skipped++;
                    continue;
                }

                // Le pool canonique a des IDs User, le pivot historique a des IDs ESBTPTeacher.
                $candidateIds = $old->teachers->pluck('id');
                if ($old->enseignant_principal_id && $byUserId->has($old->enseignant_principal_id)) {
                    $candidateIds->push($byUserId->get($old->enseignant_principal_id)->id);
                }
                foreach ($old->enseignants_secondaires ?? [] as $userId) {
                    if ($byUserId->has($userId)) {
                        $candidateIds->push($byUserId->get($userId)->id);
                    }
                }

                $selectedTeachers = $candidateIds->unique()->map(fn ($id) => $byId->get($id))
                    ->filter()->values();
                $userIds = $selectedTeachers->pluck('user_id')->unique()->values();
                $principalId = $userIds->contains($old->enseignant_principal_id)
                    ? (int) $old->enseignant_principal_id
                    : ($userIds->first() ? (int) $userIds->first() : null);

                $new = ESBTPPlanificationAcademique::create([
                    'annee_universitaire_id' => $targetYear->id,
                    'filiere_id' => $old->filiere_id,
                    'niveau_etude_id' => $old->niveau_etude_id,
                    'matiere_id' => $old->matiere_id,
                    'semestre' => $old->semestre,
                    'volume_horaire_total' => $old->volume_horaire_total,
                    'volume_horaire_cm' => $old->volume_horaire_cm,
                    'volume_horaire_td' => $old->volume_horaire_td,
                    'volume_horaire_tp' => $old->volume_horaire_tp,
                    'volume_horaire_projet' => $old->volume_horaire_projet,
                    'volume_horaire_tpe' => $old->volume_horaire_tpe,
                    'coefficient' => $old->coefficient,
                    'credits_ects' => $old->credits_ects,
                    'enseignant_principal_id' => $principalId,
                    'enseignants_secondaires' => $userIds->reject(fn ($id) => (int) $id === $principalId)->values()->all(),
                    'statut' => ESBTPPlanificationAcademique::STATUT_BROUILLON,
                    'is_active' => true,
                    'heures_effectuees' => 0,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
                // Assure la coherence meme pour un ancien pool depourvu de representation canonique.
                $new->teachers()->sync($selectedTeachers->pluck('id')->all());
                $teachersLinked += $selectedTeachers->count();
                $created++;
                $existing->put($key, true);
            }

            return compact('created', 'skipped', 'teachersLinked');
        }, 3);

        return response()->json([
            'success' => true,
            'message' => "{$result['created']} configuration(s) reprise(s), {$result['skipped']} deja presente(s). Verifiez les affectations avant validation.",
            'created' => $result['created'],
            'skipped' => $result['skipped'],
            'teacher_links' => $result['teachersLinked'],
        ]);
    }

    private function years(Request $request): array
    {
        $data = $request->validate([
            'source_annee_id' => ['required', 'integer', 'exists:esbtp_annee_universitaires,id'],
            'target_annee_id' => ['required', 'integer', 'different:source_annee_id', 'exists:esbtp_annee_universitaires,id'],
        ]);
        $source = ESBTPAnneeUniversitaire::findOrFail($data['source_annee_id']);
        $target = ESBTPAnneeUniversitaire::findOrFail($data['target_annee_id']);
        if (! $source->start_date || ! $target->start_date || ! $target->start_date->gt($source->start_date)) {
            throw ValidationException::withMessages([
                'source_annee_id' => 'Choisissez une annee source anterieure a l\'annee cible.',
            ]);
        }
        return [$source, $target];
    }

    private function sourceRows(int $yearId)
    {
        // Le planning general vise les matieres BTS, pas les ECUE du LMD.
        return ESBTPPlanificationAcademique::query()
            ->where('annee_universitaire_id', $yearId)
            ->whereHas('matiere', fn ($q) => $q->btsOnly())
            ->with('teachers')
            ->orderBy('id')
            ->get();
    }

    private function targetKeys(int $yearId, bool $lock = false)
    {
        $query = ESBTPPlanificationAcademique::withTrashed()
            ->where('annee_universitaire_id', $yearId);
        if ($lock) {
            $query->lockForUpdate();
        }
        return $query->get()->mapWithKeys(fn ($row) => [$this->key($row) => true]);
    }

    private function key(ESBTPPlanificationAcademique $plan): string
    {
        return implode(':', [
            $plan->filiere_id, $plan->niveau_etude_id, $plan->matiere_id,
            $plan->semestre ?? 'null',
        ]);
    }
}
