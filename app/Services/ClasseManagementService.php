<?php

namespace App\Services;

use App\Models\ESBTPClasse;

class ClasseManagementService
{
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

        // Compter les inscriptions avec workflow_step 'valide'
        $inscriptionsValidees = $class->inscriptions()
            ->where('workflow_step', 'valide')
            ->count();

        return max(0, $class->places_totales - $inscriptionsValidees);
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