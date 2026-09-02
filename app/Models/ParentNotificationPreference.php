<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentNotificationPreference extends Model
{
    protected $fillable = [
        'parent_id',
        'notify_inscriptions',
        'notify_paiements',
        'notify_absences',
        'notify_notes',
        'notify_bulletins',
        'notify_annonces',
        'preferred_channels',
        'absence_threshold',
        'grade_threshold',
        'attendance_rate_threshold',
        'reminder_frequency',
        'preferred_language',
        'last_notification_sent_at',
        'notifications_sent_count',
    ];

    /**
     * La colonne `preferred_channels` n'a plus de valeur par defaut en base :
     * MySQL 8 refuse un defaut sur une colonne JSON, et le schema ne se
     * creait plus du tout. Elle vit donc ici, ou elle s'applique quel que
     * soit le moteur — y compris a un `new` qui n'aurait pas renseigne le
     * champ.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'preferred_channels' => '["app", "email"]',
    ];

    protected $casts = [
        'notify_inscriptions' => 'boolean',
        'notify_paiements' => 'boolean',
        'notify_absences' => 'boolean',
        'notify_notes' => 'boolean',
        'notify_bulletins' => 'boolean',
        'notify_annonces' => 'boolean',
        'preferred_channels' => 'array',
        'absence_threshold' => 'integer',
        'grade_threshold' => 'decimal:1',
        'attendance_rate_threshold' => 'integer',
        'last_notification_sent_at' => 'datetime',
        'notifications_sent_count' => 'integer',
    ];

    /**
     * Relation avec le parent
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ESBTPParent::class, 'parent_id');
    }

    /**
     * Vérifier si un canal est activé
     */
    public function hasChannel(string $channel): bool
    {
        return in_array($channel, $this->preferred_channels ?? []);
    }

    /**
     * Vérifier si les notifications d'un type sont activées
     */
    public function isNotificationEnabled(string $type): bool
    {
        $field = "notify_{$type}";
        return $this->{$field} ?? true;
    }

    /**
     * Incrémenter le compteur de notifications envoyées
     */
    public function incrementNotificationCount(): void
    {
        $this->increment('notifications_sent_count');
        $this->update(['last_notification_sent_at' => now()]);
    }

    /**
     * Obtenir ou créer les préférences pour un parent
     */
    public static function getOrCreateForParent(int $parentId): self
    {
        return static::firstOrCreate(
            ['parent_id' => $parentId],
            [
                'notify_inscriptions' => true,
                'notify_paiements' => true,
                'notify_absences' => true,
                'notify_notes' => true,
                'notify_bulletins' => true,
                'notify_annonces' => true,
                'preferred_channels' => ['app', 'email'],
                'absence_threshold' => 3,
                'grade_threshold' => 10.0,
                'attendance_rate_threshold' => 80,
                'reminder_frequency' => 'immediate',
                'preferred_language' => 'fr',
            ]
        );
    }
}
