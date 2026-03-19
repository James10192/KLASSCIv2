<?php

namespace App\Models;

use App\Models\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ESBTPLMDResultatUE extends Model
{
    use HasFactory, SoftDeletes, HasAuditTrail;

    protected $table = 'esbtp_lmd_resultats_ues';

    protected $fillable = [
        'bulletin_id', 'unite_enseignement_id', 'etudiant_id',
        'moyenne', 'statut', 'mention', 'credit',
        'stat_min', 'stat_moy', 'stat_max',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'moyenne' => 'decimal:2',
        'credit' => 'integer',
        'stat_min' => 'decimal:2',
        'stat_moy' => 'decimal:2',
        'stat_max' => 'decimal:2',
    ];

    // Constantes statut validation
    const STATUT_AQ  = 'AQ';   // Acquis (moyenne >= 10)
    const STATUT_NAQ = 'NAQ';  // Non Acquis
    const STATUT_APC = 'APC';  // Acquis Par Compensation (moyenne_generale >= 10)

    public function bulletin()
    {
        return $this->belongsTo(ESBTPLMDBulletin::class, 'bulletin_id');
    }

    public function uniteEnseignement()
    {
        return $this->belongsTo(ESBTPUniteEnseignement::class, 'unite_enseignement_id');
    }

    public function etudiant()
    {
        return $this->belongsTo(ESBTPEtudiant::class, 'etudiant_id');
    }

    public function resultatsECUEs()
    {
        return $this->hasMany(ESBTPLMDResultatECUE::class, 'resultat_ue_id')
                     ->orderBy('id');
    }

    /**
     * L'UE est-elle validee (AQ ou APC) ?
     */
    public function isValidee(): bool
    {
        return in_array($this->statut, [self::STATUT_AQ, self::STATUT_APC]);
    }
}
