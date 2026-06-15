<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use OwenIt\Auditing\Contracts\Auditable;

class ESBTPTeacher extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable;

    /**
     * Colonnes auditées (whitelist — données RH sensibles).
     *
     * @var array
     */
    protected $auditInclude = [
        'user_id',
        'matricule',
        'employee_id',
        'title',
        'specialization',
        'status',
        'regime',
        'taux_horaire',
        'date_debut_activite',
        'diplome_principal',
        'universite_diplome',
        'annee_diplome',
        'teaching_hours_due',
        'is_active',
        'grade',
    ];

    /**
     * Événements à auditer (pas de SoftDeletes sur ce modèle).
     *
     * @var array
     */
    protected $auditEvents = [
        'created',
        'updated',
        'deleted',
    ];

    protected $table = 'esbtp_teachers';

    protected $fillable = [
        'user_id',
        'matricule',
        'title',
        'specialization',
        'status',
        'regime',
        'taux_horaire',
        'date_debut_activite',
        'diplome_principal',
        'universite_diplome',
        'annee_diplome',
        'teaching_hours_due',
        'phone',
        'email',
        'address',
        'city',
        'country',
        'postal_code',
        'bio',
        'research_interests',
        'website',
        'created_by',
        'updated_by',
        'is_active',
        'grade',
        'office_location',
        'employee_id'
    ];

    protected $casts = [
        'research_interests' => 'array',
        'is_active' => 'boolean',
        'taux_horaire' => 'decimal:2',
        'date_debut_activite' => 'date',
        'annee_diplome' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($teacher) {
            if (empty($teacher->employee_id)) {
                $teacher->employee_id = self::generateEmployeeId();
            }
        });
    }

    protected static function generateEmployeeId()
    {
        $year = Carbon::now()->format('Y');
        $lastTeacher = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastTeacher ? (int)substr($lastTeacher->employee_id, -4) + 1 : 1;
        return sprintf('EMP-%s-%04d', $year, $sequence);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function seancesCours()
    {
        return $this->hasMany(ESBTPSeanceCours::class, 'teacher_id');
    }

    public function attendances()
    {
        return $this->hasMany(ESBTPTeacherAttendance::class, 'teacher_id', 'user_id');
    }

    // Accesseur pour obtenir le nom complet
    public function getFullNameAttribute()
    {
        return $this->user ? $this->user->firstname . ' ' . $this->user->lastname : 'N/A';
    }

    // Accesseur pour obtenir le nom (utilise name en fallback)
    public function getNameAttribute()
    {
        if (!$this->user) {
            return 'N/A';
        }
        
        // Si firstname et lastname existent, les utiliser
        if ($this->user->firstname && $this->user->lastname) {
            return $this->user->firstname . ' ' . $this->user->lastname;
        }
        
        // Sinon utiliser le champ name
        return $this->user->name ?: 'N/A';
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    
    public function availabilities()
    {
        return $this->hasMany(ESBTPTeacherAvailability::class, 'teacher_id');
    }

    /**
     * Taux horaires par type de séance (CM/TD/TP…) — voir ESBTPEnseignantTauxSeance.
     */
    public function tauxSeances()
    {
        return $this->hasMany(ESBTPEnseignantTauxSeance::class, 'teacher_id');
    }

    /**
     * Taux horaire applicable pour un type de séance donné.
     *
     * Cascade : taux spécifique du type → taux par défaut (taux_horaire) → 0.
     * Accepte un App\Enums\TypeSeance ou sa valeur string.
     *
     * @param  \App\Enums\TypeSeance|string|null  $type
     */
    public function tauxPour($type): float
    {
        $value = $type instanceof \App\Enums\TypeSeance ? $type->value : (string) $type;

        $specifique = $this->tauxSeances
            ->firstWhere(fn ($t) => ($t->type_seance instanceof \App\Enums\TypeSeance
                ? $t->type_seance->value : $t->type_seance) === $value);

        if ($specifique && $specifique->taux_horaire !== null) {
            return (float) $specifique->taux_horaire;
        }

        return (float) ($this->taux_horaire ?? 0);
    }

    /**
     * Map [type_seance => taux] pour les types facturables (CM/TD/TP) — UI fiche.
     *
     * @return array<string, float|null>
     */
    public function tauxParTypeMap(): array
    {
        $map = [];
        foreach ($this->tauxSeances as $t) {
            $key = $t->type_seance instanceof \App\Enums\TypeSeance
                ? $t->type_seance->value : $t->type_seance;
            $map[$key] = $t->taux_horaire !== null ? (float) $t->taux_horaire : null;
        }
        return $map;
    }
}
