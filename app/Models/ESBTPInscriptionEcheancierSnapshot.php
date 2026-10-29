<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ESBTPInscriptionEcheancierSnapshot extends Model
{
    use HasFactory;

    protected $table = 'esbtp_inscription_echeancier_snapshots';

    protected $fillable = [
        'inscription_id',
        'snapshot_version',
        'payload',
        'generated_at',
        'computed_overdue_amount',
        'computed_overdue_days',
        'last_recomputed_at',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'payload' => 'array',
        'generated_at' => 'datetime',
        'computed_overdue_amount' => 'float',
        'computed_overdue_days' => 'integer',
        'last_recomputed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function inscription()
    {
        return $this->belongsTo(ESBTPInscription::class, 'inscription_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
