<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ESBTPAttendance extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'esbtp_attendances';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'seance_cours_id',
        'etudiant_id',
        'date',
        'heure_debut',
        'heure_fin',
        'statut', // 'present', 'absent', 'retard', 'excuse'
        'call_type', // 'start', 'end'
        'commentaire',
        'document_path',
        'justified_at',
        'created_by',
        'updated_by'
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array
     */
    protected $casts = [
        'date' => 'date',
        'justified_at' => 'datetime',
    ];

    /**
     * Relation avec la séance de cours.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function seanceCours()
    {
        return $this->belongsTo(ESBTPSeanceCours::class, 'seance_cours_id');
    }

    /**
     * Relation avec l'étudiant.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function etudiant()
    {
        return $this->belongsTo(ESBTPEtudiant::class, 'etudiant_id');
    }

    /**
     * Relation avec l'utilisateur qui a créé l'enregistrement.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation avec l'utilisateur qui a modifié l'enregistrement.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope pour filtrer par classe.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $classeId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeParClasse($query, $classeId)
    {
        return $query->whereHas('seanceCours.emploiTemps', function($q) use ($classeId) {
            $q->where('classe_id', $classeId);
        });
    }

    /**
     * Scope pour filtrer par étudiant.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $etudiantId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeParEtudiant($query, $etudiantId)
    {
        return $query->where('etudiant_id', $etudiantId);
    }

    /**
     * Scope pour filtrer par date.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeParDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    /**
     * Scope pour filtrer par statut.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $statut
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeParStatut($query, $statut)
    {
        return $query->where('statut', $statut);
    }
}
