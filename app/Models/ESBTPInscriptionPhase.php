<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class ESBTPInscriptionPhase extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable;

    public const TYPE_TRONC_COMMUN = 'tronc_commun';
    public const TYPE_SPECIALISATION = 'specialisation';

    /**
     * Le parcours d'une inscription est memorise le temps d'une requete.
     * Toute phase creee, modifiee ou supprimee le rend caduc : on l'oublie ici
     * plutot que dans chaque appelant, ou l'oubli finirait par etre oublie.
     */
    protected static function booted(): void
    {
        $oublier = static function (self $phase): void {
            if ($phase->inscription_id) {
                app(\App\Domain\BtsTroncCommun\BtsPhaseResolver::class)->oublier((int) $phase->inscription_id);
            }
        };

        static::saved($oublier);
        static::deleted($oublier);
    }

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
        'correction_reason',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'date_activation' => 'datetime',
        'date_cloture' => 'datetime',
    ];

    /**
     * Colonnes auditées — traçabilité conformité UEMOA pour orientation TC→Spé.
     * Permet de répondre : qui a orienté quel étudiant, quand, vers quelle classe ?
     */
    protected $auditInclude = [
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
        'correction_reason',
    ];

    protected $auditEvents = [
        'created',
        'updated',
        'deleted',
        'restored',
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
