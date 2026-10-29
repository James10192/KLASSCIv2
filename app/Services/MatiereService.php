<?php

namespace App\Services;

use App\Models\ESBTPEtudiant;
use App\Models\ESBTPMatiere;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MatiereService
{
    /**
     * Get all subjects available for a student
     *
     * @param ESBTPEtudiant $etudiant
     * @return Collection
     */
    public function getMatieresForStudent(ESBTPEtudiant $etudiant): Collection
    {
        // Clear the cache for debugging
        $cacheKey = "student_matieres_{$etudiant->id}";
        Cache::forget($cacheKey);

        return Cache::remember($cacheKey, 60 * 24, function () use ($etudiant) {
            $inscription = $etudiant->inscription_active;

            if (!$inscription || !$inscription->classe) {
                Log::debug('Student has no active inscription or class', [
                    'student_id' => $etudiant->id,
                    'has_inscription' => $inscription ? 'yes' : 'no',
                    'has_class' => ($inscription && $inscription->classe) ? 'yes' : 'no'
                ]);
                return collect();
            }

            $classe = $inscription->classe;

            Log::debug('Getting subjects for student', [
                'student_id' => $etudiant->id,
                'class_id' => $classe->id,
                'filiere_id' => $classe->filiere_id ?? 'none',
                'niveau_id' => $classe->niveau_etude_id ?? 'none'
            ]);

            $matieres = collect();

            // 1. Get subjects directly from the student's class
            $matieresClasse = ESBTPMatiere::whereHas('classes', function($query) use ($classe) {
                $query->where('esbtp_classes.id', $classe->id);
            })->get();

            Log::debug('Subjects from class', [
                'count' => $matieresClasse->count(),
                'subjects' => $matieresClasse->pluck('name')->toArray()
            ]);

            $matieres = $matieres->concat($matieresClasse);

            // 2. Get subjects from the student's filière
            if ($classe->filiere_id) {
                $matieresFiliere = ESBTPMatiere::whereHas('filieres', function($query) use ($classe) {
                    $query->where('esbtp_filieres.id', $classe->filiere_id);
                })->get();

                Log::debug('Subjects from filière', [
                    'filiere_id' => $classe->filiere_id,
                    'count' => $matieresFiliere->count(),
                    'subjects' => $matieresFiliere->pluck('name')->toArray()
                ]);

                $matieres = $matieres->concat($matieresFiliere);
            }

            // 3. Get subjects from the student's niveau
            if ($classe->niveau_etude_id) {
                $matieresNiveau = ESBTPMatiere::whereHas('niveaux', function($query) use ($classe) {
                    $query->where('esbtp_niveau_etudes.id', $classe->niveau_etude_id);
                })->get();

                Log::debug('Subjects from niveau', [
                    'niveau_id' => $classe->niveau_etude_id,
                    'count' => $matieresNiveau->count(),
                    'subjects' => $matieresNiveau->pluck('name')->toArray()
                ]);

                $matieres = $matieres->concat($matieresNiveau);
            }

            // 4. Get subjects from seances
            $matieresSeances = ESBTPMatiere::whereHas('seancesCours', function($query) use ($classe) {
                $query->where('classe_id', $classe->id);
            })->get();

            Log::debug('Subjects from seances', [
                'count' => $matieresSeances->count(),
                'subjects' => $matieresSeances->pluck('name')->toArray()
            ]);

            $matieres = $matieres->concat($matieresSeances);

            // If no subjects found through relationships, get all active subjects
            if ($matieres->isEmpty()) {
                Log::debug('No subjects found through relationships, getting all active subjects');
                $matieres = ESBTPMatiere::where('is_active', true)->get();
            }

            // Remove duplicates and sort by name
            $result = $matieres->unique('id')->sortBy('name')->values();

            Log::debug('Final subjects list', [
                'total_count' => $result->count(),
                'subjects' => $result->pluck('name')->toArray()
            ]);

            return $result;
        });
    }

    /**
     * Get subjects formatted for select input
     *
     * @param ESBTPEtudiant $etudiant
     * @return array
     */
    public function getMatieresForSelect(ESBTPEtudiant $etudiant): array
    {
        $matieres = $this->getMatieresForStudent($etudiant);
        $result = ['all' => 'Toutes les matières'];

        if ($matieres->isNotEmpty()) {
            $result += $matieres->pluck('name', 'id')->toArray();
        }

        Log::debug('Select options', [
            'count' => count($result),
            'options' => $result
        ]);

        return $result;
    }
}
