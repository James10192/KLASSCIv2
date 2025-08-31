<?php

namespace App\Services;

use App\Models\ESBTPEtudiant;
use App\Models\ESBTPRegleAcademique;
use App\Models\ESBTPClasse;
use App\Models\ESBTPNote;
use App\Models\ESBTPMatiere;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReeinscriptionService
{
    public function analyserSituationEtudiant($etudiantId, $anneeAcademique)
    {
        $etudiant = ESBTPEtudiant::with(['classe.niveau', 'classe.filiere'])->findOrFail($etudiantId);
        
        if (!$etudiant->classe) {
            throw new \Exception("Étudiant non assigné à une classe");
        }

        $niveauNom = $etudiant->classe->niveau ? $etudiant->classe->niveau->name : '';
        $filiereNom = $etudiant->classe->filiere ? $etudiant->classe->filiere->name : '';
        
        $regle = ESBTPRegleAcademique::getRegleForNiveauFiliere($niveauNom, $filiereNom);

        // Si pas de règle trouvée, chercher une règle par défaut
        if (!$regle) {
            $regle = ESBTPRegleAcademique::where('niveau', '')->where('filiere', '')->where('actif', true)->first();
        }
        
        // Si toujours pas de règle, créer une règle par défaut
        if (!$regle) {
            $regle = $this->creerRegleParDefaut($niveauNom, $filiereNom);
        }

        $notes = $this->getNotesEtudiant($etudiantId, $anneeAcademique);
        $moyenneGenerale = $this->calculerMoyenneGenerale($notes);
        $matieresEchouees = $this->getMatieresEchouees($notes, $regle->moyenne_passage);
        
        return [
            'etudiant' => $etudiant,
            'regle' => $regle,
            'moyenne_generale' => $moyenneGenerale,
            'notes' => $notes,
            'matieres_echouees' => $matieresEchouees,
            'decision' => $this->determinerDecision($moyenneGenerale, $matieresEchouees, $regle),
            'peut_passer' => $regle->peutPasser($moyenneGenerale),
            'peut_rattraper' => $regle->peutRattraper($moyenneGenerale),
            'doit_redoubler' => $regle->doitRedoubler($moyenneGenerale)
        ];
    }
    
    public function analyserSituationEtudiantParInscription($inscription, $anneeAcademique)
    {
        $etudiant = $inscription->etudiant;
        $classe = $inscription->classe;
        
        if (!$classe) {
            throw new \Exception("Classe manquante pour l'étudiant {$etudiant->prenom} {$etudiant->nom}");
        }

        $niveauNom = $classe->niveau ? $classe->niveau->name : '';
        $filiereNom = $classe->filiere ? $classe->filiere->name : '';
        
        $regle = ESBTPRegleAcademique::getRegleForNiveauFiliere($niveauNom, $filiereNom);

        // Si pas de règle trouvée, chercher une règle par défaut
        if (!$regle) {
            $regle = ESBTPRegleAcademique::where('niveau', '')->where('filiere', '')->where('actif', true)->first();
        }
        
        // Si toujours pas de règle, créer une règle par défaut
        if (!$regle) {
            $regle = $this->creerRegleParDefaut($niveauNom, $filiereNom);
        }

        $notes = $this->getNotesEtudiant($etudiant->id, $anneeAcademique);
        $moyenneGenerale = $this->calculerMoyenneGenerale($notes);
        $matieresEchouees = $this->getMatieresEchouees($notes, $regle->moyenne_passage);
        
        return [
            'etudiant' => $etudiant,
            'classe' => $classe,
            'inscription' => $inscription,
            'regle' => $regle,
            'moyenne_generale' => $moyenneGenerale,
            'notes' => $notes,
            'matieres_echouees' => $matieresEchouees,
            'decision' => $this->determinerDecision($moyenneGenerale, $matieresEchouees, $regle),
            'peut_passer' => $regle->peutPasser($moyenneGenerale),
            'peut_rattraper' => $regle->peutRattraper($moyenneGenerale),
            'doit_redoubler' => $regle->doitRedoubler($moyenneGenerale)
        ];
    }

    public function getEtudiantsParDecision($anneeAcademique)
    {
        // Récupérer l'année académique courante (is_current = true)
        $anneeUniversitaire = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();
        
        // Récupérer les étudiants via leurs inscriptions validées - FILTRÉS PAR ANNÉE COURANTE
        $inscriptions = \App\Models\ESBTPInscription::with(['etudiant', 'classe.niveau', 'classe.filiere'])
            ->whereNotNull('classe_id')
            ->whereNotNull('etudiant_id')
            ->when($anneeUniversitaire, function($query) use ($anneeUniversitaire) {
                return $query->where('annee_universitaire_id', $anneeUniversitaire->id);
            })
            ->get();
        
        $resultat = [
            'passages' => [],
            'rattrapages' => [],
            'redoublements' => [],
            'errors' => []
        ];

        foreach ($inscriptions as $inscription) {
            if ($inscription->etudiant && $inscription->classe) {
                try {
                    $analyse = $this->analyserSituationEtudiantParInscription($inscription, $anneeAcademique);
                    
                    switch ($analyse['decision']) {
                        case 'passage':
                            $resultat['passages'][] = $analyse;
                            break;
                        case 'rattrapage':
                            $resultat['rattrapages'][] = $analyse;
                            break;
                        case 'redoublement':
                            $resultat['redoublements'][] = $analyse;
                            break;
                    }
                } catch (\Exception $e) {
                    $resultat['errors'][] = [
                        'etudiant' => $inscription->etudiant,
                        'error' => $e->getMessage()
                    ];
                }
            }
        }

        // Ajouter les étudiants sans inscription validée dans les erreurs - FILTRÉS PAR ANNÉE COURANTE
        $etudiantsSansInscription = ESBTPEtudiant::whereDoesntHave('inscriptions', function($query) use ($anneeUniversitaire) {
            $query->whereNotNull('classe_id')
                  ->when($anneeUniversitaire, function($subQuery) use ($anneeUniversitaire) {
                      return $subQuery->where('annee_universitaire_id', $anneeUniversitaire->id);
                  });
        })->get();
        
        foreach ($etudiantsSansInscription as $etudiant) {
            $resultat['errors'][] = [
                'etudiant' => $etudiant,
                'error' => 'Inscription en cours - Non encore validée'
            ];
        }

        return $resultat;
    }

    /**
     * Obtenir seulement les statistiques (compteurs) pour optimiser les performances
     */
    public function getStatistiquesReinscription($anneeAcademique)
    {
        // Récupérer l'année académique courante (is_current = true)
        $anneeUniversitaire = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();
        
        $statistiques = [
            'passages' => 0,
            'rattrapages' => 0,
            'redoublements' => 0,
            'valides' => 0,
            'abandons_annee' => 0,
            'abandons_ecole' => 0,
            'errors' => 0
        ];
        
        if (!$anneeUniversitaire) {
            return $statistiques;
        }
        
        // CORRECTION: Faire le vrai calcul mais optimisé (avec cache potentiel)
        $inscriptions = \App\Models\ESBTPInscription::with(['etudiant', 'classe.niveau', 'classe.filiere'])
            ->whereNotNull('classe_id')
            ->whereNotNull('etudiant_id')
            ->where('annee_universitaire_id', $anneeUniversitaire->id)
            ->get();
            
        foreach ($inscriptions as $inscription) {
            if ($inscription->etudiant && $inscription->classe) {
                try {
                    $analyse = $this->analyserSituationEtudiantParInscription($inscription, $anneeAcademique);
                    $statistiques[$analyse['decision']]++;
                } catch (\Exception $e) {
                    $statistiques['errors']++;
                }
            } else {
                $statistiques['errors']++;
            }
        }
        
        // Compter les réinscriptions validées
        $statistiques['valides'] = \App\Models\ESBTPInscription::where('reinscription_status', 'validated')
            ->where('annee_universitaire_id', $anneeUniversitaire->id)
            ->count();
            
        // Compter les abandons par type
        $statistiques['abandons_annee'] = ESBTPEtudiant::where('statut', 'abandon')
            ->where(function($query) {
                $query->where('abandon_type', 'annee_scolaire')
                      ->orWhereNull('abandon_type');
            })
            ->whereHas('inscriptions', function($query) use ($anneeUniversitaire) {
                $query->where('annee_universitaire_id', $anneeUniversitaire->id);
            })
            ->count();
            
        $statistiques['abandons_ecole'] = ESBTPEtudiant::where('statut', 'abandon')
            ->where('abandon_type', 'ecole')
            ->whereHas('inscriptions', function($query) use ($anneeUniversitaire) {
                $query->where('annee_universitaire_id', $anneeUniversitaire->id);
            })
            ->count();
            
        // Compter les étudiants sans inscription validée
        $etudiantsSansInscription = ESBTPEtudiant::whereDoesntHave('inscriptions', function($query) use ($anneeUniversitaire) {
            $query->whereNotNull('classe_id')
                  ->where('annee_universitaire_id', $anneeUniversitaire->id);
        })->count();
        
        $statistiques['errors'] += $etudiantsSansInscription;
        
        return $statistiques;
    }

    public function proposerNouvellesClasses($etudiantId, $decision)
    {
        $etudiant = ESBTPEtudiant::with(['classe.niveau', 'classe.filiere'])->findOrFail($etudiantId);
        
        switch ($decision) {
            case 'passage':
                return $this->getClassesNiveauSuperieut($etudiant->classe);
            case 'redoublement':
                return $this->getClassesMemeNiveau($etudiant->classe);
            case 'rattrapage':
                return [$etudiant->classe]; // Reste dans la même classe
        }

        return [];
    }

    public function effectuerReinscription($etudiantId, $nouvelleClasseId, $decision, $observations = null, $selectedOptionals = [])
    {
        \DB::beginTransaction();
        try {
            // 1. Vérifications préalables
            $etudiant = ESBTPEtudiant::findOrFail($etudiantId);
            
            if (!$this->peutSeReinscrire($etudiantId)) {
                throw new \Exception("L'étudiant doit solder tous ses frais avant la réinscription");
            }
            
            // 2. Données de la nouvelle inscription
            $nouvelleClasse = ESBTPClasse::findOrFail($nouvelleClasseId);
            $nouvelleAnnee = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();
            
            if (!$nouvelleAnnee) {
                throw new \Exception("Aucune année universitaire active trouvée");
            }
            
            // 3. Créer nouvelle inscription
            $nouvelleInscription = \App\Models\ESBTPInscription::create([
                'etudiant_id' => $etudiantId,
                'annee_universitaire_id' => $nouvelleAnnee->id,
                'classe_id' => $nouvelleClasseId,
                'filiere_id' => $nouvelleClasse->filiere_id,
                'niveau_id' => $nouvelleClasse->niveau_etude_id,
                'type_inscription' => 'reinscription',
                'date_inscription' => now(),
                'status' => 'active',
                'workflow_step' => 'inscrit',
                'observations' => $observations,
                'created_by' => auth()->id(),
                'numero_recu' => $this->genererNumeroRecu($nouvelleAnnee, $nouvelleClasse)
            ]);
            
            // 4. Générer nouveaux frais via service existant
            $inscriptionService = app(\App\Services\ESBTPInscriptionService::class);
            $generatedFees = $inscriptionService->generateFeesForInscription(
                $nouvelleInscription, 
                $selectedOptionals
            );
            
            // 5. Créer facture automatique
            $this->creerFactureReinscription($nouvelleInscription, $generatedFees);
            
            // 6. Mise à jour statut étudiant
            $etudiant->update([
                'statut' => $this->getStatutFromDecision($decision)
            ]);
            
            // 7. Historique complet
            $this->sauvegarderHistoriqueComplet($etudiant, $decision, $observations, $nouvelleInscription, $generatedFees);
            
            \DB::commit();
            return $nouvelleInscription;
            
        } catch (\Exception $e) {
            \DB::rollback();
            throw $e;
        }
    }

    private function getNotesEtudiant($etudiantId, $anneeAcademique)
    {
        // Récupérer les notes filtrées par année académique (utilise le champ STRING annee_universitaire)
        return ESBTPNote::where('etudiant_id', $etudiantId)
            ->where('annee_universitaire', $anneeAcademique)
            ->with(['evaluation.matiere', 'matiere'])
            ->get();
    }

    private function calculerMoyenneGenerale($notes)
    {
        if ($notes->isEmpty()) {
            return 0;
        }

        $moyennesParMatiere = $notes->groupBy(function($note) {
                // Utiliser matiere_id directement ou via evaluation
                return $note->matiere_id ?? $note->evaluation?->matiere?->id;
            })
            ->map(function($notesMatiere) {
                return $notesMatiere->avg('note');
            });

        return $moyennesParMatiere->avg();
    }

    private function getMatieresEchouees($notes, $moyennePassage)
    {
        $moyennesParMatiere = $notes->groupBy(function($note) {
                // Utiliser matiere_id directement ou via evaluation
                return $note->matiere_id ?? $note->evaluation?->matiere?->id;
            })
            ->map(function($notesMatiere) {
                $premiereNote = $notesMatiere->first();
                $matiere = $premiereNote->matiere ?? $premiereNote->evaluation?->matiere;
                
                return [
                    'matiere' => $matiere,
                    'moyenne' => $notesMatiere->avg('note')
                ];
            })
            ->filter(function($item) use ($moyennePassage) {
                return $item['matiere'] && $item['moyenne'] < $moyennePassage;
            });

        return $moyennesParMatiere->values();
    }

    private function determinerDecision($moyenneGenerale, $matieresEchouees, $regle)
    {
        if ($regle->peutPasser($moyenneGenerale)) {
            return 'passage';
        }

        if ($regle->peutRattraper($moyenneGenerale) && 
            count($matieresEchouees) <= $regle->max_matieres_rattrapage) {
            return 'rattrapage';
        }

        return 'redoublement';
    }

    private function getClassesNiveauSuperieut($classeActuelle)
    {
        // Définir la hiérarchie des niveaux basée sur les codes
        $hierarchie = ['1A' => 1, '2A' => 2, 'L1' => 3, 'L2' => 4, 'L3' => 5, 'M1' => 6, 'M2' => 7];
        
        $niveauActuel = $classeActuelle->niveau->code ?? '';
        $ordreActuel = $hierarchie[$niveauActuel] ?? 0;
        
        // Trouver les codes de niveaux supérieurs
        $niveauxSuperieurs = array_keys(array_filter($hierarchie, function($ordre) use ($ordreActuel) {
            return $ordre > $ordreActuel;
        }));
        
        if (empty($niveauxSuperieurs)) {
            return collect(); // Aucun niveau supérieur
        }
        
        return ESBTPClasse::where('filiere_id', $classeActuelle->filiere_id)
            ->whereHas('niveau', function($query) use ($niveauxSuperieurs) {
                $query->whereIn('code', $niveauxSuperieurs);
            })
            ->with(['niveau', 'filiere'])
            ->get();
    }

    private function getClassesMemeNiveau($classeActuelle)
    {
        return ESBTPClasse::where('niveau_etude_id', $classeActuelle->niveau_etude_id)
            ->where('filiere_id', $classeActuelle->filiere_id)
            ->with(['niveau', 'filiere'])
            ->get();
    }

    private function sauvegarderHistoriqueComplet($etudiant, $decision, $observations, $nouvelleInscription, $generatedFees)
    {
        // Récupérer l'ancienne inscription active
        $ancienneInscription = $etudiant->inscriptions()
            ->where('annee_universitaire_id', '!=', $nouvelleInscription->annee_universitaire_id)
            ->where('status', 'active')
            ->latest()
            ->first();
            
        \Log::info("Réinscription effectuée avec nouvelle inscription", [
            'etudiant_id' => $etudiant->id,
            'ancienne_inscription_id' => $ancienneInscription?->id,
            'ancienne_classe' => $ancienneInscription?->classe?->name ?? 'N/A',
            'nouvelle_inscription_id' => $nouvelleInscription->id,
            'nouvelle_classe' => $nouvelleInscription->classe->name,
            'nouvelle_annee' => $nouvelleInscription->anneeUniversitaire->libelle,
            'decision' => $decision,
            'observations' => $observations,
            'frais_generes_count' => count($generatedFees),
            'montant_total_nouveaux_frais' => array_sum(array_column($generatedFees, 'amount')),
            'date' => now()
        ]);
    }

    private function getStatutFromDecision($decision)
    {
        switch ($decision) {
            case 'passage':
                return 'actif';
            case 'redoublement':
                return 'redoublant';
            case 'rattrapage':
                return 'rattrapage';
            default:
                return 'actif';
        }
    }

    private function creerRegleParDefaut($niveau, $filiere)
    {
        \Log::info("Création automatique d'une règle académique", [
            'niveau' => $niveau,
            'filiere' => $filiere
        ]);

        // Créer une règle académique avec des valeurs par défaut sensées
        $regle = ESBTPRegleAcademique::create([
            'niveau' => $niveau,
            'filiere' => $filiere,
            'moyenne_passage' => 12.00, // Moyenne classique pour passer
            'moyenne_rattrapage' => 8.00, // Seuil minimum pour rattrapage
            'max_matieres_rattrapage' => 3, // Maximum 3 matières en rattrapage
            'autoriser_redoublement' => true,
            'max_redoublements' => 2, // Maximum 2 redoublements
            'conditions_speciales' => 'Règle créée automatiquement - À ajuster selon les besoins',
            'actif' => true
        ]);

        return $regle;
    }

    /**
     * Vérifier si un étudiant peut se réinscrire (doit être entièrement soldé)
     */
    public function peutSeReinscrire($etudiantId): bool
    {
        $etudiant = ESBTPEtudiant::findOrFail($etudiantId);
        $inscriptionActive = $etudiant->inscriptions()
            ->where('status', 'active')
            ->latest()
            ->first();
        
        if (!$inscriptionActive) return false;
        
        $soldeRestant = $this->calculerSoldeInscription($inscriptionActive);
        return $soldeRestant <= 0;
    }

    /**
     * Calculer le solde restant d'une inscription
     */
    private function calculerSoldeInscription($inscription): float
    {
        // Utiliser la logique existante de calcul des soldes
        $montantAttendu = $inscription->paiements()->sum('montant') + 
                         $inscription->frais_inscription + 
                         $inscription->montant_scolarite;
        
        $montantPaye = $inscription->paiements()
            ->where('status', 'validated')
            ->sum('montant');
            
        return $montantAttendu - $montantPaye;
    }

    /**
     * Générer un numéro de reçu pour la réinscription
     */
    private function genererNumeroRecu($annee, $classe): string
    {
        $prefix = 'REINSC';
        $anneeCode = $annee->code ?? date('Y');
        $numero = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return "{$prefix}-{$anneeCode}-{$numero}";
    }

    /**
     * Créer une facture pour la nouvelle inscription de réinscription
     */
    private function creerFactureReinscription($inscription, $generatedFees)
    {
        $facture = new \App\Models\ESBTPFacture();
        $facture->numero = 'FREINSC-' . date('Ymd') . '-' . str_pad($inscription->id, 5, '0', STR_PAD_LEFT);
        $facture->etudiant_id = $inscription->etudiant_id;
        $facture->inscription_id = $inscription->id;
        $facture->annee_universitaire_id = $inscription->annee_universitaire_id;
        $facture->date_emission = now();
        $facture->date_echeance = now()->addDays(15);
        $facture->montant_ht = collect($generatedFees)->sum('amount');
        $facture->taux_taxe = 0;
        $facture->montant_taxe = 0;
        $facture->montant_ttc = $facture->montant_ht + $facture->montant_taxe;
        $facture->montant_regle = 0;
        $facture->montant_du = $facture->montant_ttc;
        $facture->statut = 'émise';
        $facture->notes = 'Facture générée automatiquement pour réinscription';
        $facture->createur_id = auth()->id();
        $facture->save();

        // Générer les détails de la facture
        foreach ($generatedFees as $fee) {
            \App\Models\ESBTPFactureDetail::create([
                'facture_id' => $facture->id,
                'designation' => $fee['description'],
                'description' => "Frais de réinscription - " . $fee['description'],
                'quantite' => 1,
                'montant' => $fee['amount'],
                'total_ligne' => $fee['amount'],
            ]);
        }

        return $facture;
    }
}