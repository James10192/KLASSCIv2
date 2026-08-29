<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UFR extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'ufrs';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'director_id', // ID de l'utilisateur qui est directeur de l'UFR
        'created_by',
        'updated_by',
    ];

    /**
     * Les attributs qui doivent être convertis en types natifs.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Obtenir le directeur de l'UFR.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function director()
    {
        return $this->belongsTo(User::class, 'director_id');
    }

    /**
     * Obtenir les départements associés à cette UFR.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function departments()
    {
        return $this->hasMany(Department::class);
    }


    /**
     * Obtenir l'utilisateur qui a créé l'UFR.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Obtenir l'utilisateur qui a mis à jour l'UFR.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Obtenir le nombre de départements dans l'UFR.
     * 
     * @return int
     */
    public function getDepartmentCount()
    {
        return $this->departments()->count();
    }

    /**
     * Obtenir le nombre de formations dans l'UFR.
     * 
     * @return int
     */
    public function getFormationCount()
    {
        return $this->formations()->count();
    }
} 