<?php

namespace App\Domain\Comptabilite\Reconciliation\Models;

use App\Enums\ModePaiement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Comptage caisse physique pour une (session, mode_paiement).
 * Une ligne par mode constaté lors de la session.
 */
class CashCount extends Model
{
    use HasFactory;

    protected $table = 'cash_counts';

    protected $fillable = [
        'reconciliation_session_id',
        'mode_paiement',
        'montant_compte',
        'montant_systeme',
        'counted_by',
        'counted_at',
        'notes',
    ];

    protected $casts = [
        'montant_compte' => 'decimal:2',
        'montant_systeme' => 'decimal:2',
        'counted_at' => 'datetime',
    ];

    protected $appends = ['ecart'];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ReconciliationSession::class, 'reconciliation_session_id');
    }

    public function counter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counted_by');
    }

    public function getEcartAttribute(): float
    {
        return (float) $this->montant_compte - (float) $this->montant_systeme;
    }

    public function getModeEnumAttribute(): ?ModePaiement
    {
        return ModePaiement::fromLegacy($this->mode_paiement);
    }

    public function modeLabel(): string
    {
        return $this->mode_enum?->label() ?? $this->mode_paiement;
    }
}
