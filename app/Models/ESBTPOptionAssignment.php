<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ESBTPOptionAssignment extends Model
{
    use HasFactory;

    protected $table = 'esbtp_option_assignments';

    protected $fillable = [
        'option_id',
        'filiere_id',
        'niveau_id',
        'assignment_type',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relation avec l'option (variant)
     */
    public function option()
    {
        return $this->belongsTo(ESBTPFraisOption::class, 'option_id');
    }

    /**
     * Relation avec la filière
     */
    public function filiere()
    {
        return $this->belongsTo(ESBTPFiliere::class, 'filiere_id');
    }

    /**
     * Relation avec le niveau d'étude
     */
    public function niveau()
    {
        return $this->belongsTo(ESBTPNiveauEtude::class, 'niveau_id');
    }

    /**
     * Scope pour les assignations actives
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour les assignations d'une option spécifique
     */
    public function scopeForOption($query, $optionId)
    {
        return $query->where('option_id', $optionId);
    }

    /**
     * Méthode pour obtenir le label d'affichage de l'assignation
     */
    public function getDisplayLabelAttribute()
    {
        switch ($this->assignment_type) {
            case 'all':
                return 'Tous les étudiants';
            case 'filiere':
                return 'Filière: ' . ($this->filiere->name ?? 'Inconnue');
            case 'niveau':
                return 'Niveau: ' . ($this->niveau->name ?? 'Inconnu');
            case 'classe':
                return ($this->filiere->name ?? 'Filière') . ' - ' . ($this->niveau->name ?? 'Niveau');
            default:
                return 'Assignation inconnue';
        }
    }

    /**
     * Méthode statique pour créer ou mettre à jour les assignations d'une option
     */
    public static function updateAssignmentsForOption($optionId, $assignmentType, $filieres = [], $niveaux = [])
    {
        // Supprimer les anciennes assignations
        self::where('option_id', $optionId)->delete();

        if ($assignmentType === 'all') {
            // Assignation pour tous les étudiants
            return self::create([
                'option_id' => $optionId,
                'assignment_type' => 'all',
                'filiere_id' => null,
                'niveau_id' => null,
            ]);
        }

        $assignments = [];

        if ($assignmentType === 'filiere') {
            // Assignation par filière seulement
            foreach ($filieres as $filiereId) {
                $assignments[] = self::create([
                    'option_id' => $optionId,
                    'assignment_type' => 'filiere',
                    'filiere_id' => $filiereId,
                    'niveau_id' => null,
                ]);
            }
        } elseif ($assignmentType === 'niveau') {
            // Assignation par niveau seulement
            foreach ($niveaux as $niveauId) {
                $assignments[] = self::create([
                    'option_id' => $optionId,
                    'assignment_type' => 'niveau',
                    'filiere_id' => null,
                    'niveau_id' => $niveauId,
                ]);
            }
        } elseif ($assignmentType === 'classe') {
            // Assignation par classe (filière + niveau)
            foreach ($filieres as $filiereId) {
                foreach ($niveaux as $niveauId) {
                    $assignments[] = self::create([
                        'option_id' => $optionId,
                        'assignment_type' => 'classe',
                        'filiere_id' => $filiereId,
                        'niveau_id' => $niveauId,
                    ]);
                }
            }
        }

        return $assignments;
    }
}