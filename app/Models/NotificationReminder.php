<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class NotificationReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'remindable_type',
        'remindable_id',
        'reminder_count',
        'last_reminder_sent_at',
        'next_reminder_at',
        'is_active',
    ];

    protected $casts = [
        'last_reminder_sent_at' => 'datetime',
        'next_reminder_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Relation polymorphique vers l'entité (inscription ou paiement)
     */
    public function remindable()
    {
        return $this->morphTo();
    }

    /**
     * Créer ou récupérer un reminder pour une entité
     */
    public static function getOrCreateForRemindable($remindableType, $remindableId)
    {
        return self::firstOrCreate(
            [
                'remindable_type' => $remindableType,
                'remindable_id' => $remindableId,
            ],
            [
                'reminder_count' => 0,
                'is_active' => true,
            ]
        );
    }

    /**
     * Enregistrer l'envoi d'un rappel et calculer le prochain
     */
    public function recordReminderSent($frequencyInDays)
    {
        $this->update([
            'reminder_count' => $this->reminder_count + 1,
            'last_reminder_sent_at' => now(),
            'next_reminder_at' => now()->addDays($frequencyInDays),
        ]);
    }

    /**
     * Désactiver les rappels (quand l'entité n'est plus en attente)
     */
    public function deactivate()
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Scope pour récupérer les reminders prêts à être envoyés
     */
    public function scopeReadyToSend($query)
    {
        return $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('next_reminder_at')
                          ->orWhere('next_reminder_at', '<=', now());
                    });
    }

    /**
     * Scope pour un type spécifique
     */
    public function scopeForType($query, $type)
    {
        return $query->where('remindable_type', $type);
    }
}
