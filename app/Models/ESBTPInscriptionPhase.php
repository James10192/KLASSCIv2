<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ESBTPInscriptionPhase extends Model
{
    use HasFactory;

    public const TYPE_TRONC_COMMUN = 'tronc_commun';
    public const TYPE_SPECIALISATION = 'specialisation';

    protected $table = 'esbtp_inscription_phases';

    protected $fillable = [
        'inscription_id',
        'type_phase',
        'classe_id',
        'filiere_id',
        'semestre_debut',
        'semestre_fin',
        'is_active',
        'orientation_target_id',
        'date_activation',
        'date_cloture',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'date_activation' => 'datetime',
        'date_cloture' => 'datetime',
    ];

    public function inscription()
    {
        return $this->belongsTo(ESBTPInscription::class, 'inscription_id');
    }

    public function classe()
    {
        return $this->belongsTo(ESBTPClasse::class, 'classe_id');
    }

    public function filiere()
    {
        return $this->belongsTo(ESBTPFiliere::class, 'filiere_id');
    }

    public function orientationTarget()
    {
        return $this->belongsTo(ESBTPClasseOrientationTarget::class, 'orientation_target_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
