<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPTeacherAttendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MarkUnattendedTeacherSessions extends Command
{
    protected $signature = 'attendance:mark-unattended-teacher-sessions';
    protected $description = 'Mark teacher sessions as not_signed if not signed in time';

    public function handle()
    {
        $now = Carbon::now();
        $windowLateMinutes = config('esbtp.attendance.allowed_late_minutes', 15);
        $sessions = ESBTPSeanceCours::where('date_seance', '<=', $now->toDateString())
            ->whereRaw('ADDTIME(heure_fin, SEC_TO_TIME(? * 60)) < ?', [$windowLateMinutes, $now->toTimeString()])
            ->get();

        $count = 0;
        foreach ($sessions as $session) {
            $teacherId = $session->teacher_id;
            if (!$teacherId) continue;
            $alreadyMarked = ESBTPTeacherAttendance::where('teacher_id', $teacherId)
                ->where('course_id', $session->id)
                ->exists();
            if (!$alreadyMarked) {
                ESBTPTeacherAttendance::create([
                    'teacher_id' => $teacherId,
                    'course_id' => $session->id,
                    'marked_at' => null,
                    'status' => 'not_signed',
                    'date' => $session->date_seance,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $count++;
            }
        }
        $this->info("Marked $count unattended teacher sessions as not_signed.");
    }
}
