<?php

namespace App\Events;

use App\Models\ESBTPTeacherAttendance;
use App\Models\ESBTPSeanceCours;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event déclenché quand un émargement d'enseignant est validé
 * Permet de mettre à jour automatiquement les heures de planification
 */
class TeacherAttendanceValidated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $teacherAttendance;
    public $seanceCours;

    /**
     * Create a new event instance.
     *
     * @param ESBTPTeacherAttendance $teacherAttendance
     * @param ESBTPSeanceCours|null $seanceCours
     */
    public function __construct(ESBTPTeacherAttendance $teacherAttendance, ESBTPSeanceCours $seanceCours = null)
    {
        $this->teacherAttendance = $teacherAttendance;
        $this->seanceCours = $seanceCours;
    }
}