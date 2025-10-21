<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotDisplayTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'description',
        'html_template',
        'required_fields',
        'optional_fields',
        'is_active',
    ];

    protected $casts = [
        'required_fields' => 'array',
        'optional_fields' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByName($query, string $name)
    {
        return $query->where('name', $name);
    }
}
