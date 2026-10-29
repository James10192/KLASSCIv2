<?php

namespace App\Domain\Support\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

/**
 * Un envoi au Master en attente : un signalement, ou un avis 👍 / 👎 sur une
 * reponse de l'assistant. Les deux partagent la reprise ; seul l'ecran
 * « Mes demandes » ne montre que les signalements.
 */
class SupportOutbox extends Model
{
    public const SIGNALEMENT = 'ticket';

    public const AVIS_ASSISTANT = 'assistant_feedback';

    protected $table = 'support_outbox';

    protected $fillable = ['user_id', 'kind', 'idempotency_key', 'payload', 'request_id', 'next_attempt_at'];

    protected $attributes = ['kind' => self::SIGNALEMENT];

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

    public function scopeSignalements(Builder $query): Builder
    {
        return $query->where('kind', self::SIGNALEMENT);
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

    /**
     * Reserve la ligne pour un envoi : pose `next_attempt_at` quelques minutes
     * plus loin, de facon atomique. Une ligne reservee n'est plus remplacee
     * par TransmettreRetour, et une seconde passe ne la reprend pas. Si
     * l'appel n'aboutit pas, reporter() ou differer() reposent la date.
     *
     * Rend faux si une autre passe l'a deja prise.
     */
    public function reserver(int $minutes = 5): bool
    {
        $jusqua = now()->addMinutes($minutes);
        $prise = static::whereKey($this->getKey())->aEnvoyer()->update(['next_attempt_at' => $jusqua]) === 1;
        if ($prise) {
            $this->next_attempt_at = $jusqua;
            $this->syncOriginalAttribute('next_attempt_at');
        }

        return $prise;
    }

    /** Recul progressif : 1, 2, 4… minutes, plafonne a une heure. */
    public function reporter(string $erreur): void
    {
        $this->attempts++;
        $this->last_error = mb_substr($erreur, 0, 1000);
        if ($this->attempts >= (int) config('support.boite_envoi.tentatives_max', 20)) {
            $this->abandoned_at = now();
            Log::error('KLASSCI Care : '.($this->kind === self::AVIS_ASSISTANT ? 'avis sur l\'assistant' : 'signalement').' abandonné après tous les essais', [
                'outbox_id' => $this->id,
                'nature' => $this->kind,
                'tentatives' => $this->attempts,
                'erreur' => $this->last_error,
            ]);
        } else {
            $this->next_attempt_at = now()->addMinutes(min(60, 2 ** ($this->attempts - 1)));
        }
        $this->save();
    }

    /**
     * Remis a plus tard sans compter d'essai : la ligne n'est pas en cause
     * (route ou portee pas encore ouverte au Master), elle ne doit pas
     * s'approcher de l'abandon pendant qu'on attend.
     */
    public function differer(string $raison, int $minutes = 60): void
    {
        $this->forceFill([
            'last_error' => mb_substr($raison, 0, 1000),
            'next_attempt_at' => now()->addMinutes($minutes),
        ])->save();
    }
}
