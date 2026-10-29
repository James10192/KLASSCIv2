<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ESBTPBonSortieNotification extends Model
{
    use HasFactory;

    protected $table = 'esbtp_bon_sortie_notifications';

    protected $fillable = [
        'bon_sortie_id',
        'user_id',
        'type',
        'sent_at',
        'read_at',
    ];

    /**
     * Get the bon de sortie associated with the notification.
     */
    public function bonSortie()
    {
        return $this->belongsTo(ESBTPBonSortie::class, 'bon_sortie_id');
    }

    /**
     * Get the user who received the notification.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
} 