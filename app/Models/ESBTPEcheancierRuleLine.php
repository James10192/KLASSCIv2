<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ESBTPEcheancierRuleLine extends Model
{
    use HasFactory;

    protected $table = 'esbtp_echeancier_rule_lines';

    public const AMOUNT_MODE_PERCENT = 'percent';
    public const AMOUNT_MODE_FIXED = 'fixed';

    public const DUE_MODE_DAYS_AFTER_INSCRIPTION = 'days_after_inscription';
    public const DUE_MODE_FIXED_MM_DD = 'fixed_mm_dd';

    protected $fillable = [
        'rule_id',
        'label',
        'sort_order',
        'amount_mode',
        'amount_value',
        'due_mode',
        'due_value',
        'grace_days',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'amount_value' => 'decimal:2',
        'grace_days' => 'integer',
        'is_active' => 'boolean',
    ];

    public function rule()
    {
        return $this->belongsTo(ESBTPEcheancierRule::class, 'rule_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
