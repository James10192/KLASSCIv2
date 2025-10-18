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
        'annee_universitaire_id',
        'classe_id',
        'matiere_id',
        'teacher_id',
        'date',
        'heure_debut',
        'heure_fin',
        'statut', // 'present', 'absent', 'retard', 'excuse'
        'status', // Alias for statut (for backward compatibility)
        'call_type', // 'start', 'end'
        'is_justified',
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
     * Relation avec la classe.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function classe()
    {
        return $this->belongsTo(ESBTPClasse::class, 'classe_id');
    }

    /**
     * Relation avec la matière.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function matiere()
    {
        return $this->belongsTo(ESBTPMatiere::class, 'matiere_id');
    }

    /**
     * Relation avec l'enseignant.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function teacher()
    {
        return $this->belongsTo(ESBTPTeacher::class, 'teacher_id');
    }

    /**
     * Relation avec l'année universitaire.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function anneeUniversitaire()
    {
        return $this->belongsTo(ESBTPAnneeUniversitaire::class, 'annee_universitaire_id');
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

    /**
     * Scope pour récupérer uniquement les présences FINALES (statut fusionné).
     *
     * Récupère LE PLUS RÉCENT enregistrement 'merged' (fusion début + fin) par séance/étudiant
     * OU les enregistrements 'start' si pas encore de fusion (appel de fin non fait).
     *
     * IMPORTANT: Utilise MAX(id) pour éviter les doublons quand plusieurs 'merged' existent
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFinalOnly($query)
    {
        return $query->whereIn('id', function($subquery) {
            $subquery->select(\DB::raw('MAX(id)'))
                ->from('esbtp_attendances as att_final')
                ->where(function($q) {
                    $q->where('call_type', 'merged')
                      ->orWhere(function($subq) {
                          // Seulement les 'start' qui n'ont pas encore de 'merged' pour cette séance
                          $subq->where('call_type', 'start')
                              ->whereNotExists(function($exists) {
                                  $exists->select(\DB::raw(1))
                                      ->from('esbtp_attendances as att_check_merged')
                                      ->whereColumn('att_check_merged.seance_cours_id', 'att_final.seance_cours_id')
                                      ->whereColumn('att_check_merged.etudiant_id', 'att_final.etudiant_id')
                                      ->where('att_check_merged.call_type', 'merged')
                                      ->whereNull('att_check_merged.deleted_at');
                              });
                      });
                })
                ->whereNull('att_final.deleted_at')
                ->groupBy('att_final.seance_cours_id', 'att_final.etudiant_id');
        });
    }
}
