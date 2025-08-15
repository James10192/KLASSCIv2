<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ESBTPSessionWorkflow extends Model
{
    use HasFactory;

    protected $table = 'esbtp_session_workflow_status';

    protected $fillable = [
        'seance_cours_id',
        'teacher_id',
        'attendance_signed',
        'call_start_done',
        'call_end_done',
        'report_submitted',
        'current_step',
        'attendance_signed_at',
        'call_start_done_at',
        'call_end_done_at',
        'report_submitted_at'
    ];

    protected $casts = [
        'attendance_signed' => 'boolean',
        'call_start_done' => 'boolean',
        'call_end_done' => 'boolean',
        'report_submitted' => 'boolean',
        'attendance_signed_at' => 'datetime',
        'call_start_done_at' => 'datetime',
        'call_end_done_at' => 'datetime',
        'report_submitted_at' => 'datetime'
    ];

    /**
     * Relation avec la séance de cours
     */
    public function seanceCours(): BelongsTo
    {
        return $this->belongsTo(ESBTPSeanceCours::class, 'seance_cours_id');
    }

    /**
     * Relation avec l'enseignant
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Marque l'émargement comme fait
     */
    public function markAttendanceSigned(): void
    {
        $this->attendance_signed = true;
        $this->attendance_signed_at = now();
        $this->current_step = 'call_start';
        $this->save();
    }

    /**
     * Marque l'appel de début comme fait
     */
    public function markCallStartDone(): void
    {
        $this->call_start_done = true;
        $this->call_start_done_at = now();
        $this->current_step = 'call_end';
        $this->save();
    }

    /**
     * Marque l'appel de fin comme fait
     */
    public function markCallEndDone(): void
    {
        $this->call_end_done = true;
        $this->call_end_done_at = now();
        $this->current_step = 'report';
        $this->save();
    }

    /**
     * Marque le rapport comme soumis
     */
    public function markReportSubmitted(): void
    {
        $this->report_submitted = true;
        $this->report_submitted_at = now();
        $this->current_step = 'completed';
        $this->save();
    }

    /**
     * Vérifie si une étape peut être exécutée
     */
    public function canExecuteStep(string $step): bool
    {
        switch ($step) {
            case 'attendance':
                return true;
            case 'call_start':
                return (bool) $this->attendance_signed;
            case 'call_end':
                return (bool) $this->attendance_signed && (bool) $this->call_start_done;
            case 'report':
                return (bool) $this->attendance_signed && (bool) $this->call_start_done && (bool) $this->call_end_done;
            default:
                return false;
        }
    }

    /**
     * Obtient la prochaine étape à exécuter
     */
    public function getNextStep(): ?string
    {
        if (!(bool) $this->attendance_signed) return 'attendance';
        if (!(bool) $this->call_start_done) return 'call_start';
        if (!(bool) $this->call_end_done) return 'call_end';
        if (!(bool) $this->report_submitted) return 'report';
        return null; // Workflow terminé
    }

    /**
     * Vérifie si le workflow est terminé
     */
    public function isCompleted(): bool
    {
        return $this->current_step === 'completed';
    }

    /**
     * Obtient le pourcentage de progression
     */
    public function getProgressPercentage(): int
    {
        $completed = 0;
        if ((bool) $this->attendance_signed) $completed++;
        if ((bool) $this->call_start_done) $completed++;
        if ((bool) $this->call_end_done) $completed++;
        if ((bool) $this->report_submitted) $completed++;

        return (int) round(($completed / 4) * 100);
    }

    /**
     * Obtient le label de l'étape actuelle
     */
    public function getCurrentStepLabel(): string
    {
        $labels = [
            'attendance' => 'Émargement',
            'call_start' => 'Appel de début',
            'call_end' => 'Appel de fin',
            'report' => 'Rapport de cours',
            'completed' => 'Terminé'
        ];

        return $labels[$this->current_step] ?? 'Inconnu';
    }

    /**
     * Crée ou met à jour un workflow pour une séance
     */
    public static function getOrCreateForSession(int $seanceId, int $teacherId): self
    {
        return self::firstOrCreate(
            [
                'seance_cours_id' => $seanceId,
                'teacher_id' => $teacherId
            ],
            [
                'current_step' => 'attendance'
            ]
        );
    }
}
