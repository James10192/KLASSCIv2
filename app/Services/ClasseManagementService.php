<?php

namespace App\Services;

use App\Models\ESBTPClasse;
use App\Models\ESBTPNiveauEtude;

class ClasseManagementService
{
    /**
     * Types de niveaux consideres comme LMD.
     */
    public const LMD_TYPES = ['Licence', 'Master', 'Doctorat'];

    /**
     * Determiner le systeme academique a partir du type de niveau.
     */
    public static function determinerSystemeAcademique(string $niveauType): string
    {
        return in_array($niveauType, self::LMD_TYPES) ? 'LMD' : 'BTS';
    }

    /**
     * Synchroniser systeme_academique sur les classes depuis leur niveau d'etudes.
     *
     * @param int|null $classeId  Si fourni, sync uniquement cette classe
     * @return array ['updated' => int, 'total' => int, 'details' => [['id' => ..., 'name' => ..., 'old' => ..., 'new' => ...]]]
     */
    public function syncSystemeAcademique(?int $classeId = null): array
    {
        $query = ESBTPClasse::with('niveau');
        if ($classeId) {
            $query->where('id', $classeId);
        }

        $classes = $query->get();
        $updated = 0;
        $details = [];

        foreach ($classes as $classe) {
            if (!$classe->niveau) {
                continue;
            }

            $expected = self::determinerSystemeAcademique($classe->niveau->type ?? '');

            if ($classe->systeme_academique !== $expected) {
                $old = $classe->systeme_academique;
                // Bypass model event to avoid infinite loop
                $classe->timestamps = false;
                ESBTPClasse::withoutEvents(function () use ($classe, $expected) {
                    $classe->update(['systeme_academique' => $expected]);
                });
                $classe->timestamps = true;

                $details[] = [
                    'id' => $classe->id,
                    'name' => $classe->name,
                    'old' => $old,
                    'new' => $expected,
                    'niveau_type' => $classe->niveau->type,
                ];
                $updated++;
            }
        }

        return [
            'updated' => $updated,
            'total' => $classes->count(),
            'details' => $details,
        ];
    }

    /**
     * Get available places for a given class.
     * Calcul basé sur les inscriptions réellement validées.
     *
     * @param  int  $classId
     * @return int
     */
    public function getAvailablePlaces(int $classId): int
    {
        $class = ESBTPClasse::find($classId);

        if (!$class) {
            return 0;
        }

        // Utiliser la même logique que le modèle ESBTPClasse
        return $class->places_disponibles;
    }

    /**
     * Manage class places and automatic assignment.
     *
     * @param  mixed  $class
     * @param  mixed  $student
     * @return bool
     */
    public function manageClassPlaces($class, $student)
    {
        // TODO: Implement logic for managing class places.
        // This could include checking availability, and assigning a student to a class.
        // It should also handle cases where a class is full and suggest alternatives.
        return true;
    }

    /**
     * Assign a student to a class automatically.
     *
     * @param  mixed  $student
     * @return mixed
     */
    public function assignToClass($student)
    {
        // TODO: Implement logic to automatically assign a student to a class.
        // This could be based on their chosen field of study, level, etc.
        return null;
    }
} 