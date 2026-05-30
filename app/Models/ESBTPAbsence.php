<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ESBTPAbsence extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'esbtp_absences';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'etudiant_id',
        'cours_id',
        'date',
        'heure_debut',
        'heure_fin',
        'justifie',
        'motif',
        'document_justificatif',
        'commentaire',
        'created_by',
        'updated_by'
    ];

    /**
     * Les attributs qui doivent être convertis en types natifs.
     *
     * @var array
     */
    protected $casts = [
        'date' => 'date',
        'heure_debut' => 'datetime',
        'heure_fin' => 'datetime',
        'justifie' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relation avec l'étudiant associé à cette absence.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function etudiant()
    {
        return $this->belongsTo(ESBTPEtudiant::class, 'etudiant_id');
    }

    /**
     * Relation avec le cours associé à cette absence.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function cours()
    {
        return $this->belongsTo(ESBTPCours::class, 'cours_id');
    }

    /**
     * Relation avec l'utilisateur qui a enregistré l'absence.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation avec l'utilisateur qui a mis à jour l'absence.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Calculer la durée de l'absence en heures.
     *
     * @return float
     */
    public function getDureeHeuresAttribute()
    {
        if (!$this->heure_debut || !$this->heure_fin) {
            return 0;
        }

        $debut = $this->heure_debut;
        $fin = $this->heure_fin;

        return round($fin->diffInMinutes($debut) / 60, 2);
    }

    /**
     * Déterminer si l'absence est récente (moins de 7 jours).
     *
     * @return bool
     */
    public function getIsRecenteAttribute()
    {
        return $this->date->diffInDays(now()) < 7;
    }

    /**
     * Obtenir le statut formaté de l'absence.
     *
     * @return string
     */
    public function getStatutFormateAttribute()
    {
        if ($this->justifie) {
            return 'Justifiée';
        }

        return 'Non justifiée';
    }

    /**
     * Accès à la matière via la relation avec le cours
     * Cette méthode est ajoutée pour résoudre l'erreur dans la génération du PDF
     */
    public function matiere()
    {
        // Vérifier si la relation cours existe et est chargée
        return $this->belongsTo(ESBTPMatiere::class, 'matiere_id');
    }
}
