<?php

namespace App\Models\ESBTP;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeCategoryRuleInstallment extends Model
{
    use HasFactory;

    protected $fillable = [
        'fee_category_rule_id',
        'label',
        'offset_days',
        'amount',
        'pourcentage',
    ];

    public function rule()
    {
        return $this->belongsTo(FeeCategoryRule::class, 'fee_category_rule_id');
    }
}
