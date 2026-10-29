<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Laboratory extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     * 
     * @var string
     */
    protected $table = 'laboratories';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'department_id',
        'director_id', // ID de l'utilisateur (enseignant) qui dirige le laboratoire
        'creation_date',
        'research_areas',
        'website',
        'email',
        'phone',
        'location',
        'logo',
        'status', // actif, inactif
        'funding_sources',
        'international_partnerships',
        'equipment',
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
        'research_areas' => 'array',
        'funding_sources' => 'array',
        'international_partnerships' => 'array',
        'equipment' => 'array',
    ];

    /**
     * Relation avec le département auquel est rattaché le laboratoire.
     * 
     * Un laboratoire est comme un atelier de recherche spécialisé au sein d'un département.
     * Par exemple, le laboratoire d'intelligence artificielle au sein du département d'informatique.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Relation avec l'enseignant-chercheur qui dirige le laboratoire.
     */
    public function director()
    {
        return $this->belongsTo(User::class, 'director_id');
    }

    /**
     * Relation avec les enseignants-chercheurs rattachés à ce laboratoire.
     */
    public function researchers()
    {
        return $this->hasMany(Teacher::class);
    }




    /**
     * Relation avec l'utilisateur qui a créé ce laboratoire.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation avec l'utilisateur qui a mis à jour ce laboratoire.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Obtenir le nombre de chercheurs dans ce laboratoire.
     * 
     * @return int
     */
    public function getResearcherCountAttribute()
    {
        return $this->researchers()->count();
    }

    /**
     * Obtenir le nombre de projets de recherche actifs.
     * 
     * @return int
     */
    public function getActiveProjectsCountAttribute()
    {
        return $this->researchProjects()->where('status', 'actif')->count();
    }

    /**
     * Obtenir le nombre de publications des 5 dernières années.
     * 
     * @return int
     */
    public function getRecentPublicationsCountAttribute()
    {
        $fiveYearsAgo = now()->subYears(5)->format('Y-m-d');
        return $this->publications()->where('publication_date', '>=', $fiveYearsAgo)->count();
    }

    /**
     * Scope pour filtrer les laboratoires par département.
     */
    public function scopeInDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope pour filtrer les laboratoires actifs.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'actif');
    }

    /**
     * Scope pour filtrer les laboratoires inactifs.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactif');
    }

    /**
     * Scope pour filtrer les laboratoires par domaine de recherche.
     */
    public function scopeWithResearchArea($query, $area)
    {
        return $query->where('research_areas', 'like', '%' . $area . '%');
    }
} 