<?php

namespace App\Domain\Comptabilite\Reconciliation\Models;

use App\Enums\ReconciliationSessionStatus;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPPaiement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class ReconciliationSession extends Model implements Auditable
{
    use HasFactory, SoftDeletes, AuditableTrait;

    protected $table = 'reconciliation_sessions';

    protected $fillable = [
        'code',
        'frequency',
        'annee_universitaire_id',
        'period_start',
        'period_end',
        'status',
        'opened_by',
        'opened_at',
        'reviewed_by',
        'reviewed_at',
        'approved_by',
        'approved_at',
        'closed_by',
        'closed_at',
        'reopened_by',
        'reopened_at',
        'reopen_reason',
        'pv_pdf_path',
        'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'opened_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'closed_at' => 'datetime',
        'reopened_at' => 'datetime',
        'status' => ReconciliationSessionStatus::class,
    ];

    protected $auditInclude = [
        'status',
        'frequency',
        'period_start',
        'period_end',
        'reviewed_by',
        'approved_by',
        'closed_by',
        'reopened_by',
        'reopen_reason',
        'notes',
    ];

    protected $auditEvents = ['created', 'updated', 'deleted', 'restored'];

    public function annee(): BelongsTo
    {
        return $this->belongsTo(ESBTPAnneeUniversitaire::class, 'annee_universitaire_id');
    }

    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function cashCounts(): HasMany
    {
        return $this->hasMany(CashCount::class);
    }

    public function discrepancies(): HasMany
    {
        return $this->hasMany(ReconciliationDiscrepancy::class);
    }

    public function paymentLogs(): HasMany
    {
        return $this->hasMany(PaymentReconciliationLog::class);
    }

    public function lockedPaiements(): HasMany
    {
        return $this->hasMany(ESBTPPaiement::class, 'last_reconciliation_session_id');
    }

    /**
     * Génère un code de session séquentiel thread-safe.
     * Format : REC-{ANNEE_UNIV}-{SEQ4}.
     */
    public static function reserveCode(int $anneeUniversitaireId): string
    {
        return \DB::transaction(function () use ($anneeUniversitaireId) {
            $lastNum = static::where('annee_universitaire_id', $anneeUniversitaireId)
                ->lockForUpdate()
                ->max(\DB::raw("CAST(SUBSTRING_INDEX(code, '-', -1) AS UNSIGNED)")) ?? 0;
            $next = str_pad((string) ($lastNum + 1), 4, '0', STR_PAD_LEFT);
            return "REC-{$anneeUniversitaireId}-{$next}";
        });
    }

    public function isModifiable(): bool
    {
        return $this->status->isModifiable();
    }

    public function isLocked(): bool
    {
        return in_array($this->status, [
            ReconciliationSessionStatus::APPROVED,
            ReconciliationSessionStatus::CLOSED,
        ], true);
    }

    public function totalEcart(): float
    {
        return (float) $this->cashCounts->sum(fn (CashCount $c) => $c->ecart);
    }
}
