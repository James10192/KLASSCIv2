<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * Modèle des réponses sortantes WhatsApp envoyées par les agents école.
 *
 * Trace audit + cost tracking pour KPI temps de réponse / satisfaction.
 * Auditable car contenu sensible (réponse école à parent — preuve communication).
 */
class WhatsAppOutboundReply extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $table = 'whatsapp_outbound_replies';

    protected $fillable = [
        'inbound_message_id',
        'sent_by_user_id',
        'body',
        'meta_message_id',
        'type',
        'template_name',
        'status',
        'error_message',
        'cost_fcfa',
        'sent_at',
        'delivered_at',
        'read_at',
        'failed_at',
    ];

    protected $casts = [
        'cost_fcfa' => 'decimal:2',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    protected array $auditInclude = [
        'status',
        'meta_message_id',
        'error_message',
    ];

    public function inboundMessage(): BelongsTo
    {
        return $this->belongsTo(WhatsAppInboundMessage::class, 'inbound_message_id');
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }
}
