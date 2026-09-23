<?php

namespace App\Domain\Support\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

/** Un signalement en attente d'envoi au Master. */
class SupportOutbox extends Model
{
    protected $table = 'support_outbox';

    protected $fillable = ['user_id', 'idempotency_key', 'payload', 'request_id', 'next_attempt_at'];

    protected $casts = [
        'payload' => 'array',
        'next_attempt_at' => 'datetime',
        'sent_at' => 'datetime',
        'abandoned_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeEnAttente(Builder $query): Builder
    {
        return $query->whereNull('sent_at')->whereNull('abandoned_at');
    }

    public function scopeAEnvoyer(Builder $query): Builder
    {
        return $query->enAttente()->where(fn ($q) => $q->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now()));
    }

    /**
     * Ce que la personne doit voir de sa boite d'envoi : ce qui attend, ce qui
     * vient de partir (le Master peut etre injoignable au moment ou elle
     * regarde), et ce qui n'est jamais parti — pour qu'elle le renvoie autrement.
     */
    public function scopeAMontrer(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->enAttente()
            ->orWhere('sent_at', '>=', now()->subDay())
            ->orWhere('abandoned_at', '>=', now()->subDays(30)));
    }

    public function extrait(): string
    {
        return mb_strimwidth((string) ($this->payload['report']['description'] ?? ''), 0, 90, '…');
    }

    /** Recul progressif : 1, 2, 4… minutes, plafonne a une heure. */
    public function reporter(string $erreur): void
    {
        $this->attempts++;
        $this->last_error = mb_substr($erreur, 0, 1000);
        if ($this->attempts >= (int) config('support.boite_envoi.tentatives_max', 20)) {
            $this->abandoned_at = now();
            Log::error('KLASSCI Care : signalement abandonné après tous les essais', [
                'outbox_id' => $this->id,
                'tentatives' => $this->attempts,
                'erreur' => $this->last_error,
            ]);
        } else {
            $this->next_attempt_at = now()->addMinutes(min(60, 2 ** ($this->attempts - 1)));
        }
        $this->save();
    }
}
