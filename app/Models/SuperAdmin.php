<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SuperAdmin extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     * 
     * @var string
     */
    protected $table = 'super_admins';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'access_level',
        'dashboard_preferences',
        'last_system_check',
        'created_by',
        'updated_by',
    ];

    /**
     * Les attributs qui doivent être convertis.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'dashboard_preferences' => 'array',
        'last_system_check' => 'datetime',
    ];

    /**
     * Relation avec l'utilisateur.
     * 
     * Un super administrateur est lié à un utilisateur.
     * C'est comme si l'utilisateur portait le "chapeau" de super administrateur.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec l'utilisateur qui a créé ce profil.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation avec l'utilisateur qui a mis à jour ce profil.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Vérifier si le super administrateur a un accès complet.
     * 
     * @return bool
     */
    public function hasFullAccess()
    {
        return $this->access_level === 'full';
    }

    /**
     * Obtenir le nom complet du super administrateur.
     * 
     * @return string
     */
    public function getFullNameAttribute()
    {
        return $this->user->full_name;
    }
} 