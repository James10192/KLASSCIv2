<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ESBTPLaboratory extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'esbtp_laboratories';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'department_id',
        'location',
        'capacity',
        'equipment',
        'is_active',
        'created_by',
        'updated_by'
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
        'capacity' => 'integer',
        'equipment' => 'array'
    ];

    /**
     * Get the department that owns this laboratory.
     */
    public function department()
    {
        return $this->belongsTo(ESBTPDepartment::class, 'department_id');
    }

    /**
     * Get the teachers associated with this laboratory.
     */
    public function teachers()
    {
        return $this->hasMany(ESBTPTeacher::class, 'laboratory_id');
    }

    /**
     * Get the user who created this laboratory.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this laboratory.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
