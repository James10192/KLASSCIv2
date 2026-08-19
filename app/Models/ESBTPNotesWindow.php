<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ESBTPNotesWindow extends Model
{
    protected $table = 'esbtp_notes_windows';

    protected $fillable = [
        'classe_id',
        'starts_at',
        'ends_at',
        'opened_by',
        'closed_at',
        'closed_by',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
        'closed_at' => 'datetime',
    ];

    public function classe(): BelongsTo
    {
        return $this->belongsTo(ESBTPClasse::class, 'classe_id');
    }

    public function isOpenOn(?\DateTimeInterface $date = null): bool
    {
        if ($this->closed_at !== null) {
            return false;
        }

        $day = \Carbon\Carbon::parse($date ?? now())->startOfDay();

        return $day->betweenIncluded($this->starts_at->startOfDay(), $this->ends_at->endOfDay());
    }
}