<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ESBTPEcheancierRule extends Model
{
    use HasFactory;

    protected $table = 'esbtp_echeancier_rules';

    public const STATUS_ALL = 'all';
    public const STATUS_AFFECTE = 'affecté';
    public const STATUS_REAFFECTE = 'réaffecté';
    public const STATUS_NON_AFFECTE = 'non_affecté';

    public const SCOPE_CONFIGURATION = 'configuration';
    public const SCOPE_OPTION_ASSIGNMENT = 'option_assignment';

    protected $fillable = [
        'scope_type',
        'scope_id',
        'affectation_status',
        'priority',
        'is_active',
        'effective_from',
        'effective_to',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'priority' => 'integer',
        'is_active' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function lines()
    {
        return $this->hasMany(ESBTPEcheancierRuleLine::class, 'rule_id')->orderBy('sort_order');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForScope($query, string $scopeType, int $scopeId)
    {
        return $query
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId);
    }

    public function scopeValidAt($query, $date = null)
    {
        $date = $date ?: now()->toDateString();

        return $query->where(function ($q) use ($date) {
            $q->whereNull('effective_from')->orWhere('effective_from', '<=', $date);
        })->where(function ($q) use ($date) {
            $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date);
        });
    }

    public static function normalizeStatus(?string $status): string
    {
        $value = trim((string) $status);
        if ($value === '') {
            return self::STATUS_ALL;
        }

        $map = [
            'all' => self::STATUS_ALL,
            'affecte' => self::STATUS_AFFECTE,
            'affecté' => self::STATUS_AFFECTE,
            'reaffecte' => self::STATUS_REAFFECTE,
            'réaffecté' => self::STATUS_REAFFECTE,
            'non_affecte' => self::STATUS_NON_AFFECTE,
            'non-affecte' => self::STATUS_NON_AFFECTE,
            'non_affecté' => self::STATUS_NON_AFFECTE,
            'non-affecté' => self::STATUS_NON_AFFECTE,
        ];

        $key = mb_strtolower($value);

        return $map[$key] ?? self::STATUS_ALL;
    }
}
