<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une permission ouverte a une personne jusqu'a une date donnee.
 *
 * L'acces se lit dans le temps, pas dans une colonne d'etat : une ligne est
 * active tant que `starts_at <= maintenant < expires_at` et qu'elle n'a pas ete
 * retiree. Personne n'a donc a « fermer » un acces arrive a echeance.
 */
class TemporaryPermissionGrant extends Model
{
    protected $fillable = [
        'user_id',
        'permission',
        'starts_at',
        'expires_at',
        'motif',
        'granted_by',
        'revoked_at',
        'revoked_by',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function scopeActives(Builder $query): Builder
    {
        $maintenant = now();

        return $query->whereNull('revoked_at')
            ->where('starts_at', '<=', $maintenant)
            ->where('expires_at', '>', $maintenant);
    }

    public function estActive(): bool
    {
        $maintenant = now();

        return $this->revoked_at === null
            && $this->starts_at !== null && $this->starts_at->lte($maintenant)
            && $this->expires_at !== null && $this->expires_at->gt($maintenant);
    }

    /** `active`, `a_venir`, `expiree` ou `retiree`. */
    public function statut(): string
    {
        if ($this->revoked_at !== null) {
            return 'retiree';
        }
        if ($this->starts_at !== null && $this->starts_at->isFuture()) {
            return 'a_venir';
        }

        return $this->estActive() ? 'active' : 'expiree';
    }
}
