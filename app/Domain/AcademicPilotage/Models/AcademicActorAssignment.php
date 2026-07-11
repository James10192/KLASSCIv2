<?php

namespace App\Domain\AcademicPilotage\Models;

use App\Domain\AcademicPilotage\Enums\AcademicResponsibility;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class AcademicActorAssignment extends Model implements AuditableContract
{
    use Auditable;

    protected $table = 'esbtp_academic_actor_assignments';

    protected $fillable = [
        'user_id',
        'classe_id',
        'annee_universitaire_id',
        'responsibility',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'responsibility' => AcademicResponsibility::class,
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    protected $auditInclude = [
        'user_id',
        'classe_id',
        'annee_universitaire_id',
        'responsibility',
        'is_active',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
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
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
