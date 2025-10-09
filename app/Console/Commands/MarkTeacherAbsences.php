<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPTeacherAttendance;
use App\Models\ESBTPSessionWorkflow;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MarkTeacherAbsences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teacher:mark-absences {--test : Run in test mode without saving}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Marque automatiquement les enseignants absents pour les cours dont la fenêtre d\'émargement (45min) a expiré';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $isTest = $this->option('test');
        $now = Carbon::now();

        $this->info("🕒 Vérification des absences automatiques - " . $now->format('Y-m-d H:i:s'));

        if ($isTest) {
            $this->warn("⚠️  MODE TEST - Aucune modification ne sera enregistrée");
        }

        // Récupérer toutes les séances du jour
        $today = $now->toDateString();
        $seances = ESBTPSeanceCours::whereDate('date_seance', $today)
            ->whereHas('emploiTemps', function($query) {
                $query->where('is_active', true);
            })
            ->with(['teacher.user', 'matiere', 'classe'])
            ->get();

        $this->info("📚 {$seances->count()} séance(s) trouvée(s) pour aujourd'hui");

        $absentsMarked = 0;
        $alreadyMarked = 0;
        $notYetExpired = 0;

        foreach ($seances as $seance) {
            $courseStart = Carbon::parse($seance->heure_debut);
            $limite45min = $courseStart->copy()->addMinutes(45);

            // Vérifier si la fenêtre de 45min a expiré
            if ($now->lte($limite45min)) {
                $notYetExpired++;
                continue;
            }

            // Vérifier si l'enseignant a déjà émargé
            $hasAttendance = ESBTPTeacherAttendance::where('course_id', $seance->id)
                ->whereDate('date', $today)
                ->exists();

            if ($hasAttendance) {
                $alreadyMarked++;
                continue;
            }

            // === MARQUER ABSENT ===
            $teacherName = $seance->teacher->user->name ?? 'Enseignant inconnu';
            $matiereName = $seance->matiere->name ?? 'Matière inconnue';
            $classeName = $seance->classe->name ?? 'Classe inconnue';

            $this->warn("❌ ABSENT: {$teacherName} - {$matiereName} ({$classeName}) - Début: {$courseStart->format('H:i')} - Expiré à: {$limite45min->format('H:i')}");

            if (!$isTest) {
                try {
                    // Créer l'émargement absent
                    $teacherUserId = optional($seance->teacher->user)->id;
                    if (!$teacherUserId) {
                        $this->error("⚠️ Impossible de marquer la séance {$seance->id} : aucun utilisateur associé à l'enseignant.");
                        continue;
                    }
                    ESBTPTeacherAttendance::create([
                        'teacher_id' => $teacherUserId,
                        'course_id' => $seance->id,
                        'daily_code_id' => null, // Pas de code car automatique
                        'date' => $today,
                        'status' => 'absent',
                        'attempts' => 0,
                        'ip_address' => '0.0.0.0', // Système
                        'device_info' => json_encode(['auto_marked' => true, 'marked_at' => $now->toDateTimeString()]),
                        'validated_at' => $now,
                        'marked_at' => $now,
                    ]);

                    // Fermer le workflow
                    $workflow = ESBTPSessionWorkflow::getOrCreateForSession($seance->id, $teacherUserId);
                    $workflow->current_step = 'closed_absent';
                    $workflow->save();

                    Log::info("Enseignant marqué absent automatiquement", [
                        'teacher_user_id' => $teacherUserId,
                        'teacher_model_id' => $seance->teacher_id,
                        'course_id' => $seance->id,
                        'teacher_name' => $teacherName,
                        'course_start' => $courseStart->format('H:i'),
                        'marked_at' => $now->format('H:i')
                    ]);

                    $absentsMarked++;
                } catch (\Exception $e) {
                    $this->error("❗ Erreur lors du marquage d'absence pour la séance {$seance->id}: " . $e->getMessage());
                    Log::error("Erreur marquage absence automatique", [
                        'course_id' => $seance->id,
                        'error' => $e->getMessage()
                    ]);
                }
            } else {
                $absentsMarked++;
            }
        }

        // Résumé
        $this->newLine();
        $this->info("📊 RÉSUMÉ:");
        $this->info("   ✅ Enseignants marqués présents/retard: {$alreadyMarked}");
        $this->info("   ⏳ Cours pas encore expirés: {$notYetExpired}");
        $this->warn("   ❌ Enseignants marqués ABSENTS: {$absentsMarked}");

        if ($isTest && $absentsMarked > 0) {
            $this->newLine();
            $this->warn("⚠️  MODE TEST - Relancez sans --test pour enregistrer les absences");
        }

        return Command::SUCCESS;
    }
}
