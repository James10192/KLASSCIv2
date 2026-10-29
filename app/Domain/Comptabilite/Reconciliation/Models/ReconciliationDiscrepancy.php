<?php

namespace App\Domain\Comptabilite\Reconciliation\Models;

use App\Models\ESBTPPaiement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReconciliationDiscrepancy extends Model
{
    use HasFactory;

    protected $table = 'reconciliation_discrepancies';

    protected $fillable = [
        'reconciliation_session_id',
        'cash_count_id',
        'type',
        'montant_ecart',
        'paiement_concerne_id',
        'action',
        'resolution_type',
        'resolution_payment_id',
        'motif',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'montant_ecart' => 'decimal:2',
        'resolved_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ReconciliationSession::class, 'reconciliation_session_id');
    }

    public function cashCount(): BelongsTo
    {
        return $this->belongsTo(CashCount::class);
    }

    public function paiementConcerne(): BelongsTo
    {
        return $this->belongsTo(ESBTPPaiement::class, 'paiement_concerne_id');
    }

    public function resolutionPayment(): BelongsTo
    {
        return $this->belongsTo(ESBTPPaiement::class, 'resolution_payment_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function isResolved(): bool
    {
        return $this->action === 'resolu';
    }
}
