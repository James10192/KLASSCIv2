<?php

namespace App\Listeners;

use App\Events\TeacherAttendanceValidated;
use App\Models\ESBTPPlanificationAcademique;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Listener qui met à jour les heures de planification 
 * quand un émargement d'enseignant est validé
 */
class UpdatePlanificationHours
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  TeacherAttendanceValidated  $event
     * @return void
     */
    public function handle(TeacherAttendanceValidated $event)
    {
        try {
            $attendance = $event->teacherAttendance;
            $seance = $event->seanceCours;

            // Si on n'a pas de séance liée, on ne peut pas mettre à jour la planification
            if (!$seance) {
                Log::warning('Émargement validé sans séance associée', [
                    'attendance_id' => $attendance->id,
                    'teacher_id' => $attendance->teacher_id
                ]);
                return;
            }

            if (!$seance->isCourse()) {
                Log::info('Émargement ignoré pour une séance non cours', [
                    'seance_id' => $seance->id,
                    'type' => $seance->type,
                ]);
                return;
            }

            // Récupérer la planification académique correspondante
            $planification = ESBTPPlanificationAcademique::where('matiere_id', $seance->matiere_id)
                ->where('filiere_id', $seance->classe->filiere_id)
                ->where('niveau_etude_id', $seance->classe->niveau_etude_id)
                ->where('annee_universitaire_id', $seance->annee_universitaire_id)
                ->where('enseignant_principal_id', $attendance->teacher_id)
                ->active()
                ->first();

            if (!$planification) {
                Log::warning('Aucune planification trouvée pour cette séance', [
                    'seance_id' => $seance->id,
                    'matiere_id' => $seance->matiere_id,
                    'teacher_id' => $attendance->teacher_id,
                    'classe_id' => $seance->classe_id
                ]);
                return;
            }

            // Calculer la durée de la séance effectuée
            $heuresEffectuees = 0;
            if ($seance->heure_debut && $seance->heure_fin) {
                $debut = Carbon::parse($seance->heure_debut);
                $fin = Carbon::parse($seance->heure_fin);
                $heuresEffectuees = $fin->diffInMinutes($debut) / 60; // Convertir en heures
            }

            // Mettre à jour le champ heures_effectuees dans la planification
            if ($planification->heures_effectuees === null) {
                $planification->heures_effectuees = 0;
            }
            
            $planification->heures_effectuees += $heuresEffectuees;
            $planification->derniere_mise_a_jour_heures = now();
            $planification->save();

            // Log de succès
            Log::info('Heures de planification mises à jour après émargement', [
                'planification_id' => $planification->id,
                'seance_id' => $seance->id,
                'attendance_id' => $attendance->id,
                'heures_ajoutees' => $heuresEffectuees,
                'total_heures_effectuees' => $planification->heures_effectuees,
                'volume_horaire_total' => $planification->volume_horaire_total
            ]);

            // Optionnel : créer une notification si les heures sont dépassées
            if ($planification->heures_effectuees > $planification->volume_horaire_total) {
                Log::warning('Dépassement du volume horaire planifié', [
                    'planification_id' => $planification->id,
                    'heures_effectuees' => $planification->heures_effectuees,
                    'volume_prevu' => $planification->volume_horaire_total,
                    'depassement' => $planification->heures_effectuees - $planification->volume_horaire_total
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour des heures de planification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'attendance_id' => $event->teacherAttendance->id ?? null
            ]);
        }
    }
}
