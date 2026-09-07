<?php

namespace App\Models;

use App\Enums\StatutReservationRdv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ESBTPRdvReservation extends Model
{
    protected $table = 'esbtp_rdv_reservations';

    protected $fillable = [
        'creneau_id',
        'candidature_id',
        'reinscription_demande_id',
        'statut',
        'nom',
        'prenoms',
        'telephone',
        'date_naissance',
        'email',
        'libere_at',
    ];

    protected $casts = [
        'statut' => StatutReservationRdv::class,
        'date_naissance' => 'date',
        'libere_at' => 'datetime',
    ];

    public function creneau(): BelongsTo
    {
        return $this->belongsTo(ESBTPRdvCreneau::class, 'creneau_id');
    }

    public function candidature(): BelongsTo
    {
        return $this->belongsTo(ESBTPCandidature::class, 'candidature_id');
    }

    public function demande(): BelongsTo
    {
        return $this->belongsTo(ESBTPReinscriptionDemande::class, 'reinscription_demande_id');
    }

    public function scopeOccupantes(Builder $query): Builder
    {
        return $query->whereIn('statut', StatutReservationRdv::valeursOccupantes());
    }

    public function nomComplet(): string
    {
        return trim($this->nom.' '.$this->prenoms);
    }
}
