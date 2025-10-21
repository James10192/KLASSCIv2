<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotKnowledgeBase extends Model
{
    use HasFactory;

    protected $table = 'chatbot_knowledge_base';

    protected $fillable = [
        'intent',
        'route',
        'controller',
        'model',
        'table_name',
        'columns_mapping',
        'display_columns',
        'deep_link_pattern',
        'required_permissions',
        'allowed_roles',
        'exploration_log',
        'last_used_at',
        'usage_count',
    ];

    protected $casts = [
        'columns_mapping' => 'array',
        'display_columns' => 'array',
        'required_permissions' => 'array',
        'allowed_roles' => 'array',
        'last_used_at' => 'datetime',
    ];

    /**
     * Incrémenter le compteur d'utilisation et mettre à jour last_used_at
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Scope: Par intent
     */
    public function scopeByIntent($query, string $intent)
    {
        return $query->where('intent', $intent);
    }

    /**
     * Scope: Par modèle
     */
    public function scopeByModel($query, string $model)
    {
        return $query->where('model', $model);
    }

    /**
     * Scope: Les plus utilisés
     */
    public function scopeMostUsed($query, int $limit = 10)
    {
        return $query->orderByDesc('usage_count')->limit($limit);
    }

    /**
     * Scope: Utilisés récemment
     */
    public function scopeRecentlyUsed($query, int $limit = 10)
    {
        return $query->orderByDesc('last_used_at')->limit($limit);
    }

    /**
     * Vérifier si un rôle est autorisé
     */
    public function isRoleAllowed(string $role): bool
    {
        if (empty($this->allowed_roles)) {
            return true; // Si vide, accessible à tous
        }

        return in_array($role, $this->allowed_roles);
    }

    /**
     * Vérifier si une permission est requise
     */
    public function hasRequiredPermissions(array $userPermissions): bool
    {
        if (empty($this->required_permissions)) {
            return true; // Pas de permission requise
        }

        // Vérifier que l'utilisateur a au moins une des permissions requises
        return !empty(array_intersect($this->required_permissions, $userPermissions));
    }
}
