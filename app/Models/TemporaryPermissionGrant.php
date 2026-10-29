<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une permission individuelle temporaire ou definitive, toujours revocable.
 *
 * Le caractere definitif est explicite : une ancienne date de fin NULL ne
 * doit jamais ouvrir un droit qui etait jusqu'ici inactif.
 */
class TemporaryPermissionGrant extends Model
{
    protected $fillable = [
        'user_id',
        'permission',
        'starts_at',
        'expires_at',
        'is_permanent',
        'motif',
        'granted_by',
        'revoked_at',
        'revoked_by',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_permanent' => 'boolean',
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
            ->where(function (Builder $period) use ($maintenant) {
                $period->where(function (Builder $permanent) {
                    $permanent->where('is_permanent', true)->whereNull('expires_at');
                })->orWhere(function (Builder $temporaire) use ($maintenant) {
                    $temporaire->where('is_permanent', false)->where('expires_at', '>', $maintenant);
                });
            });
    }

    public function estActive(): bool
    {
        $maintenant = now();

        return $this->revoked_at === null
            && $this->starts_at !== null && $this->starts_at->lte($maintenant)
            && ($this->is_permanent
                ? $this->expires_at === null
                : ($this->expires_at !== null && $this->expires_at->gt($maintenant)));
    }

    /** `active`, `definitif`, `a_venir`, `expiree` ou `retiree`. */
    public function statut(): string
    {
        if ($this->revoked_at !== null) {
            return 'retiree';
        }
        if ($this->starts_at !== null && $this->starts_at->isFuture()) {
            return 'a_venir';
        }

        if (! $this->estActive()) {
            return 'expiree';
        }

        return $this->is_permanent ? 'definitif' : 'active';
    }
}
