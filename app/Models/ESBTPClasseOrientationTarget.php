<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ESBTPClasseOrientationTarget extends Model
{
    use HasFactory;

    protected $table = 'esbtp_classe_orientation_targets';

    protected $fillable = [
        'source_classe_id',
        'target_classe_id',
        'semestre_activation',
        'is_active',
        'sort_order',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function sourceClasse()
    {
        return $this->belongsTo(ESBTPClasse::class, 'source_classe_id');
    }

    public function targetClasse()
    {
        return $this->belongsTo(ESBTPClasse::class, 'target_classe_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
