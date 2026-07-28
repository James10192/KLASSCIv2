<?php

namespace App\Models;

use App\Models\Traits\HasAuditTrail;
use App\Services\AppreciationScaleService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ESBTPLMDBulletin extends Model
{
    use HasFactory, SoftDeletes, HasAuditTrail;

    protected $table = 'esbtp_lmd_bulletins';

    protected $fillable = [
        'etudiant_id', 'classe_id', 'parcours_id', 'annee_universitaire_id',
        'semestre', 'niveau', 'domaine_label', 'mention_label', 'parcours_label',
        'moyenne_generale', 'credits_capitalises', 'credits_totaux',
        'rang', 'effectif', 'decision_deliberation', 'appreciation',
        'absences_justifiees', 'absences_non_justifiees', 'is_published',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'semestre' => 'integer',
        'moyenne_generale' => 'decimal:2',
        'credits_capitalises' => 'integer',
        'credits_totaux' => 'integer',
        'rang' => 'integer',
        'effectif' => 'integer',
        'absences_justifiees' => 'integer',
        'absences_non_justifiees' => 'integer',
        'is_published' => 'boolean',
    ];

    // --- Relations ---

    public function etudiant()
    {
        return $this->belongsTo(ESBTPEtudiant::class, 'etudiant_id');
    }

    public function classe()
    {
        return $this->belongsTo(ESBTPClasse::class, 'classe_id');
    }

    public function parcours()
    {
        return $this->belongsTo(ESBTPLMDParcours::class, 'parcours_id');
    }

    public function anneeUniversitaire()
    {
        return $this->belongsTo(ESBTPAnneeUniversitaire::class, 'annee_universitaire_id');
    }

    public function resultatsUEs()
    {
        return $this->hasMany(ESBTPLMDResultatUE::class, 'bulletin_id')
                     ->orderBy('id');
    }

    public function resultatsECUEs()
    {
        return $this->hasMany(ESBTPLMDResultatECUE::class, 'bulletin_id');
    }

    public function deliberation()
    {
        return $this->hasOne(ESBTPLMDDeliberation::class, 'bulletin_id');
    }

    public function scopeForJury(Builder $query, ESBTPLMDJury $jury): Builder
    {
        return $query
            ->where('annee_universitaire_id', $jury->annee_universitaire_id)
            ->when($jury->parcours_id, fn (Builder $builder, int $parcoursId) => $builder->where('parcours_id', $parcoursId))
            ->when($jury->classe_id, fn (Builder $builder, int $classeId) => $builder->where('classe_id', $classeId))
            ->when($jury->semestre, fn (Builder $builder, int $semestre) => $builder->where('semestre', $semestre));
    }

    // --- Accessors ---

    public function getMentionGeneraleAttribute(): ?string
    {
        if ($this->moyenne_generale === null) return null;

        return app(AppreciationScaleService::class)->labelFor((float) $this->moyenne_generale, 'lmd');
    }

    public function getMentionAttribute(): ?string
    {
        return $this->mention_generale;
    }

    public function getTauxCapitalisationAttribute(): float
    {
        if ($this->credits_totaux == 0) return 0;
        return round(($this->credits_capitalises / $this->credits_totaux) * 100, 1);
    }
}
