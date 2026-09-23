<?php

namespace App\Models;

use App\Contracts\PorteurDeRendezVous;
use App\Enums\StatutConvocationRdv;
use App\Enums\StatutReservationRdv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'convocation_statut',
        'convocation_action',
        'convocation_tentatives',
        'convocation_envoyee_at',
        'convocation_erreur',
        'convocation_message_id',
        'accueilli_at',
        'accueilli_par',
        'prevenue_par',
    ];

    protected $casts = [
        'statut' => StatutReservationRdv::class,
        'date_naissance' => 'date',
        'libere_at' => 'datetime',
        'convocation_statut' => StatutConvocationRdv::class,
        'convocation_tentatives' => 'integer',
        'convocation_envoyee_at' => 'datetime',
        'accueilli_at' => 'datetime',
    ];

    public function creneau(): BelongsTo
    {
        return $this->belongsTo(ESBTPRdvCreneau::class, 'creneau_id');
    }

    public function accueilliPar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accueilli_par');
    }

    public function prevenuePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prevenue_par');
    }

    public function reprogrammations(): HasMany
    {
        return $this->hasMany(ESBTPRdvReprogrammation::class, 'reservation_id');
    }

    public function candidature(): BelongsTo
    {
        return $this->belongsTo(ESBTPCandidature::class, 'candidature_id');
    }

    public function demande(): BelongsTo
    {
        return $this->belongsTo(ESBTPReinscriptionDemande::class, 'reinscription_demande_id');
    }

    public function porteur(): ?PorteurDeRendezVous
    {
        $this->loadMissing(['candidature', 'demande']);

        $porteur = $this->candidature ?? $this->demande;

        return $porteur instanceof PorteurDeRendezVous ? $porteur : null;
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
