<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Grade extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'grades';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_id', // Étudiant concerné
        'evaluation_id', // Évaluation concernée
        'score', // Note obtenue
        'comments', // Commentaires sur la note
        'is_absent', // Si l'étudiant était absent
        'is_excused', // Si l'absence est justifiée
        'created_by',
        'updated_by',
    ];

    /**
     * Les attributs qui doivent être convertis en types natifs.
     *
     * @var array
     */
    protected $casts = [
        'score' => 'float',
        'is_absent' => 'boolean',
        'is_excused' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Obtenir l'étudiant associé à cette note.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Obtenir l'évaluation associée à cette note.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function evaluation()
    {
        return $this->belongsTo(Evaluation::class);
    }

    /**
     * Obtenir l'utilisateur qui a créé la note.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Obtenir l'utilisateur qui a mis à jour la note.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Obtenir la note sur 20.
     * 
     * @return float|null
     */
    public function getScoreOn20()
    {
        if ($this->is_absent) {
            return 0;
        }
        
        $maxScore = $this->evaluation->max_score;
        
        if ($maxScore == 0 || $maxScore == 20) {
            return $this->score;
        }
        
        return ($this->score / $maxScore) * 20;
    }

    /**
     * Vérifier si la note est supérieure ou égale à la moyenne.
     * 
     * @param float $average La moyenne à comparer (par défaut 10)
     * @return bool
     */
    public function isAboveAverage($average = 10)
    {
        return $this->getScoreOn20() >= $average;
    }

    /**
     * Obtenir la mention correspondant à la note.
     * 
     * @return string
     */
    public function getMention()
    {
        $score = $this->getScoreOn20();
        
        if ($this->is_absent) {
            return $this->is_excused ? 'Absence justifiée' : 'Absence';
        }
        
        if ($score < 10) {
            return 'Insuffisant';
        } elseif ($score < 12) {
            return 'Passable';
        } elseif ($score < 14) {
            return 'Assez bien';
        } elseif ($score < 16) {
            return 'Bien';
        } else {
            return 'Très bien';
        }
    }
} 