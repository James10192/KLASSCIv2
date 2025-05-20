<?php

namespace App\Models\ESBTP;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeeCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'default_amount',
        'is_active',
        'is_mandatory',
    ];

    protected $casts = [
        'default_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'is_mandatory' => 'boolean',
    ];

    public function fees()
    {
        return $this->hasMany(Fee::class, 'fee_category_id');
    }

    public function rules()
    {
        return $this->hasMany(FeeCategoryRule::class, 'fee_category_id');
    }
}
