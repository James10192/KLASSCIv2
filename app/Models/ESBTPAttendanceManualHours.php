<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ESBTPAttendanceManualHours extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'esbtp_attendance_manual_hours';

    protected $fillable = [
        'etudiant_id',
        'matiere_id',
        'classe_id',
        'annee_universitaire_id',
        'periode',
        'heures_presence',
        'heures_absence_justifiees',
        'heures_absence_non_justifiees',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'heures_presence' => 'decimal:2',
        'heures_absence_justifiees' => 'decimal:2',
        'heures_absence_non_justifiees' => 'decimal:2',
    ];

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(ESBTPEtudiant::class, 'etudiant_id');
    }

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(ESBTPMatiere::class, 'matiere_id');
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(ESBTPClasse::class, 'classe_id');
    }

    public function anneeUniversitaire(): BelongsTo
    {
        return $this->belongsTo(ESBTPAnneeUniversitaire::class, 'annee_universitaire_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    public function scopeForPeriod($query, int $anneeId, string $periode)
    {
        return $query->where('annee_universitaire_id', $anneeId)
            ->where('periode', $periode);
    }

    public function scopeForEtudiant($query, int $etudiantId)
    {
        return $query->where('etudiant_id', $etudiantId);
    }

    public function scopeForClasse($query, int $classeId)
    {
        return $query->where('classe_id', $classeId);
    }

    public function getTotalAbsencesAttribute(): float
    {
        return (float) $this->heures_absence_justifiees + (float) $this->heures_absence_non_justifiees;
    }

    public function getTotalHeuresAttribute(): float
    {
        return (float) $this->heures_presence + $this->total_absences;
    }
}
