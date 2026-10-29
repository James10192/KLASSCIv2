<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Secretary extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     * 
     * @var string
     */
    protected $table = 'secretaries';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'service',
        'ufr_id',
        'department_id',
        'accreditation_level', // establishment, ufr, department
        'job_title',
        'office_location',
        'office_hours',
        'extension_number',
        'created_by',
        'updated_by',
    ];

    /**
     * Les attributs qui doivent être convertis.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'office_hours' => 'array',
    ];

    /**
     * Relation avec l'utilisateur.
     * 
     * Un secrétaire est lié à un utilisateur.
     * Comme un employé qui a un badge d'identification, l'utilisateur est l'identité
     * et le secrétaire est le rôle spécifique avec ses propres attributs.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec l'UFR auquel le secrétaire est rattaché.
     */
    public function ufr()
    {
        return $this->belongsTo(UFR::class);
    }

    /**
     * Relation avec le département auquel le secrétaire est rattaché.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
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
     * Vérifier si le secrétaire a une accréditation au niveau de l'établissement.
     * 
     * @return bool
     */
    public function hasEstablishmentAccreditation()
    {
        return $this->accreditation_level === 'establishment';
    }

    /**
     * Vérifier si le secrétaire a une accréditation au niveau de l'UFR.
     * 
     * @return bool
     */
    public function hasUfrAccreditation()
    {
        return $this->accreditation_level === 'ufr' || $this->hasEstablishmentAccreditation();
    }

    /**
     * Vérifier si le secrétaire a une accréditation au niveau du département.
     * 
     * @return bool
     */
    public function hasDepartmentAccreditation()
    {
        return $this->accreditation_level === 'department' || $this->hasUfrAccreditation();
    }

    /**
     * Obtenir le nom complet du secrétaire.
     * 
     * @return string
     */
    public function getFullNameAttribute()
    {
        return $this->user->full_name;
    }

    /**
     * Obtenir l'adresse email du secrétaire.
     * 
     * @return string
     */
    public function getEmailAttribute()
    {
        return $this->user->email;
    }

    /**
     * Obtenir le numéro de téléphone du secrétaire.
     * 
     * @return string|null
     */
    public function getPhoneAttribute()
    {
        return $this->user->phone;
    }
} 