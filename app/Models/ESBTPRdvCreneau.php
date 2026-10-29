<?php

namespace App\Models;

use App\Enums\StatutReservationRdv;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ESBTPRdvCreneau extends Model
{
    protected $table = 'esbtp_rdv_creneaux';

    protected $fillable = [
        'annee_universitaire_id',
        'date',
        'heure_debut',
        'heure_fin',
        'capacite',
        'ouvert',
    ];

    protected $casts = [
        'date' => 'date',
        'capacite' => 'integer',
        'ouvert' => 'boolean',
    ];

    public function anneeUniversitaire(): BelongsTo
    {
        return $this->belongsTo(ESBTPAnneeUniversitaire::class, 'annee_universitaire_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(ESBTPRdvReservation::class, 'creneau_id');
    }

    public function reservationsActives(): HasMany
    {
        return $this->reservations()->whereIn('statut', StatutReservationRdv::valeursOccupantes());
    }

    public function placesPrises(): int
    {
        return $this->reservationsActives()->count();
    }

    public function estOccupe(): bool
    {
        return $this->reservationsActives()->exists();
    }

    public function heureDebutHi(): string
    {
        return substr((string) ($this->attributes['heure_debut'] ?? ''), 0, 5);
    }

    public function heureFinHi(): string
    {
        return substr((string) ($this->attributes['heure_fin'] ?? ''), 0, 5);
    }

    public function debut(): Carbon
    {
        return Carbon::parse($this->date->toDateString().' '.$this->heureDebutHi().':00');
    }

    public function aCommence(): bool
    {
        return Carbon::now()->gte($this->debut());
    }

    public function estTermine(): bool
    {
        return Carbon::now()->gte(Carbon::parse($this->date->toDateString().' '.$this->heureFinHi().':00'));
    }
}
