<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ESBTPSessionReport extends Model
{
    use HasFactory;

    protected $table = 'esbtp_session_reports';

    protected $fillable = [
        'seance_cours_id',
        'teacher_id',
        'content_summary',
        'teaching_methods',
        'student_behavior',
        'difficulties_encountered',
        'next_session_preparation',
        'homework_assigned',
        'status',
        'submitted_at'
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
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
     * Vérifie si le résumé respecte la longueur minimale
     */
    public function hasValidContentLength(): bool
    {
        return strlen(trim($this->content_summary)) >= 30;
    }

    /**
     * Marque le rapport comme soumis
     */
    public function markAsSubmitted(): void
    {
        $this->status = 'submitted';
        $this->submitted_at = now();
        $this->save();
    }

    /**
     * Vérifie si le rapport est soumis
     */
    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    /**
     * Scope pour les rapports soumis
     */
    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    /**
     * Scope pour les brouillons
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Accesseur pour obtenir le nom du comportement des étudiants
     */
    public function getStudentBehaviorLabelAttribute(): string
    {
        $labels = [
            'excellent' => 'Excellent',
            'good' => 'Bon',
            'satisfactory' => 'Satisfaisant',
            'difficult' => 'Difficile'
        ];

        return $labels[$this->student_behavior] ?? 'Non défini';
    }

    /**
     * Vérifie si le rapport peut être modifié
     */
    public function canBeEdited(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Obtient la couleur du badge selon le comportement
     */
    public function getBehaviorBadgeColorAttribute(): string
    {
        $colors = [
            'excellent' => 'success',
            'good' => 'primary',
            'satisfactory' => 'warning',
            'difficult' => 'danger'
        ];

        return $colors[$this->student_behavior] ?? 'secondary';
    }
}
