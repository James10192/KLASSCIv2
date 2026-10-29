<?php

namespace App\Models;

use App\Enums\CashSessionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ESBTPCashSession extends Model
{
    protected $table = 'esbtp_cash_sessions';

    protected $fillable = [
        'cashier_user_id',
        'business_date',
        'status',
        'opened_at',
        'closed_at',
        'counted_amount',
        'expected_amount',
        'variance',
        'regularized_at',
        'notes',
    ];

    protected $casts = [
        'business_date' => 'date',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'regularized_at' => 'datetime',
        'counted_amount' => 'decimal:2',
        'expected_amount' => 'decimal:2',
        'variance' => 'decimal:2',
        'status' => CashSessionStatus::class,
    ];

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_user_id');
    }

    public function isLocked(): bool
    {
        return $this->status->isLocked();
    }
}
