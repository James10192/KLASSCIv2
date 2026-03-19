<?php

namespace App\Models;

use App\Models\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ESBTPLMDResultatECUE extends Model
{
    use HasFactory, SoftDeletes, HasAuditTrail;

    protected $table = 'esbtp_lmd_resultats_ecues';

    protected $fillable = [
        'bulletin_id', 'resultat_ue_id', 'matiere_id', 'etudiant_id',
        'moyenne', 'credit', 'rang', 'enseignant_id',
        'stat_min', 'stat_moy', 'stat_max',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'moyenne' => 'decimal:2',
        'credit' => 'integer',
        'rang' => 'integer',
        'stat_min' => 'decimal:2',
        'stat_moy' => 'decimal:2',
        'stat_max' => 'decimal:2',
    ];

    public function bulletin()
    {
        return $this->belongsTo(ESBTPLMDBulletin::class, 'bulletin_id');
    }

    public function resultatUE()
    {
        return $this->belongsTo(ESBTPLMDResultatUE::class, 'resultat_ue_id');
    }

    public function matiere()
    {
        return $this->belongsTo(ESBTPMatiere::class, 'matiere_id');
    }

    public function etudiant()
    {
        return $this->belongsTo(ESBTPEtudiant::class, 'etudiant_id');
    }

    public function enseignant()
    {
        return $this->belongsTo(User::class, 'enseignant_id');
    }

}
