<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPTeacherAttendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Marque « non émargée » une séance terminée que l'enseignant n'a pas signée.
 *
 * Deux identifiants à ne pas confondre : sur la séance, teacher_id est le
 * profil enseignant (esbtp_teachers.id) ; sur l'émargement, teacher_id est le
 * compte (users.id, clé étrangère). Recopier l'un dans l'autre faisait planter
 * la tâche quand ce numéro n'existait pas comme compte (toutes les séances
 * suivantes restaient alors non marquées), et rattachait l'émargement à un
 * AUTRE compte quand il existait. Comme MarkTeacherAbsences, on passe par le
 * profil pour trouver le compte.
 */
class MarkUnattendedTeacherSessions extends Command
{
    protected $signature = 'attendance:mark-unattended-teacher-sessions';
    protected $description = 'Mark teacher sessions as not_signed if not signed in time';

    public function handle()
    {
        $now = Carbon::now();
        $windowLateMinutes = config('esbtp.attendance.allowed_late_minutes', 15);
        $sessions = ESBTPSeanceCours::with('teacher.user:id')
            ->whereNotNull('teacher_id')
            ->where('date_seance', '<=', $now->toDateString())
            ->whereRaw('ADDTIME(heure_fin, SEC_TO_TIME(? * 60)) < ?', [$windowLateMinutes, $now->toTimeString()])
            ->get();

        $count = 0;
        $sansCompte = [];
        foreach ($sessions as $session) {
            // Profil dont le compte n'existe plus : la clé étrangère refuserait la ligne à chaque passage.
            $userId = $session->teacher?->user?->id;
            if (! $userId) {
                $sansCompte[] = $session->id;
                continue;
            }
            $alreadyMarked = ESBTPTeacherAttendance::where('teacher_id', $userId)
                ->where('course_id', $session->id)
                ->exists();
            if ($alreadyMarked) {
                continue;
            }
            try {
                ESBTPTeacherAttendance::create([
                    'teacher_id' => $userId,
                    'course_id' => $session->id,
                    'marked_at' => null,
                    'status' => 'not_signed',
                    'date' => $session->date_seance,
                ]);
                $count++;
            } catch (\Illuminate\Database\QueryException $e) {
                // Une séance refusée (compte supprimé entre-temps, doublon) ne bloque plus les suivantes.
                Log::warning('attendance.non_emargee_refusee', ['seance' => $session->id, 'erreur' => $e->getMessage()]);
            }
        }

        // Un profil enseignant sans compte : rien à quoi rattacher l'émargement, à corriger dans l'emploi du temps.
        // Dit une fois par jour, pas toutes les dix minutes.
        if ($sansCompte !== [] && Cache::add('attendance.sans_compte.'.$now->toDateString(), true, $now->copy()->endOfDay())) {
            Log::warning('attendance.seances_sans_compte_enseignant', ['seances' => array_slice($sansCompte, 0, 50), 'total' => count($sansCompte)]);
        }
        $this->info("Marked $count unattended teacher sessions as not_signed.");
    }
}
