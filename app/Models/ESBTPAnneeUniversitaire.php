<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ESBTPAnneeUniversitaire extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'esbtp_annee_universitaires';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_current',
        'is_active',
        'description',
    ];

    /**
     * Les attributs qui doivent être convertis en types natifs.
     *
     * @var array
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Obtenir les inscriptions associées à cette année universitaire.
     *
     * Une année universitaire peut avoir plusieurs inscriptions.
     * Par exemple, l'année 2024-2025 peut avoir plusieurs étudiants inscrits.
     */
    public function inscriptions()
    {
        return $this->hasMany(ESBTPInscription::class, 'annee_universitaire_id');
    }

    /**
     * Définir cette année universitaire comme l'année en cours.
     * Cette méthode désactive également toutes les autres années universitaires.
     *
     * @return bool
     */
    public function setAsCurrent()
    {
        // Désactiver toutes les autres années universitaires
        self::where('id', '!=', $this->id)
            ->update(['is_current' => false]);

        // Définir cette année comme l'année en cours
        $this->is_current = true;
        return $this->save();
    }
}
