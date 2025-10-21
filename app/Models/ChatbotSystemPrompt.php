<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotSystemPrompt extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'prompt',
        'allowed_roles',
        'is_active',
        'is_default',
        'priority',
    ];

    protected $casts = [
        'allowed_roles' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeForRole($query, string $role)
    {
        return $query->active()->whereJsonContains('allowed_roles', $role);
    }

    public function scopeHighestPriority($query)
    {
        return $query->orderByDesc('priority');
    }
}
