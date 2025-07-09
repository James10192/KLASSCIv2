<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ESBTPTypeFrais extends Model
{
    use HasFactory;

    protected $table = 'esbtp_types_frais';

    protected $fillable = [
        'nom',
        'description',
        'montant_fixe',
        'periodicite',
        'conditions',
        'est_obligatoire',
        'actif'
    ];

    protected $casts = [
        'conditions' => 'json',
        'est_obligatoire' => 'boolean',
        'actif' => 'boolean',
        'montant_fixe' => 'decimal:2'
    ];

    /**
     * Scopes
     */
    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    public function scopeObligatoire($query)
    {
        return $query->where('est_obligatoire', true);
    }

    public function scopeParPeriodicite($query, $periodicite)
    {
        return $query->where('periodicite', $periodicite);
    }

    /**
     * Accessors
     */
    public function getMontantFormatteAttribute()
    {
        return $this->montant_fixe ? number_format($this->montant_fixe, 0, ',', ' ') . ' FCFA' : 'Variable';
    }

    public function getPeriodiciteFormateeAttribute()
    {
        $periodicites = [
            'unique' => 'Paiement unique',
            'mensuel' => 'Mensuel',
            'trimestriel' => 'Trimestriel',
            'semestriel' => 'Semestriel',
            'annuel' => 'Annuel'
        ];

        return $periodicites[$this->periodicite] ?? $this->periodicite;
    }

    public function getStatutFormatteAttribute()
    {
        return $this->actif ? 'Actif' : 'Inactif';
    }

    public function getStatutClassAttribute()
    {
        return $this->actif ? 'success' : 'secondary';
    }

    /**
     * Méthodes métier
     */
    public function calculerMontant($conditions = [])
    {
        if ($this->montant_fixe) {
            return $this->montant_fixe;
        }

        // Logique de calcul dynamique basée sur les conditions
        if ($this->conditions && is_array($this->conditions)) {
            return $this->appliquerConditions($conditions);
        }

        return 0;
    }

    private function appliquerConditions($conditions)
    {
        // Exemple de logique conditionnelle
        $montant = 0;

        if (isset($this->conditions['base']) && isset($conditions['niveau'])) {
            $montant = $this->conditions['base'];
            
            // Majoration selon le niveau
            if (isset($this->conditions['majoration_niveau'][$conditions['niveau']])) {
                $montant += $this->conditions['majoration_niveau'][$conditions['niveau']];
            }
        }

        return $montant;
    }

    public function estApplicablePour($etudiant, $inscription = null)
    {
        // Vérifie si ce type de frais est applicable pour un étudiant donné
        if (!$this->actif) {
            return false;
        }

        if ($this->conditions && is_array($this->conditions)) {
            return $this->verifierConditions($etudiant, $inscription);
        }

        return true;
    }

    private function verifierConditions($etudiant, $inscription)
    {
        // Vérification des conditions d'application
        if (isset($this->conditions['filieres']) && $inscription) {
            return in_array($inscription->filiere_id, $this->conditions['filieres']);
        }

        if (isset($this->conditions['niveaux']) && $inscription) {
            return in_array($inscription->niveau_id, $this->conditions['niveaux']);
        }

        return true;
    }
}
