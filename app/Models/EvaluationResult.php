<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationResult extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     * 
     * @var string
     */
    protected $table = 'evaluation_results';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_id',
        'evaluation_id',
        'grade',
        'is_absent',
        'is_excused',
        'excuse_reason',
        'has_supporting_document',
        'supporting_document_path',
        'feedback',
        'comments',
        'graded_by',
        'updated_by',
    ];

    /**
     * Les attributs qui doivent être convertis.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'grade' => 'float',
        'is_absent' => 'boolean',
        'is_excused' => 'boolean',
        'has_supporting_document' => 'boolean',
    ];

    /**
     * Relation avec l'étudiant concerné par ce résultat.
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Relation avec l'évaluation concernée.
     */
    public function evaluation()
    {
        return $this->belongsTo(Evaluation::class);
    }

    /**
     * Relation avec l'utilisateur qui a noté cette évaluation.
     */
    public function gradedBy()
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    /**
     * Relation avec l'utilisateur qui a mis à jour ce résultat.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Vérifier si l'étudiant a réussi l'évaluation.
     * 
     * @return bool|null
     */
    public function isPassed()
    {
        if ($this->is_absent && !$this->is_excused) {
            return false;
        }
        
        if ($this->grade === null) {
            return null;
        }
        
        // La note de passage est généralement 10/20 en France
        return $this->grade >= 10;
    }

    /**
     * Obtenir le statut du résultat (réussi, échoué, absent, excusé).
     * 
     * @return string
     */
    public function getStatusAttribute()
    {
        if ($this->is_absent) {
            return $this->is_excused ? 'excusé' : 'absent';
        }
        
        if ($this->grade === null) {
            return 'en attente';
        }
        
        return $this->grade >= 10 ? 'réussi' : 'échoué';
    }

    /**
     * Obtenir la mention correspondant à la note.
     * 
     * @return string|null
     */
    public function getMentionAttribute()
    {
        if ($this->grade === null || $this->is_absent) {
            return null;
        }
        
        if ($this->grade < 10) {
            return 'Insuffisant';
        } elseif ($this->grade < 12) {
            return 'Passable';
        } elseif ($this->grade < 14) {
            return 'Assez Bien';
        } elseif ($this->grade < 16) {
            return 'Bien';
        } else {
            return 'Très Bien';
        }
    }

    /**
     * Scope pour filtrer les résultats par note minimale.
     */
    public function scopeMinGrade($query, $minGrade)
    {
        return $query->where('grade', '>=', $minGrade);
    }

    /**
     * Scope pour filtrer les résultats par note maximale.
     */
    public function scopeMaxGrade($query, $maxGrade)
    {
        return $query->where('grade', '<=', $maxGrade);
    }

    /**
     * Scope pour filtrer les résultats des étudiants absents.
     */
    public function scopeAbsent($query)
    {
        return $query->where('is_absent', true);
    }

    /**
     * Scope pour filtrer les résultats des étudiants excusés.
     */
    public function scopeExcused($query)
    {
        return $query->where('is_absent', true)->where('is_excused', true);
    }

    /**
     * Scope pour filtrer les résultats des étudiants présents.
     */
    public function scopePresent($query)
    {
        return $query->where('is_absent', false);
    }

    /**
     * Scope pour filtrer les résultats réussis.
     */
    public function scopePassed($query)
    {
        return $query->where('grade', '>=', 10);
    }

    /**
     * Scope pour filtrer les résultats échoués.
     */
    public function scopeFailed($query)
    {
        return $query->where('grade', '<', 10);
    }

    /**
     * Scope pour filtrer les résultats en attente de notation.
     */
    public function scopePending($query)
    {
        return $query->whereNull('grade')->where('is_absent', false);
    }
} 