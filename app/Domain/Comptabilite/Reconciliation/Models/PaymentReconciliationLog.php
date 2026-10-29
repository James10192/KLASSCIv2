<?php

namespace App\Domain\Comptabilite\Reconciliation\Models;

use App\Models\ESBTPPaiement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Log immuable des mutations paiement faites dans le contexte d'une session.
 * Pas de timestamps Laravel (append-only) — performed_at unique.
 *
 * Complémentaire à OwenIt\Auditing (qui loggue tous updates du model). Cette
 * table loggue les mutations DANS LE CONTEXTE de la réconciliation avec un
 * motif obligatoire et le lien session.
 */
class PaymentReconciliationLog extends Model
{
    use HasFactory;

    protected $table = 'payment_reconciliation_logs';

    public $timestamps = false;

    protected $fillable = [
        'reconciliation_session_id',
        'paiement_id',
        'action_type',
        'snapshot_before',
        'snapshot_after',
        'delta',
        'motif',
        'performed_by',
        'performed_at',
    ];

    protected $casts = [
        'snapshot_before' => 'array',
        'snapshot_after' => 'array',
        'delta' => 'array',
        'performed_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ReconciliationSession::class, 'reconciliation_session_id');
    }

    public function paiement(): BelongsTo
    {
        return $this->belongsTo(ESBTPPaiement::class, 'paiement_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
