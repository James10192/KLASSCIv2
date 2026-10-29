<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     * 
     * @var string
     */
    protected $table = 'departments';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'ufr_id',
        'director_id', // ID de l'utilisateur (enseignant) qui dirige le département
        'creation_date',
        'website',
        'email',
        'phone',
        'location',
        'logo',
        'status', // actif, inactif
        'created_by',
        'updated_by',
    ];

    /**
     * Les attributs qui doivent être convertis.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'creation_date' => 'date',
    ];

    /**
     * Relation avec l'UFR auquel appartient le département.
     * 
     * Un département est comme une division spécialisée au sein d'une UFR.
     * Par exemple, le département d'informatique au sein de l'UFR Sciences.
     */
    public function ufr()
    {
        return $this->belongsTo(UFR::class);
    }

    /**
     * Relation avec l'enseignant qui dirige le département.
     */
    public function director()
    {
        return $this->belongsTo(User::class, 'director_id');
    }

    /**
     * Relation avec les enseignants rattachés à ce département.
     */
    public function teachers()
    {
        return $this->hasMany(Teacher::class);
    }


    /**
     * Relation avec les parcours proposés par ce département.
     */
    public function parcours()
    {
        return $this->hasMany(Parcours::class);
    }

    /**
     * Relation avec les secrétaires affectées à ce département.
     */
    public function secretaries()
    {
        return $this->hasMany(Secretary::class);
    }

    /**
     * Relation avec les laboratoires associés à ce département.
     */
    public function laboratories()
    {
        return $this->hasMany(Laboratory::class);
    }

    /**
     * Relation avec l'utilisateur qui a créé ce département.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation avec l'utilisateur qui a mis à jour ce département.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Obtenir le nombre d'enseignants dans ce département.
     * 
     * @return int
     */
    public function getTeacherCountAttribute()
    {
        return $this->teachers()->count();
    }

    /**
     * Obtenir le nombre d'étudiants inscrits dans les parcours de ce département.
     * 
     * @return int
     */
    public function getStudentCountAttribute()
    {
        return Student::whereHas('parcours', function($query) {
            $query->where('department_id', $this->id);
        })->count();
    }

    /**
     * Scope pour filtrer les départements par UFR.
     */
    public function scopeInUfr($query, $ufrId)
    {
        return $query->where('ufr_id', $ufrId);
    }

    /**
     * Scope pour filtrer les départements actifs.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'actif');
    }

    /**
     * Scope pour filtrer les départements inactifs.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactif');
    }
} 