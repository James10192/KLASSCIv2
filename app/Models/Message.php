<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'sender_id',
        'recipient_id',
        'subject',
        'content',
        'recipient_type',
        'recipient_group',
        'is_read',
        'read_at',
        'parent_id',
    ];

    /**
     * Les attributs qui doivent être convertis.
     *
     * @var array
     */
    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * Obtenir l'expéditeur du message.
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Obtenir le destinataire du message (si c'est un utilisateur spécifique).
     */
    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * Obtenir le message parent (pour les réponses).
     */
    public function parent()
    {
        return $this->belongsTo(Message::class, 'parent_id');
    }

    /**
     * Obtenir les réponses à ce message.
     */
    public function replies()
    {
        return $this->hasMany(Message::class, 'parent_id');
    }

    /**
     * Marquer le message comme lu.
     */
    public function markAsRead()
    {
        $this->is_read = true;
        $this->read_at = now();
        $this->save();
        
        return $this;
    }

    /**
     * Marquer le message comme non lu.
     */
    public function markAsUnread()
    {
        $this->is_read = false;
        $this->read_at = null;
        $this->save();
        
        return $this;
    }

    /**
     * Vérifier si le message est une réponse.
     */
    public function isReply()
    {
        return $this->parent_id !== null;
    }

    /**
     * Vérifier si le message est destiné à un groupe.
     */
    public function isGroupMessage()
    {
        return $this->recipient_type !== null && $this->recipient_type !== 'user';
    }

    /**
     * Get the count of unread messages for a specific user.
     *
     * @param int $userId The ID of the user
     * @return int The count of unread messages
     */
    public static function getUnreadCountForUser($userId)
    {
        try {
            if (!$userId) {
                return 0;
            }
            
            return self::where('recipient_id', $userId)
                ->where('is_read', false)
                ->count();
        } catch (\Exception $e) {
            \Log::error('Error getting unread message count: ' . $e->getMessage());
            return 0;
        }
    }
}
