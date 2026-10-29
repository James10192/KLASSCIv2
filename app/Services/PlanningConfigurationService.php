<?php

namespace App\Services;

use App\Models\ESBTPPlanificationAcademique;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PlanningConfigurationService
{
    /**
     * Configuration rapide d'une matière pour une combinaison filière/niveau
     */
    public function configureRapide(array $data): ESBTPPlanificationAcademique
    {
        $validated = $this->validateRapideData($data);
        
        return DB::transaction(function () use ($validated) {
            // Vérifier si une planification existe déjà
            $existing = $this->findExistingPlanification(
                $validated['matiere_id'],
                $validated['annee_id'],
                $validated['filiere_id'] ?? null,
                $validated['niveau_id'] ?? null,
                $validated['semestre'] ?? 1
            );
            
            if ($existing) {
                // Mettre à jour la planification existante
                return $this->updatePlanification($existing, $validated);
            } else {
                // Créer une nouvelle planification
                return $this->createPlanification($validated);
            }
        });
    }
    
    /**
     * Configuration avancée d'une planification
     */
    public function configureAvance(array $data): ESBTPPlanificationAcademique
    {
        $validated = $this->validateAvanceData($data);
        
        return DB::transaction(function () use ($validated) {
            $existing = $this->findExistingPlanification(
                $validated['matiere_id'],
                $validated['annee_universitaire_id'],
                $validated['filiere_id'] ?? null,
                $validated['niveau_etude_id'] ?? null,
                $validated['semestre'] ?? 1
            );
            
            if ($existing) {
                return $this->updatePlanificationAvancee($existing, $validated);
            } else {
                return $this->createPlanificationAvancee($validated);
            }
        });
    }
    
    /**
     * Configuration en lot pour plusieurs matières
     */
    public function configureBulk(array $selections, array $baseConfig): Collection
    {
        $results = collect();
        
        DB::transaction(function () use ($selections, $baseConfig, &$results) {
            foreach ($selections as $selection) {
                $config = array_merge($baseConfig, $selection);
                try {
                    $planification = $this->configureRapide($config);
                    $results->push([
                        'success' => true,
                        'planification' => $planification,
                        'selection' => $selection
                    ]);
                } catch (\Exception $e) {
                    $results->push([
                        'success' => false,
                        'error' => $e->getMessage(),
                        'selection' => $selection
                    ]);
                }
            }
        });
        
        return $results;
    }
    
    /**
     * Récupérer les options de configuration pour une matière
     */
    public function getConfigurationOptions(int $matiereId, ?int $anneeId = null): array
    {
        $matiere = ESBTPMatiere::with(['filieres', 'niveaux'])->findOrFail($matiereId);
        
        return [
            'matiere' => $matiere,
            'filieres_disponibles' => $matiere->filieres,
            'niveaux_disponibles' => $matiere->niveaux,
            'planifications_existantes' => $this->getExistingPlanifications($matiereId, $anneeId),
            'volume_horaire_recommande' => $this->getVolumeHoraireRecommande($matiere),
            'enseignants_disponibles' => $this->getEnseignantsDisponibles($matiere)
        ];
    }
    
    /**
     * Valider les données de configuration rapide
     */
    private function validateRapideData(array $data): array
    {
        $validator = Validator::make($data, [
            'matiere_id' => 'required|exists:esbtp_matieres,id',
            'annee_id' => 'required|exists:esbtp_annees_universitaires,id',
            'filiere_id' => 'nullable|exists:esbtp_filieres,id',
            'niveau_id' => 'nullable|exists:esbtp_niveau_etudes,id',
            'volume_horaire' => 'required|numeric|min:1|max:500',
            'semestre' => 'nullable|integer|in:1,2',
            'periode' => 'nullable|in:S1,S2,Annuel'
        ]);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        
        return $validator->validated();
    }
    
    /**
     * Valider les données de configuration avancée
     */
    private function validateAvanceData(array $data): array
    {
        $validator = Validator::make($data, [
            'matiere_id' => 'required|exists:esbtp_matieres,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annees_universitaires,id',
            'filiere_id' => 'nullable|exists:esbtp_filieres,id',
            'niveau_etude_id' => 'nullable|exists:esbtp_niveau_etudes,id',
            'volume_horaire_total' => 'required|numeric|min:1|max:500',
            'volume_horaire_cm' => 'nullable|numeric|min:0',
            'volume_horaire_td' => 'nullable|numeric|min:0',
            'volume_horaire_tp' => 'nullable|numeric|min:0',
            'coefficient' => 'nullable|numeric|min:0.5|max:10',
            'credits_ects' => 'nullable|integer|min:1|max:30',
            'enseignant_principal_id' => 'nullable|exists:users,id',
            'semestre' => 'nullable|integer|in:1,2',
            'statut' => 'nullable|in:planifie,valide,archive'
        ]);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        
        // Validation supplémentaire : CM + TD + TP <= Total
        $validated = $validator->validated();
        $total = $validated['volume_horaire_total'];
        $sommeDetails = ($validated['volume_horaire_cm'] ?? 0) + 
                       ($validated['volume_horaire_td'] ?? 0) + 
                       ($validated['volume_horaire_tp'] ?? 0);
        
        if ($sommeDetails > $total) {
            throw ValidationException::withMessages([
                'volume_horaire_total' => 'Le volume total doit être supérieur ou égal à la somme CM + TD + TP'
            ]);
        }
        
        return $validated;
    }
    
    /**
     * Trouver une planification existante
     */
    private function findExistingPlanification(int $matiereId, int $anneeId, ?int $filiereId, ?int $niveauId, int $semestre = 1): ?ESBTPPlanificationAcademique
    {
        return ESBTPPlanificationAcademique::where('matiere_id', $matiereId)
            ->where('annee_universitaire_id', $anneeId)
            ->where('filiere_id', $filiereId)
            ->where('niveau_etude_id', $niveauId)
            ->where('semestre', $semestre)
            ->first();
    }
    
    /**
     * Créer une planification rapide
     */
    private function createPlanification(array $data): ESBTPPlanificationAcademique
    {
        return ESBTPPlanificationAcademique::create([
            'matiere_id' => $data['matiere_id'],
            'annee_universitaire_id' => $data['annee_id'],
            'filiere_id' => $data['filiere_id'] ?? null,
            'niveau_etude_id' => $data['niveau_id'] ?? null,
            'volume_horaire_total' => $data['volume_horaire'],
            'semestre' => $data['semestre'] ?? 1,
            'statut' => 'planifie',
            'created_by' => auth()->id()
        ]);
    }
    
    /**
     * Créer une planification avancée
     */
    private function createPlanificationAvancee(array $data): ESBTPPlanificationAcademique
    {
        return ESBTPPlanificationAcademique::create([
            'matiere_id' => $data['matiere_id'],
            'annee_universitaire_id' => $data['annee_universitaire_id'],
            'filiere_id' => $data['filiere_id'] ?? null,
            'niveau_etude_id' => $data['niveau_etude_id'] ?? null,
            'volume_horaire_total' => $data['volume_horaire_total'],
            'volume_horaire_cm' => $data['volume_horaire_cm'] ?? 0,
            'volume_horaire_td' => $data['volume_horaire_td'] ?? 0,
            'volume_horaire_tp' => $data['volume_horaire_tp'] ?? 0,
            'coefficient' => $data['coefficient'] ?? null,
            'credits_ects' => $data['credits_ects'] ?? null,
            'enseignant_principal_id' => $data['enseignant_principal_id'] ?? null,
            'semestre' => $data['semestre'] ?? 1,
            'statut' => $data['statut'] ?? 'planifie',
            'created_by' => auth()->id()
        ]);
    }
    
    /**
     * Mettre à jour une planification (mode rapide)
     */
    private function updatePlanification(ESBTPPlanificationAcademique $planification, array $data): ESBTPPlanificationAcademique
    {
        $planification->update([
            'volume_horaire_total' => $data['volume_horaire'],
            'semestre' => $data['semestre'] ?? $planification->semestre,
            'updated_by' => auth()->id()
        ]);
        
        return $planification->fresh();
    }
    
    /**
     * Mettre à jour une planification (mode avancé)
     */
    private function updatePlanificationAvancee(ESBTPPlanificationAcademique $planification, array $data): ESBTPPlanificationAcademique
    {
        $planification->update([
            'volume_horaire_total' => $data['volume_horaire_total'],
            'volume_horaire_cm' => $data['volume_horaire_cm'] ?? $planification->volume_horaire_cm,
            'volume_horaire_td' => $data['volume_horaire_td'] ?? $planification->volume_horaire_td,
            'volume_horaire_tp' => $data['volume_horaire_tp'] ?? $planification->volume_horaire_tp,
            'coefficient' => $data['coefficient'] ?? $planification->coefficient,
            'credits_ects' => $data['credits_ects'] ?? $planification->credits_ects,
            'enseignant_principal_id' => $data['enseignant_principal_id'] ?? $planification->enseignant_principal_id,
            'semestre' => $data['semestre'] ?? $planification->semestre,
            'statut' => $data['statut'] ?? $planification->statut,
            'updated_by' => auth()->id()
        ]);
        
        return $planification->fresh();
    }
    
    /**
     * Récupérer les planifications existantes pour une matière
     */
    private function getExistingPlanifications(int $matiereId, ?int $anneeId = null): Collection
    {
        $query = ESBTPPlanificationAcademique::where('matiere_id', $matiereId)
            ->with(['anneeUniversitaire', 'filiere', 'niveauEtude', 'enseignantPrincipal']);
            
        if ($anneeId) {
            $query->where('annee_universitaire_id', $anneeId);
        }
        
        return $query->get();
    }
    
    /**
     * Obtenir le volume horaire recommandé pour une matière
     */
    private function getVolumeHoraireRecommande(ESBTPMatiere $matiere): ?int
    {
        // Logique pour recommander un volume horaire basé sur:
        // - Le type de matière
        // - Les volumes historiques
        // - Les standards de l'établissement
        
        return $matiere->total_heures_default ?? null;
    }
    
    /**
     * Obtenir les enseignants disponibles pour une matière
     */
    private function getEnseignantsDisponibles(ESBTPMatiere $matiere): Collection
    {
        // Récupérer les enseignants qui peuvent enseigner cette matière
        // Basé sur leurs spécialisations, disponibilités, etc.
        
        return collect(); // Placeholder - à implémenter selon la logique métier
    }
}