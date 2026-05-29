<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * Modèle des messages entrants WhatsApp (chat 2-way Phase 7).
 *
 * Auditable avec whitelist limitée (status, assigned_to_user_id, replied_at)
 * pour traçabilité workflow sans bloater la table audits.
 *
 * @property int $id
 * @property string $message_id Meta wa_id (idempotency)
 * @property string $from_phone E.164
 * @property int|null $parent_id Lookup automatique via phone
 * @property int|null $etudiant_id Si parent rattaché à 1 seul étudiant
 * @property string $type text/image/document/audio/video/location/other
 * @property string|null $body
 * @property string $status unread/read/replied/archived
 */
class WhatsAppInboundMessage extends Model implements AuditableContract
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'whatsapp_inbound_messages';

    protected $fillable = [
        'message_id',
        'from_phone',
        'to_number',
        'parent_id',
        'etudiant_id',
        'type',
        'body',
        'media_url',
        'raw_payload',
        'received_at',
        'read_at',
        'replied_at',
        'assigned_to_user_id',
        'replied_by_user_id',
        'status',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'received_at' => 'datetime',
        'read_at' => 'datetime',
        'replied_at' => 'datetime',
    ];

    /**
     * Whitelist Auditable — colonnes loggées dans table audits.
     */
    protected array $auditInclude = [
        'status',
        'assigned_to_user_id',
        'replied_by_user_id',
        'replied_at',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ESBTPParent::class, 'parent_id');
    }

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(ESBTPEtudiant::class, 'etudiant_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function repliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by_user_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(WhatsAppOutboundReply::class, 'inbound_message_id');
    }

    public function markAsRead(?int $userId = null): bool
    {
        return $this->update([
            'status' => 'read',
            'read_at' => now(),
            'assigned_to_user_id' => $userId ?? $this->assigned_to_user_id,
        ]);
    }

    public function markAsReplied(int $userId): bool
    {
        return $this->update([
            'status' => 'replied',
            'replied_at' => now(),
            'replied_by_user_id' => $userId,
        ]);
    }

    /**
     * Indique si le message est dans la fenêtre service Meta 24h (reply gratuit text).
     */
    public function isWithinServiceWindow(): bool
    {
        return $this->received_at->isAfter(now()->subHours(24));
    }
}
