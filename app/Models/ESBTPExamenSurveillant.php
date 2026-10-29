<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ESBTPExamenSurveillant extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'esbtp_examen_surveillants';

    protected $fillable = [
        'examen_id',
        'user_id',
        'role',
        'notification_sent',
        'notification_sent_at',
        'confirmed',
        'confirmed_at',
        'notes',
    ];

    protected $casts = [
        'notification_sent' => 'boolean',
        'confirmed' => 'boolean',
        'notification_sent_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function examen(): BelongsTo
    {
        return $this->belongsTo(ESBTPExamenPlanifie::class, 'examen_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
